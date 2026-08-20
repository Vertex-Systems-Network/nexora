<?php

declare(strict_types=1);

namespace App\Nexora\Media\Contracts;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;

interface MediaManagerContract
{
    /** @param array<string,mixed> $metadata */
    public function upload(UploadedFile $file, ?int $folderId, ?int $userId, array $metadata = []): MediaAsset;
    public function delete(MediaAsset $asset): void;
    public function restore(MediaAsset $asset): void;
    public function forceDelete(MediaAsset $asset): void;
    /** @return array<string,mixed> */
    public function present(MediaAsset $asset): array;
}
