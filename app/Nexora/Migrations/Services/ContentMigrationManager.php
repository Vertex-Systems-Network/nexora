<?php

declare(strict_types=1);

namespace App\Nexora\Migrations\Services;

use App\Jobs\ProcessContentMigrationJob;
use App\Models\ContentMigrationRun;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ContentMigrationManager
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function stageWordPressWxr(UploadedFile $file, User $actor): ContentMigrationRun
    {
        $organization = $this->tenant->organization();
        if (! $organization || $organization->status !== 'active' || $actor->status !== 'active') {
            throw ValidationException::withMessages(['source' => 'An active organization and active user are required.']);
        }

        $bytes = $file->getSize();
        if (! is_int($bytes) || $bytes < 1 || $bytes > 52_428_800) {
            throw ValidationException::withMessages(['source' => 'WordPress WXR uploads must be between 1 byte and 50 MB.']);
        }
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xml', 'wxr'], true)) {
            throw ValidationException::withMessages(['source' => 'Upload a WordPress WXR XML file.']);
        }

        $temporaryPath = $file->getRealPath();
        if (! is_string($temporaryPath) || ! is_readable($temporaryPath)) {
            throw ValidationException::withMessages(['source' => 'The uploaded migration source could not be read.']);
        }
        $header = file_get_contents($temporaryPath, false, null, 0, 16_384);
        if (! is_string($header) || stripos($header, '<rss') === false || stripos($header, 'wordpress.org/export') === false) {
            throw ValidationException::withMessages(['source' => 'The uploaded XML does not look like a WordPress WXR export.']);
        }

        $sourceHash = hash_file('sha256', $temporaryPath);
        if (! is_string($sourceHash) || strlen($sourceHash) !== 64) {
            throw ValidationException::withMessages(['source' => 'Unable to fingerprint the uploaded migration source.']);
        }

        $existing = ContentMigrationRun::query()
            ->where('source_type', 'wordpress_wxr')
            ->where('source_hash', $sourceHash)
            ->first();
        if ($existing) {
            if ($existing->status === 'failed' && ! Storage::disk('local')->exists($existing->source_path)) {
                $this->restageExisting($existing, $file);
            }
            if (in_array($existing->status, ['queued', 'failed'], true)) {
                $this->dispatch($existing);
            }
            return $existing->refresh();
        }

        $id = (string) Str::uuid();
        $directory = 'nexora/migrations/'.$organization->id;
        $stored = Storage::disk('local')->putFileAs($directory, $file, $id.'.wxr.xml');
        if (! is_string($stored) || $stored === '') {
            throw ValidationException::withMessages(['source' => 'Unable to stage the migration source safely.']);
        }

        try {
            $run = ContentMigrationRun::query()->create([
                'id' => $id,
                'tenant_id' => $organization->id,
                'created_by' => $actor->id,
                'source_type' => 'wordpress_wxr',
                'source_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'source_path' => $stored,
                'source_hash' => $sourceHash,
                'source_bytes' => $bytes,
                'status' => 'queued',
                'options' => ['remote_media_fetch' => false, 'parser' => 'wordpress-wxr-v1'],
            ]);
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($stored);
            $run = ContentMigrationRun::query()
                ->where('source_type', 'wordpress_wxr')
                ->where('source_hash', $sourceHash)
                ->first();
            if (! $run) {
                throw $exception;
            }
        }

        $this->dispatch($run);
        return $run->refresh();
    }

    public function resume(ContentMigrationRun $run): ContentMigrationRun
    {
        if (! in_array($run->status, ['failed', 'queued'], true)) {
            throw ValidationException::withMessages(['run' => 'Only failed or queued migrations can be resumed.']);
        }
        if (! Storage::disk('local')->exists($run->source_path)) {
            throw ValidationException::withMessages(['run' => 'The staged source file is no longer available; upload it again.']);
        }

        $run->forceFill(['status' => 'queued', 'error_code' => null, 'failed_at' => null])->save();
        $this->dispatch($run);
        return $run->refresh();
    }

    private function restageExisting(ContentMigrationRun $run, UploadedFile $file): void
    {
        $stored = Storage::disk('local')->putFileAs(
            'nexora/migrations/'.$run->tenant_id,
            $file,
            $run->id.'.wxr.xml',
        );
        if (! is_string($stored) || $stored === '') {
            throw ValidationException::withMessages(['source' => 'Unable to restage the migration source.']);
        }
        $run->forceFill(['source_path' => $stored])->save();
    }

    private function dispatch(ContentMigrationRun $run): void
    {
        ProcessContentMigrationJob::dispatch($run->id)->onQueue('migrations');
    }
}
