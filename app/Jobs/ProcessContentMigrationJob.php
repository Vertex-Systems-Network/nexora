<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContentMigrationItem;
use App\Models\ContentMigrationRun;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantAuthorizationService;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use App\Nexora\Migrations\Services\WordPressContentImporter;
use App\Nexora\Migrations\WordPress\WordPressWxrReader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class ProcessContentMigrationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;
    public bool $failOnTimeout = true;
    public int $tries = 1;

    public function __construct(public string $runId) {}

    public function handle(
        TenantExecutionScope $tenantScope,
        TenantAuthorizationService $authorization,
        WordPressWxrReader $reader,
        WordPressContentImporter $importer,
    ): void {
        $tenantId = ContentMigrationRun::withoutGlobalScope('nexora_tenant')
            ->whereKey($this->runId)
            ->value('tenant_id');

        $tenantScope->runRequired(
            is_string($tenantId) ? $tenantId : null,
            "content migration {$this->runId}",
            function () use ($authorization, $reader, $importer): void {
                $run = $this->claim();
                if (! $run) {
                    return;
                }

                try {
                    $actor = $run->created_by ? User::query()->find((int) $run->created_by) : null;
                    if (! $actor || $actor->status !== 'active' || ! $authorization->allows($actor, 'documents.create')) {
                        throw new RuntimeException('The migration creator no longer has permission to import documents for this organization.');
                    }
                    if ($run->source_type !== 'wordpress_wxr') {
                        throw new RuntimeException('Unsupported content migration source type.');
                    }
                    if (! Storage::disk('local')->exists($run->source_path)) {
                        throw new RuntimeException('The staged migration source is unavailable.');
                    }

                    $path = Storage::disk('local')->path($run->source_path);
                    $seen = 0;
                    $alreadyImported = 0;

                    foreach ($reader->items($path) as $source) {
                        $seen++;
                        if ($seen > 20_000) {
                            throw new RuntimeException('The migration exceeds the 20,000 item safety limit.');
                        }

                        $outcome = $importer->import($run, $source);
                        if ($outcome === 'skipped') {
                            $alreadyImported++;
                        }

                        if ($seen % 25 === 0) {
                            $this->refreshProgress($run, $seen, $alreadyImported);
                        }
                    }

                    $this->refreshProgress($run, $seen, $alreadyImported);
                    $failed = ContentMigrationItem::query()
                        ->where('migration_run_id', $run->id)
                        ->where('status', 'failed')
                        ->count();
                    $completed = $failed === 0;
                    $sourceDeleted = false;
                    if ($completed) {
                        $sourceDeleted = Storage::disk('local')->delete($run->source_path);
                    }

                    $run->forceFill([
                        'status' => $completed ? 'completed' : 'completed_with_errors',
                        'completed_at' => now(),
                        'failed_at' => null,
                        'error_code' => null,
                        'result' => [
                            'parser' => 'wordpress-wxr-v1',
                            'source_deleted' => $sourceDeleted,
                            'remote_media_fetch' => false,
                        ],
                    ])->save();
                } catch (Throwable $exception) {
                    $run->forceFill([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'completed_at' => null,
                        'error_code' => 'migration_'.substr(hash('sha256', $exception::class.'|'.$exception->getMessage()), 0, 16),
                        'result' => ['message' => 'The migration run failed before completion.'],
                    ])->save();
                    throw $exception;
                }
            },
        );
    }

    private function claim(): ?ContentMigrationRun
    {
        return DB::transaction(function (): ?ContentMigrationRun {
            $run = ContentMigrationRun::query()->lockForUpdate()->findOrFail($this->runId);
            if ($run->status !== 'queued') {
                return null;
            }

            $run->forceFill([
                'status' => 'running',
                'cursor' => 0,
                'processed_items' => 0,
                'skipped_items' => 0,
                'started_at' => now(),
                'completed_at' => null,
                'failed_at' => null,
                'error_code' => null,
            ])->save();

            return $run->refresh();
        }, 3);
    }

    private function refreshProgress(ContentMigrationRun $run, int $seen, int $alreadyImported): void
    {
        $imported = ContentMigrationItem::query()
            ->where('migration_run_id', $run->id)
            ->where('status', 'imported')
            ->count();
        $failed = ContentMigrationItem::query()
            ->where('migration_run_id', $run->id)
            ->where('status', 'failed')
            ->count();

        $run->forceFill([
            'cursor' => $seen,
            'processed_items' => $imported + $failed,
            'imported_items' => $imported,
            'failed_items' => $failed,
            'skipped_items' => $alreadyImported,
        ])->save();
    }
}
