<?php

declare(strict_types=1);

namespace App\Nexora\Media\Services;

use App\Models\MediaAsset;
use App\Nexora\Foundation\Transfers\TransferSafety;
use App\Nexora\Media\Contracts\MediaManagerContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class MediaManager implements MediaManagerContract
{
    public function __construct(
        private MediaUploadPolicy $policy,
        private ImageVariantGenerator $variants,
        private TransferSafety $transfers,
    ) {}

    public function upload(UploadedFile $file, ?int $folderId, ?int $userId, array $metadata = []): MediaAsset
    {
        $inspection = $this->policy->inspect($file);
        $uuid = (string) Str::uuid();
        $storedName = $uuid.'.'.$inspection['extension'];
        $path = 'nexora/media/'.now()->format('Y/m').'/'.$storedName;
        $sourcePath = $file->getPathname();
        if ($sourcePath === '' || ! is_file($sourcePath) || ! is_readable($sourcePath)) throw new \RuntimeException('Nexora cannot read the temporary media upload.');
        $checksum = hash_file('sha256', $sourcePath);
        if (! is_string($checksum) || strlen($checksum) !== 64) throw new \RuntimeException('Nexora could not calculate the media checksum.');

        $mediaDisk=(string)config('nexora-storage-runtime.media_disk','public');$diskConfig=(array)config('filesystems.disks.'.$mediaDisk,[]);
        if (($diskConfig['driver']??null)==='local' && is_string($diskConfig['root']??null)) {
            $this->transfers->assertLocalCapacity((string)$diskConfig['root'], (int)$inspection['size']);
        }

        return DB::transaction(function () use ($file, $folderId, $userId, $metadata, $inspection, $uuid, $storedName, $path, $checksum, $sourcePath, $mediaDisk): MediaAsset {
            try {
                $stream = fopen($sourcePath, 'rb');
                if (! is_resource($stream)) throw new \RuntimeException('Nexora could not read the uploaded media payload.');
                try { $written = Storage::disk($mediaDisk)->put($path, $stream, ['visibility'=>'public']); }
                finally { fclose($stream); }
                if ($written !== true || ! Storage::disk($mediaDisk)->exists($path)) throw new \RuntimeException('Nexora could not write the uploaded file to the public media storage disk.');

                $storedSize=(int)Storage::disk($mediaDisk)->size($path);
                if ($storedSize !== (int)$inspection['size']) throw new \RuntimeException('Stored media size does not match the validated upload size. The partial object was rejected.');
                $storedStream=Storage::disk($mediaDisk)->readStream($path);
                if (! is_resource($storedStream)) throw new \RuntimeException('Nexora could not reopen the stored media object for verification.');
                try { $storedHash=$this->transfers->hashStream($storedStream, (int)config('nexora-transfers.media.max_upload_bytes',52_428_800)); }
                finally { fclose($storedStream); }
                if ($storedHash['bytes'] !== (int)$inspection['size'] || ! hash_equals($checksum,$storedHash['sha256'])) throw new \RuntimeException('Stored media checksum verification failed. The partial object was rejected.');

                $asset = MediaAsset::query()->create([
                    'uuid'=>$uuid,'folder_id'=>$folderId,'disk'=>$mediaDisk,'visibility'=>'public','media_type'=>$inspection['type'],
                    'mime_type'=>$inspection['mime'],'extension'=>$inspection['extension'],'original_name'=>mb_substr((string) $file->getClientOriginalName(), 0, 255),
                    'stored_name'=>$storedName,'path'=>$path,'size_bytes'=>$inspection['size'],'width'=>$inspection['width'],'height'=>$inspection['height'],
                    'checksum_sha256'=>$checksum,'title'=>trim((string) ($metadata['title'] ?? '')) ?: pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME),
                    'alt_text'=>trim((string) ($metadata['alt_text'] ?? '')) ?: null,'caption'=>trim((string) ($metadata['caption'] ?? '')) ?: null,
                    'description'=>trim((string) ($metadata['description'] ?? '')) ?: null,'metadata'=>[],'uploaded_by'=>$userId,
                ]);
                if ($inspection['type'] === 'image') {
                    $generated = $this->variants->generate($mediaDisk, $path, $inspection['mime'], (int) $inspection['width'], (int) $inspection['height']);
                    if ($generated !== []) $asset->forceFill(['variants'=>$generated])->save();
                }
                return $asset->fresh(['folder','uploader','usages']) ?? $asset;
            } catch (\Throwable $exception) {
                Storage::disk($mediaDisk)->delete($path);
                throw $exception;
            }
        });
    }

    public function delete(MediaAsset $asset): void { $asset->delete(); }
    public function restore(MediaAsset $asset): void { $asset->restore(); }

    public function forceDelete(MediaAsset $asset): void
    {
        $paths = [$asset->path];
        foreach ((array) $asset->variants as $variant) if (is_array($variant) && is_string($variant['path'] ?? null)) $paths[] = $variant['path'];
        DB::transaction(function () use ($asset, $paths): void {
            $asset->usages()->delete(); $asset->collections()->detach(); $asset->forceDelete();
            Storage::disk($asset->disk)->delete(array_values(array_unique($paths)));
        });
    }

    public function present(MediaAsset $asset): array
    {
        $variantUrls = [];
        foreach ((array) $asset->variants as $width => $variant) {
            if (! is_array($variant) || ! is_string($variant['path'] ?? null)) continue;
            $variantUrls[(string) $width] = url('/media/'.$asset->uuid.'/'.$width);
        }
        return [
            'id'=>$asset->id,'uuid'=>$asset->uuid,'title'=>$asset->title ?: $asset->original_name,'original_name'=>$asset->original_name,
            'media_type'=>$asset->media_type,'mime_type'=>$asset->mime_type,'size_bytes'=>(int) $asset->size_bytes,'width'=>$asset->width,'height'=>$asset->height,
            'alt_text'=>$asset->alt_text,'caption'=>$asset->caption,'description'=>$asset->description,'folder'=>$asset->folder?->name,
            'folder_id'=>$asset->folder_id,'url'=>$asset->publicUrl(),'variants'=>$variantUrls,'usages_count'=>$asset->usages_count ?? $asset->usages()->count(),
            'deleted_at'=>$asset->deleted_at?->toIso8601String(),'created_at'=>$asset->created_at?->toIso8601String(),
        ];
    }
}
