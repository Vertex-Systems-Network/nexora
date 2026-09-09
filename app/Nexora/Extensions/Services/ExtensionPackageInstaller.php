<?php

declare(strict_types=1);

namespace App\Nexora\Extensions\Services;

use App\Models\Extension;
use App\Models\ExtensionDependency;
use App\Models\ExtensionLifecycleEvent;
use App\Models\ExtensionVersion;
use App\Models\SupplyChainArtifact;
use App\Nexora\Foundation\Filesystem\PortablePath;
use App\Nexora\Foundation\Transfers\TransferSafety;
use App\Nexora\Security\Sentinel\Support\SentinelApprovalGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final readonly class ExtensionPackageInstaller
{
    public function __construct(
        private ExtensionManifestValidator $manifests,
        private TransferSafety $transfers,
        private SentinelApprovalGuard $approval,
    ) {}

    public function install(SupplyChainArtifact $artifact, ?int $actorId): ExtensionVersion
    {
        $artifact->loadMissing(['scan','package','publisher']);
        if (! $artifact->scan || $artifact->scan->decision !== 'allow') throw new RuntimeException('Sentinel must return ALLOW before an extension can be installed.');
        if (! $artifact->package || ! is_file($artifact->package->path)) throw new RuntimeException('The quarantined package archive is no longer available.');
        $this->approval->assertCurrent($artifact->package, $artifact->scan);
        $manifest = $this->manifests->validate((array) $artifact->scan->manifest);
        if ($artifact->package_identifier && $artifact->package_identifier !== $manifest->identifier) throw new RuntimeException('Supply-chain package identity does not match the Sentinel manifest.');
        if ($artifact->content_sha256 === '') throw new RuntimeException('Supply-chain content digest is required before installation.');

        $existing = Extension::query()->where('identifier',$manifest->identifier)->first();
        if ($existing) {
            $same = $existing->versions()->where('version',$manifest->version)->first();
            if ($same) {
                if (! hash_equals((string) $same->content_sha256, (string) $artifact->content_sha256)) throw new RuntimeException('The same extension version already exists with different package content. Version immutability blocked the install.');
                return $same;
            }
        }

        $installPath = storage_path('app/nexora/extensions/'.$manifest->identifier.'/'.$manifest->version);
        $extensionBudget=(array)config('nexora-transfers.archives.extension',[]);
        $this->transfers->assertSourceFile((string)$artifact->package->path,(int)($extensionBudget['max_source_bytes']??134_217_728),'Extension package');
        $this->extract($artifact->package->path, $installPath, $extensionBudget);

        try {
            return DB::transaction(function () use ($artifact,$actorId,$manifest,$installPath,$existing): ExtensionVersion {
            $extension = $existing ?? Extension::query()->create([
                'id'=>(string) Str::uuid(),'identifier'=>$manifest->identifier,'name'=>$manifest->name,'type'=>$manifest->type,'status'=>'installed',
                'publisher_id'=>$artifact->publisher_id,'description'=>$manifest->description,'metadata'=>['runtime_mode'=>$manifest->runtimeMode], 'installed_at'=>now(),
            ]);
            if ($existing) $extension->forceFill(['name'=>$manifest->name,'type'=>$manifest->type,'publisher_id'=>$artifact->publisher_id,'description'=>$manifest->description])->save();
            $version = ExtensionVersion::query()->create([
                'id'=>(string) Str::uuid(),'extension_id'=>$extension->id,'artifact_id'=>$artifact->id,'version'=>$manifest->version,'state'=>'installed',
                'content_sha256'=>$artifact->content_sha256,'install_path'=>$installPath,'compatibility_status'=>'compatible','runtime_mode'=>$manifest->runtimeMode,
                'migration_policy'=>$manifest->migrationPolicy,'schema_compatible_rollback'=>$manifest->schemaCompatibleRollback,'manifest'=>$manifest->raw,
                'installed_by'=>$actorId,'installed_at'=>now(),
            ]);
            foreach ($manifest->dependencies as $identifier=>$constraint) ExtensionDependency::query()->create(['extension_version_id'=>$version->id,'dependency_identifier'=>$identifier,'version_constraint'=>$constraint,'optional'=>false]);
            ExtensionLifecycleEvent::query()->create(['id'=>(string) Str::uuid(),'extension_id'=>$extension->id,'extension_version_id'=>$version->id,'event'=>'installed','status'=>'completed','context'=>['content_sha256'=>$artifact->content_sha256,'trust_tier'=>$artifact->trust_tier],'actor_id'=>$actorId,'created_at'=>now()]);
            return $version;
            });
        } catch (\Throwable $e) {
            if (is_dir($installPath)) {
                File::deleteDirectory($installPath);
            }
            throw $e;
        }
    }

    /** @param array<string,mixed> $budget */
    private function extract(string $archive, string $destination, array $budget): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archive, ZipArchive::RDONLY) !== true) throw new RuntimeException('Nexora could not open the verified extension archive.');
        $temp = $destination.'.staging-'.bin2hex(random_bytes(5));
        File::ensureDirectoryExists($temp, 0700, true);
        try {
            $this->transfers->assertArchiveBudget($zip,$budget,'Extension');
            $caseFolded = [];
            $maximum=max(1,(int)($budget['max_entry_uncompressed_bytes']??67_108_864));
            for ($i=0; $i<$zip->numFiles; $i++) {
                $stat=$zip->statIndex($i);
                $name=is_array($stat)?(string)($stat['name']??''):'';
                if ($name === '') throw new RuntimeException('Unsafe empty archive path detected during extension installation.');
                $normalized=PortablePath::normalizeRelative(rtrim(str_replace('\\','/',$name), '/'));
                $folded=strtolower($normalized);
                if (isset($caseFolded[$folded])) throw new RuntimeException("Extension archive contains a case-insensitive path collision [{$normalized}] versus [{$caseFolded[$folded]}].");
                $caseFolded[$folded]=$normalized;
                $opsys=0; $attributes=0;
                if ($zip->getExternalAttributesIndex($i,$opsys,$attributes) && (($attributes >> 16) & 0170000) === 0120000) throw new RuntimeException("Extension archive symbolic-link entry [{$normalized}] is forbidden.");
                $target=$temp.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$normalized);
                if (str_ends_with($name,'/')) { File::ensureDirectoryExists($target,0700,true); continue; }
                $expected=max(0,(int)(is_array($stat)?($stat['size']??0):0));
                $stream=$zip->getStream($name);
                if (! is_resource($stream)) throw new RuntimeException('Unable to read an extension archive entry.');
                try { $this->transfers->copyStreamAtomically($stream,$target,$maximum,$expected,0700); }
                finally { fclose($stream); }
            }
        } catch (\Throwable $e) {
            File::deleteDirectory($temp);
            throw $e;
        } finally { $zip->close(); }

        if (is_dir($destination) && ! File::deleteDirectory($destination)) {
            File::deleteDirectory($temp);
            throw new RuntimeException('Unable to remove a stale extension install directory before atomic publication.');
        }
        File::ensureDirectoryExists(dirname($destination),0700,true);
        if (! @rename($temp,$destination)) {
            File::deleteDirectory($temp);
            throw new RuntimeException('Unable to atomically publish staged extension files. Nexora refuses partial directory-copy fallback.');
        }
    }
}
