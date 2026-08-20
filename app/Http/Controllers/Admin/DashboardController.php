<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemHealth;
use App\Models\User;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(ModuleRegistryContract $modules): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'summary' => [
                'users' => User::query()->count(),
                'modules' => count($modules->manifests()),
                'healthIssues' => SystemHealth::query()->whereNot('status', 'healthy')->count(),
                'auditEvents24h' => AuditLog::query()->where('created_at', '>=', now()->subDay())->count(),
            ],
            'database' => [
                'driver' => DB::connection()->getDriverName(),
                'connected' => true,
            ],
        ]);
    }
}
