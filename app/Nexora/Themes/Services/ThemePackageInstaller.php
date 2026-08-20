<?php

declare(strict_types=1);

namespace App\Nexora\Themes\Services;

use App\Models\QuarantinePackage;
use App\Models\SecurityScan;
use App\Models\Theme;
use App\Models\ThemeVersion;
use App\Nexora\Foundation\Filesystem\PortablePath;
use App\Nexora\Foundation\Transfers\TransferSafety;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

final readonly class ThemePackageInstaller
{
    public function __construct(private ThemeManifestValidator $validator, private VersionConstraintMatcher $versions, private TransferSafety $transfers)
    {
    }

    public function install(QuarantinePackage $package, SecurityScan $scan, ?int $userId): ThemeVersion
    {
        if ($scan->quarantine_package_id !== $package->id || $scan->decision !== 'allow' || $scan->status !== 'completed') {
            throw new \RuntimeException('Only a completed Sentinel ALLOW decision can enter the Theme Engine.');
        }
        if (($scan->manifest['type'] ?? null) !== 'theme') {
            throw new \InvalidArgumentException('Sentinel package manifest is not a theme package.');
        }
        if (! is_file((string) $package->path)) {
            throw new \RuntimeException('The scanned quarantine archive is no longer available.');
        }
        $currentSha256 = hash_file('sha256', (string) $package->path);
        if (! is_string($currentSha256) || ! hash_equals((string) $package->sha256, $currentSha256) || ! hash_equals((string) $scan->source_sha256, $currentSha256)) {
            throw new \RuntimeException('Theme archive changed after Sentinel approval. Re-upload and rescan the package.');
        }

        $themeBudget=(array)config('nexora-transfers.archives.theme',[]);
        $this->transfers->assertSourceFile((string)$package->path,(int)($themeBudget['max_source_bytes']??52_428_800),'Theme package');
        $zip = new ZipArchive();
        $opened = $zip->open((string) $package->path, ZipArchive::RDONLY);
        if ($opened !== true) {
            throw new \RuntimeException('Unable to reopen the scanned theme archive.');
        }

        try {
            $this->transfers->assertArchiveBudget($zip,$themeBudget,'Theme');
            $themeStat=$zip->statName('theme.json');
            if(!is_array($themeStat) || (int)($themeStat['size']??0) > (int)($themeBudget['max_text_entry_bytes']??2_097_152)) throw new \RuntimeException('Theme manifest exceeds the bounded inspection limit.');
            $themeJson = $zip->getFromName('theme.json');
            if (! is_string($themeJson)) {
                throw new \InvalidArgumentException('Theme packages require a root-level theme.json manifest.');
            }
            $manifest = $this->validator->parse($themeJson, (array) $scan->manifest);
            if ($manifest->engine !== 'nexora-safe-html') {
                throw new \RuntimeException('Only the non-executable nexora-safe-html theme engine can be installed in N0.20.');
            }
            $constraint = trim((string) (($scan->manifest['requires']['nexora'] ?? '')));
            if (! $this->versions->matches((string) config('nexora.version', '0.0.0'), $constraint)) {
                throw new \RuntimeException("Theme requires Nexora {$constraint}; this platform is ".config('nexora.version', '0.0.0').'.');
            }
            $this->assertArchivePolicy($zip, $manifest->templates, $manifest->stylesheet, $manifest->screenshot);

            $existing = Theme::query()->where('identifier', $manifest->identifier)->first();
            $existingVersion = $existing?->versions()->where('version', $manifest->version)->first();
            if ($existingVersion !== null) {
                if (! hash_equals((string) $existingVersion->sha256, (string) $package->sha256)) {
                    throw new \RuntimeException("Theme {$manifest->identifier} {$manifest->version} already exists with a different checksum. Publish a new semantic version instead of replacing immutable theme code.");
                }
                $package->forceFill(['status' => 'installed'])->save();
                return $existingVersion;
            }

            $safeId = preg_replace('/[^a-z0-9._-]+/', '-', $manifest->identifier) ?: 'theme';
            $storageRoot = storage_path('app/nexora/themes/'.$safeId.'/'.$manifest->version);
            $publicRoot = public_path('nexora-themes/'.$safeId.'/'.$manifest->version);
            $this->prepareEmptyDirectory($storageRoot);
            $this->prepareEmptyDirectory($publicRoot);

            try {
                $this->extractApprovedFiles($zip, $storageRoot, $publicRoot);
                $this->validateInstalledFiles($storageRoot, $manifest->templates, $manifest->stylesheet);

                return DB::transaction(function () use ($manifest, $storageRoot, $publicRoot, $package, $scan, $userId, $existing): ThemeVersion {
                    $theme = $existing ?? Theme::query()->create([
                        'identifier' => $manifest->identifier,
                        'name' => $manifest->name,
                        'description' => $manifest->description,
                        'status' => 'inactive',
                        'is_builtin' => false,
                        'created_by' => $userId,
                    ]);
                    $theme->forceFill(['name' => $manifest->name, 'description' => $manifest->description])->save();

                    $version = $theme->versions()->create([
                        'version' => $manifest->version,
                        'engine' => $manifest->engine,
                        'install_path' => $storageRoot,
                        'asset_base_path' => '/nexora-themes/'.$safeId.'/'.$manifest->version,
                        'sha256' => $package->sha256,
                        'manifest' => $manifest->toArray(),
                        'source_type' => 'sentinel-package',
                        'source_scan_id' => $scan->id,
                        'installed_by' => $userId,
                        'installed_at' => now(),
                    ]);

                    $package->forceFill(['status' => 'installed'])->save();

                    return $version;
                });
            } catch (\Throwable $exception) {
                $this->removeDirectory($storageRoot);
                $this->removeDirectory($publicRoot);
                throw $exception;
            }
        } finally {
            $zip->close();
        }
    }

    /** @param array<string,string> $templates */
    private function assertArchivePolicy(ZipArchive $zip, array $templates, ?string $stylesheet, ?string $screenshot): void
    {
        $allowedReferenced = array_values($templates);
        if ($stylesheet) $allowedReferenced[] = $stylesheet;
        if ($screenshot) $allowedReferenced[] = $screenshot;

        $caseFolded = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat) ? str_replace('\\', '/', (string) ($stat['name'] ?? '')) : '';
            if ($name === '') continue;
            $portableName = PortablePath::normalizeRelative(rtrim($name, '/'));
            $folded = strtolower($portableName);
            if (isset($caseFolded[$folded])) {
                throw new \RuntimeException("Theme archive contains a case-insensitive path collision [{$portableName}] versus [{$caseFolded[$folded]}].");
            }
            $caseFolded[$folded] = $portableName;
            $opsys = 0; $attributes = 0;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attributes) && (($attributes >> 16) & 0170000) === 0120000) {
                throw new \RuntimeException("Theme archive symbolic-link entry [{$portableName}] is forbidden.");
            }
            if (str_ends_with($name, '/')) continue;
            $name = $portableName;

            $basename = basename($name);
            if (in_array($basename, ['nexora.json', 'theme.json'], true)) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $size=(int)($stat['size']??0);
            if (in_array($ext,['html','css','svg','json'],true) && $size > (int)config('nexora-transfers.archives.theme.max_text_entry_bytes',2_097_152)) {
                throw new \RuntimeException("Theme text entry [{$name}] exceeds the bounded inspection limit.");
            }
            if ($ext === 'html' && in_array($name, $allowedReferenced, true)) continue;
            if (str_starts_with($name, 'assets/') && in_array($ext, ['css', 'png', 'jpg', 'jpeg', 'webp', 'svg', 'ico'], true)) continue;

            throw new \RuntimeException("Theme package contains unsupported executable or undeclared file [{$name}]. N0.20 safe themes contain declared HTML templates and static CSS/image assets only.");
        }
    }

    private function extractApprovedFiles(ZipArchive $zip, string $storageRoot, string $publicRoot): void
    {
        $maximum=(int)config('nexora-transfers.archives.theme.max_entry_uncompressed_bytes',20_971_520);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat) ? str_replace('\\', '/', (string) ($stat['name'] ?? '')) : '';
            if ($name === '' || str_ends_with($name, '/')) continue;
            $expected=max(0,(int)($stat['size']??0));
            $stream=$zip->getStream($name);
            if (! is_resource($stream)) throw new \RuntimeException("Unable to stream theme archive file [{$name}].");
            $target = $storageRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
            try { $this->transfers->copyStreamAtomically($stream,$target,$maximum,$expected,0755); }
            finally { fclose($stream); }

            $extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
            if (in_array($extension,['html','css','svg'],true)) {
                $contents=file_get_contents($target);
                if (! is_string($contents)) throw new \RuntimeException("Unable to inspect staged theme file [{$name}].");
                $this->assertStaticContentPolicy($name,$contents);
            }

            if (str_starts_with($name, 'assets/')) {
                $assetRelative = substr($name, strlen('assets/'));
                $this->transfers->copyFileAtomically(
                    $target,
                    $publicRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $assetRelative),
                    $maximum,
                    0755,
                );
            }
        }
    }

    /** @param array<string,string> $templates */
    private function validateInstalledFiles(string $root, array $templates, ?string $stylesheet): void
    {
        foreach ($templates as $template => $path) {
            $file = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (! is_file($file)) {
                throw new \RuntimeException("Theme template [{$template}] is missing after extraction.");
            }
            $contents = file_get_contents($file);
            if (! is_string($contents)) {
                throw new \RuntimeException("Theme template [{$template}] could not be read after extraction.");
            }
            if (in_array($template, ['home', 'document'], true)) {
                foreach (['{{ nx_head }}', '{{ nx_theme_assets }}', '{{ nx_schema }}', '{{ nx_content }}'] as $requiredSlot) {
                    if (! str_contains($contents, $requiredSlot)) {
                        throw new \RuntimeException("Theme template [{$template}] must include required platform slot {$requiredSlot}. Themes cannot suppress platform SEO/schema/content output.");
                    }
                }
            }
        }
        if ($stylesheet !== null && ! is_file($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $stylesheet))) {
            throw new \RuntimeException('Declared theme stylesheet is missing after extraction.');
        }
    }

    private function assertStaticContentPolicy(string $name, string $contents): void
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($extension, ['html', 'svg'], true)) {
            $blocked = [
                '/<script\b/i', '/\bon[a-z]+\s*=/i', '/javascript\s*:/i', '/<(?:iframe|object|embed|form|input|button|textarea|select|base)\b/i',
                '/(?:href|src|action|formaction)\s*=\s*[\'\"]https?:\/\//i',
                '/<meta\b[^>]*http-equiv\s*=\s*[\'\"]?refresh/i',
            ];
            foreach ($blocked as $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    throw new \RuntimeException("Theme markup [{$name}] contains executable or external content forbidden by the safe theme runtime.");
                }
            }
            if ($extension === 'html' && preg_match('/<[^>]*\{\{\s*nx_(?:head|content|schema|theme_assets)\s*\}\}[^>]*>/is', $contents) === 1) {
                throw new \RuntimeException("Theme template [{$name}] places a trusted Nexora raw slot inside an HTML tag/attribute. Raw slots are allowed only between markup elements.");
            }
        }
        if ($extension === 'css') {
            foreach (['/@import[^;]*https?:\/\//i', '/url\([^)]*https?:\/\//i', '/\bexpression\s*\(/i', '/-moz-binding\s*:/i'] as $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    throw new \RuntimeException("Theme stylesheet [{$name}] contains an external or executable construct forbidden by the safe theme runtime.");
                }
            }
        }
    }

    private function prepareEmptyDirectory(string $path): void
    {
        if (is_dir($path)) $this->removeDirectory($path);
        if (! mkdir($path, 0755, true) && ! is_dir($path)) throw new \RuntimeException("Unable to create theme directory [{$path}].");
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($path);
    }
}
