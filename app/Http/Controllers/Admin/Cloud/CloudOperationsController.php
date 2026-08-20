<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cloud;

use App\Http\Controllers\Controller;
use App\Models\RuntimeBackupRun;
use App\Models\RuntimeLease;
use App\Models\RuntimeMetric;
use App\Models\RuntimeNode;
use App\Nexora\Cloud\Services\BackupOrchestrator;
use App\Nexora\Cloud\Services\HealthProbeService;
use App\Nexora\Cloud\Services\HaReadinessService;
use App\Nexora\Cloud\Services\NodeIdentity;
use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Cloud\Services\RestorePlanner;
use App\Nexora\Cloud\Services\RuntimeMetricsRecorder;
use App\Nexora\Cloud\Services\RuntimeTopology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CloudOperationsController extends Controller
{
    public function index(
        Request $request,
        RuntimeTopology $topology,
        HealthProbeService $health,
        NodeManager $nodes,
        NodeIdentity $identity,
        HaReadinessService $ha,
    ): Response {
        $nodes->heartbeat();
        $deep = $health->readiness(true);
        $staleSeconds = max(60, (int) config('nexora_cloud.node_stale_seconds', 180));

        return Inertia::render('Admin/Cloud/Index', [
            'topology' => $topology->snapshot(),
            'health' => $deep,
            'haCertification' => $ha->assess(),
            'currentNodeKey' => $identity->key(),
            'nodes' => RuntimeNode::query()->orderByDesc('last_heartbeat_at')->get()->map(fn (RuntimeNode $node): array => [
                'id' => $node->id,
                'node_key' => $node->node_key,
                'hostname' => $node->hostname,
                'role' => $node->role,
                'status' => $node->status,
                'version' => $node->version,
                'environment' => $node->environment,
                'last_heartbeat_at' => $node->last_heartbeat_at?->toIso8601String(),
                'stale' => $node->last_heartbeat_at === null || $node->last_heartbeat_at->lt(now()->subSeconds($staleSeconds)),
            ]),
            'leases' => RuntimeLease::query()->orderBy('name')->get()->map(fn (RuntimeLease $lease): array => [
                'id' => $lease->id,
                'name' => $lease->name,
                'owner' => $lease->owner_node_key,
                'expires_at' => $lease->expires_at?->toIso8601String(),
                'active' => $lease->expires_at !== null && $lease->expires_at->isFuture(),
            ]),
            'backups' => RuntimeBackupRun::query()->latest()->limit(25)->get()->map(fn (RuntimeBackupRun $run): array => [
                'id' => $run->id,
                'type' => $run->type,
                'status' => $run->status,
                'driver' => $run->driver,
                'bytes' => $run->bytes,
                'checksum' => $run->checksum_sha256,
                'error' => $run->error_message,
                'created_at' => $run->created_at?->toIso8601String(),
                'completed_at' => $run->completed_at?->toIso8601String(),
            ]),
            'metrics' => RuntimeMetric::query()->where('observed_at', '>=', now()->subDay())->latest('observed_at')->limit(60)->get()->groupBy('metric')->map(fn ($rows) => $rows->first()?->value)->all(),
            'oneTimeRestoreConfirmation' => $request->session()->pull('cloud.restore_confirmation'),
            'canManage' => $request->user()->hasPermission('cloud.operations.manage'),
            'canBackup' => $request->user()->hasPermission('cloud.backups.manage'),
        ]);
    }

    public function heartbeat(NodeManager $nodes): RedirectResponse
    {
        $nodes->heartbeat();
        return back()->with('success', 'Current node heartbeat recorded.');
    }

    public function status(Request $request, NodeManager $nodes): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:active,draining,maintenance']]);
        $nodes->setStatus($data['status']);
        return back()->with('success', 'Current node status changed to '.ucfirst($data['status']).'.');
    }

    public function metrics(RuntimeMetricsRecorder $metrics): RedirectResponse
    {
        $metrics->snapshot();
        return back()->with('success', 'Operational metrics snapshot recorded.');
    }

    public function backup(Request $request, BackupOrchestrator $backups): RedirectResponse
    {
        $run = $backups->createDatabaseBackup($request->user()->id);
        return back()->with($run->status === 'completed' ? 'success' : 'error', $run->status === 'completed' ? 'Database backup completed and checksum sealed.' : ($run->error_message ?: 'Database backup failed.'));
    }

    public function verify(RuntimeBackupRun $backup, BackupOrchestrator $backups): RedirectResponse
    {
        $result = $backups->verify($backup);
        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function restorePlan(Request $request, RuntimeBackupRun $backup, RestorePlanner $planner): RedirectResponse
    {
        $result = $planner->create($backup, $request->user()->id);
        $request->session()->flash('cloud.restore_confirmation', [
            'plan_id' => $result['record']->id,
            'confirmation' => $result['confirmation'],
            'expires_at' => $result['record']->expires_at?->toIso8601String(),
            'steps' => $result['record']->plan['steps'] ?? [],
        ]);
        return back()->with('warning', 'Restore plan created. Nexora does not perform unattended destructive restores.');
    }

    public function download(RuntimeBackupRun $backup, BackupOrchestrator $backups): StreamedResponse
    {
        abort_unless($backup->status === 'completed' && is_string($backup->storage_path) && $backup->storage_path !== '', 404);
        $disk = (string) ($backup->storage_disk ?: 'local');
        abort_unless(Storage::disk($disk)->exists($backup->storage_path), 404);
        $verified=$backups->verify($backup);
        abort_unless($verified['ok']===true,409,'Backup integrity verification failed; download was blocked.');
        $extension = pathinfo($backup->storage_path, PATHINFO_EXTENSION) ?: 'bin';
        return Storage::disk($disk)->download($backup->storage_path, 'nexora-backup-'.$backup->id.'.'.$extension, [
            'Cache-Control'=>'no-store, private',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }
}
