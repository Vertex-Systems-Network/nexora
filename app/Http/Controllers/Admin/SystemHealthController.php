<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Nexora\Foundation\Contracts\NexoraKernelContract;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Installation\InstallationState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class SystemHealthController extends Controller
{
    public function __invoke(NexoraKernelContract $kernel, InstallationState $installation, RuntimeProcessPlane $processPlane): Response
    {
        return Inertia::render('Admin/System/Health', [
            'checks' => [
                $this->check('Nexora Kernel', function () use ($kernel): void {
                    if (! $kernel->isBooted()) {
                        throw new RuntimeException('Kernel is not booted.');
                    }
                }),
                $this->check('Installation Lock', function () use ($installation): void {
                    if (! $installation->isInstalled()) {
                        throw new RuntimeException('Persistent installation lock is missing.');
                    }
                }),
                $this->check('Database', function (): void { DB::select('select 1'); }),
                $this->check('Cache', function (): void { Cache::put('nexora.health', 'ok', 10); }),
                $this->check('Storage', function (): void {
                    Storage::disk('local')->put('nexora/health/.probe', 'ok');
                    Storage::disk('local')->delete('nexora/health/.probe');
                }),
                $this->check('Media Storage', function (): void {
                    Storage::disk('public')->put('nexora/health/.media-probe', 'ok');
                    Storage::disk('public')->delete('nexora/health/.media-probe');
                }),
                $this->check('Sentinel ZIP Engine', function (): void {
                    if (! class_exists(\ZipArchive::class)) {
                        throw new RuntimeException('PHP ext-zip / ZipArchive is unavailable.');
                    }
                }),
                $this->check('Runtime Process Policy', function () use ($processPlane): void {
                    if (($processPlane->policy()['status'] ?? 'fail') !== 'pass') {
                        throw new RuntimeException('Runtime process-role policy is not safe.');
                    }
                }),
                $this->check('Sentinel Quarantine', function (): void {
                    $path = (string) config('sentinel.quarantine_path');
                    if ($path === '') {
                        throw new RuntimeException('Sentinel quarantine path is not configured.');
                    }
                    if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
                        throw new RuntimeException('Sentinel quarantine directory cannot be created.');
                    }
                    if (! is_writable($path)) {
                        throw new RuntimeException('Sentinel quarantine directory is not writable.');
                    }
                }),
            ],
            'runtime' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'database' => DB::connection()->getDriverName(),
                'processPlane' => $processPlane->current(false),
            ],
        ]);
    }

    /** @param callable(): void $probe */
    private function check(string $name, callable $probe): array
    {
        $startedAt = microtime(true);

        try {
            $probe();

            return [
                'name' => $name,
                'status' => 'healthy',
                'message' => 'Operational',
                'durationMs' => round((microtime(true) - $startedAt) * 1000, 2),
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'name' => $name,
                'status' => 'unhealthy',
                'message' => app()->isProduction() ? 'Check failed' : $exception->getMessage(),
                'durationMs' => round((microtime(true) - $startedAt) * 1000, 2),
            ];
        }
    }
}
