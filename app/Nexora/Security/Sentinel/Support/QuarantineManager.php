<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Support;

use App\Models\QuarantinePackage;
use App\Nexora\Foundation\Transfers\TransferSafety;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class QuarantineManager
{
    public function __construct(
        private readonly QuarantinePathGuard $paths,
        private readonly TransferSafety $transfers,
    ) {}

    public function store(UploadedFile $file, ?int $userId): QuarantinePackage
    {
        return $this->storeLocalFile($file->getPathname(), $file->getClientOriginalName(), $file->getClientMimeType(), $userId, true);
    }

    public function storeLocalFile(string $sourcePath, string $originalName, ?string $mimeType, ?int $userId, bool $move = false): QuarantinePackage
    {
        $maximum=max(1024,(int)config('sentinel.upload.max_kilobytes',51_200)*1024);
        $this->transfers->assertSourceFile($sourcePath,$maximum,'Sentinel package');
        $directory = (string) config('sentinel.quarantine_path');
        if ($directory === '') throw new \RuntimeException('Sentinel quarantine path is not configured.');

        $id = (string) Str::uuid();
        $storedName = $id.'.zip';
        $path = $directory.DIRECTORY_SEPARATOR.$storedName;
        $copied=$this->transfers->copyFileAtomically($sourcePath,$path,$maximum,0700);
        if ($move && is_file($sourcePath) && ! @unlink($sourcePath)) {
            @unlink($path);
            throw new \RuntimeException('Package was copied into quarantine but the transfer staging source could not be removed.');
        }
        @chmod($path, 0600);

        try {
            return QuarantinePackage::query()->create([
                'id' => $id,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'path' => $path,
                'sha256' => $copied['sha256'],
                'size_bytes' => $copied['bytes'],
                'mime_type' => $mimeType ?: 'application/zip',
                'status' => 'quarantined',
                'uploaded_by' => $userId,
            ]);
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }
    }

    public function delete(QuarantinePackage $package): void
    {
        $path = $package->path;
        if (is_string($path) && is_file($path)) $path = $this->paths->assertInside($path);
        if (is_string($path) && is_file($path) && ! @unlink($path)) throw new \RuntimeException('Unable to remove the quarantined package file. Database history was preserved.');
        $package->delete();
    }
}
