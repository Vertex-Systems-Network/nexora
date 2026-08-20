<?php

use Illuminate\Foundation\Inspiring;
use App\Nexora\Distribution\Services\NewsletterDispatchService;
use App\Nexora\Cloud\Services\BackupOrchestrator;
use App\Nexora\Cloud\Services\ClusterLeadership;
use App\Nexora\Cloud\Services\HealthProbeService;
use App\Nexora\Cloud\Services\HaReadinessService;
use App\Nexora\Cloud\Services\ClusterRehearsalService;
use App\Nexora\Cloud\Services\BackupRestoreRehearsalService;
use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Cloud\Services\RestorePlanner;
use App\Nexora\Cloud\Services\RuntimeMetricsRecorder;
use App\Nexora\Cloud\Services\RuntimeTopology;
use App\Models\RuntimeBackupRun;
use App\Nexora\Installation\Database\DatabaseRuntimeDoctor;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$leaderCheck = static fn (): bool => app(ClusterLeadership::class)->isSchedulerLeader();

Schedule::command('nexora:publishing:run')->everyMinute()->withoutOverlapping()->when($leaderCheck);


Artisan::command('nexora:distribution:run', function (NewsletterDispatchService $distribution) {
    $count = $distribution->queueDue();
    $this->info("Queued {$count} due newsletter campaign(s).");
})->purpose('Queue scheduled Nexora newsletter campaigns that are due.');

Schedule::command('nexora:distribution:run')->everyMinute()->withoutOverlapping()->when($leaderCheck);

// N0.26 first-party discovery maintenance. Search indexing is event-driven; this aggregation remains cheap and idempotent.
Schedule::command('nexora:analytics:aggregate')->hourly()->withoutOverlapping()->when($leaderCheck);
Schedule::command('nexora:seo:crawl')->dailyAt('03:15')->withoutOverlapping()->when(static function (): bool {
    try {
        return filter_var(app(\App\Nexora\Foundation\Contracts\SettingsContract::class)->get('seo.crawler.enabled', false), FILTER_VALIDATE_BOOL);
    } catch (\Throwable) {
        return false;
    }
})->when($leaderCheck);
Schedule::command('nexora:analytics:prune')->dailyAt('04:00')->withoutOverlapping()->when($leaderCheck);

Artisan::command('nexora:automation:prune', function (\App\Nexora\Foundation\Contracts\SettingsContract $settings) {
    $eventDays = max(7, min(365, (int) $settings->get('automation.event_retention_days', 30)));
    $receiptDays = max(7, min(365, (int) $settings->get('automation.webhook_receipt_retention_days', 30)));
    $events = \App\Models\AutomationEvent::query()->where('created_at','<',now()->subDays($eventDays))->whereDoesntHave('runs', fn ($query) => $query->whereIn('status',['queued','running']))->delete();
    $receipts = \App\Models\WebhookReceipt::query()->where('created_at','<',now()->subDays($receiptDays))->delete();
    $this->info("Pruned {$events} automation event(s) and {$receipts} webhook receipt(s).");
})->purpose('Prune retained Nexora automation events and inbound webhook receipts.');

Schedule::command('nexora:automation:prune')->dailyAt('04:30')->withoutOverlapping()->when($leaderCheck);

Schedule::call(function (): void {
    $now=now();
    app(\App\Nexora\Automation\Contracts\AutomationEventBusContract::class)->emit('schedule.hourly', ['schedule'=>['iso'=>$now->toIso8601String(),'hour'=>(int)$now->format('G')]], 'schedule', $now->format('Y-m-d-H'), 'schedule-hourly:'.$now->format('Y-m-d-H'));
})->name('nexora.automation.hourly')->hourly()->withoutOverlapping()->when($leaderCheck);

Schedule::call(function (): void {
    $now=now();
    app(\App\Nexora\Automation\Contracts\AutomationEventBusContract::class)->emit('schedule.daily', ['schedule'=>['iso'=>$now->toIso8601String(),'date'=>$now->toDateString()]], 'schedule', $now->toDateString(), 'schedule-daily:'.$now->toDateString());
})->name('nexora.automation.daily')->dailyAt('00:05')->withoutOverlapping()->when($leaderCheck);

Artisan::command('nexora:membership:expire', function (\App\Nexora\Membership\Contracts\MembershipManagerContract $memberships) {
    $count = 0;
    \App\Models\Membership::query()->whereIn('status', ['active','trial'])->whereNotNull('ends_at')->where('ends_at','<=',now())->chunkById(100, function ($rows) use ($memberships, &$count): void {
        foreach ($rows as $membership) { $memberships->setStatus($membership, 'expired'); $count++; }
    }, 'id');
    $this->info("Expired {$count} membership(s).");
})->purpose('Expire Nexora memberships whose access end time has passed.');

Artisan::command('nexora:helpdesk:sla-check', function (\App\Nexora\Helpdesk\Services\HelpdeskSlaService $sla) {
    $checked = 0; $breached = 0;
    \App\Models\HelpdeskTicket::query()->whereIn('status',['open','pending'])->chunkById(100, function ($tickets) use ($sla, &$checked, &$breached): void {
        foreach ($tickets as $ticket) { $before = $ticket->first_response_breached || $ticket->resolution_breached; $sla->refreshBreaches($ticket); $after = $ticket->first_response_breached || $ticket->resolution_breached; $checked++; if (! $before && $after) $breached++; }
    }, 'id');
    $this->info("Checked {$checked} ticket(s); {$breached} newly breached SLA target(s).");
})->purpose('Refresh Nexora Helpdesk first-response and resolution SLA breach state.');

Schedule::command('nexora:membership:expire')->hourly()->withoutOverlapping()->when($leaderCheck);
Schedule::command('nexora:helpdesk:sla-check')->everyFiveMinutes()->withoutOverlapping()->when($leaderCheck);


Artisan::command('nexora:node:heartbeat', function (NodeManager $nodes) {
    $node = $nodes->heartbeat();
    $this->info($node ? 'Node heartbeat recorded for '.$node->node_key.'.' : 'Runtime node table is not available yet.');
})->purpose('Record the current Nexora runtime node heartbeat.');

Artisan::command('nexora:node:drain', function (NodeManager $nodes) {
    $node = $nodes->setStatus('draining');
    $this->warn($node ? 'Node '.$node->node_key.' is draining and readiness will return HTTP 503.' : 'Runtime node table is not available yet.');
})->purpose('Drain this node from load-balancer readiness without killing in-flight requests.');

Artisan::command('nexora:node:activate', function (NodeManager $nodes) {
    $node = $nodes->setStatus('active');
    $this->info($node ? 'Node '.$node->node_key.' is active and can pass readiness checks.' : 'Runtime node table is not available yet.');
})->purpose('Return this Nexora node to active readiness.');

Artisan::command('nexora:cloud:status', function (RuntimeTopology $topology, HealthProbeService $health) {
    $this->line(json_encode(['topology'=>$topology->snapshot(),'readiness'=>$health->readiness(false)], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
})->purpose('Print Nexora distributed-runtime topology and readiness as JSON.');

Artisan::command('nexora:runtime:metrics', function (RuntimeMetricsRecorder $metrics) {
    $snapshot = $metrics->snapshot();
    $this->line(json_encode($snapshot, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
})->purpose('Record a Nexora runtime metrics snapshot.');

Artisan::command('nexora:runtime:prune', function () {
    $days = max(1, min(3650, (int) config('nexora_cloud.metric_retention_days', 30)));
    $deleted = \App\Models\RuntimeMetric::query()->where('observed_at','<',now()->subDays($days))->delete();
    $this->info("Pruned {$deleted} runtime metric row(s) older than {$days} day(s).");
})->purpose('Prune retained Nexora operational metrics.');

Artisan::command('nexora:backup:create', function (BackupOrchestrator $backups) {
    $run = $backups->createDatabaseBackup();
    $this->line(json_encode(['id'=>$run->id,'status'=>$run->status,'driver'=>$run->driver,'bytes'=>$run->bytes,'checksum'=>$run->checksum_sha256,'error'=>$run->error_message], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    return $run->status === 'completed' ? 0 : 1;
})->purpose('Create a protected checksum-sealed Nexora database backup when the configured driver supports in-app snapshots.');

Artisan::command('nexora:backup:verify {backup}', function (string $backup, BackupOrchestrator $backups) {
    $run = RuntimeBackupRun::query()->findOrFail($backup);
    $result = $backups->verify($run);
    $this->line(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    return $result['ok'] ? 0 : 1;
})->purpose('Verify a Nexora runtime backup artifact against its persisted SHA-256 checksum.');

Artisan::command('nexora:restore:plan {backup}', function (string $backup, RestorePlanner $planner) {
    $run = RuntimeBackupRun::query()->findOrFail($backup);
    $result = $planner->create($run);
    $this->warn('One-time confirmation: '.$result['confirmation']);
    $this->line(json_encode($result['record']->plan, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
})->purpose('Generate an offline, checksum-verified restore plan. This command does not perform destructive restore automatically.');


Artisan::command('nexora:ha:status', function (HaReadinessService $ha) {
    $assessment = $ha->assess();
    $this->line(json_encode($assessment, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    return $assessment['ready'] ? 0 : 1;
})->purpose('Assess whether the current Nexora runtime configuration and observed nodes meet the strict HA readiness contract.');

Artisan::command('nexora:ha:rehearse', function (ClusterRehearsalService $rehearsal) {
    $result = $rehearsal->run();
    $this->line(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    return $result['status'] === 'pass' ? 0 : 1;
})->purpose('Safely rehearse Nexora lease failover and deep readiness on the current runtime. Multi-host evidence is still required for final HA certification.');

Artisan::command('nexora:backup:rehearse {backup}', function (string $backup, BackupRestoreRehearsalService $rehearsal) {
    $run = RuntimeBackupRun::query()->findOrFail($backup);
    $result = $rehearsal->validate($run);
    $this->line(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    return $result['status'] === 'pass' ? 0 : 1;
})->purpose('Verify a sealed backup and guarded restore plan without performing a destructive restore. Final evidence requires a disposable-target recovery rehearsal.');

// Heartbeats are intentionally NOT leader-gated: every node and every scheduler process must report itself.
Schedule::command('nexora:runtime:process-heartbeat scheduler')->everyMinute();
Schedule::command('nexora:node:heartbeat')->everyMinute();
Schedule::command('nexora:runtime:metrics')->everyFiveMinutes()->withoutOverlapping()->when($leaderCheck);
Schedule::command('nexora:runtime:prune')->dailyAt('04:45')->withoutOverlapping()->when($leaderCheck);


Artisan::command('nexora:database:doctor', function (DatabaseRuntimeDoctor $doctor) {
    $result=$doctor->inspect();
    $this->line(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    return $result['status']==='pass' ? 0 : 1;
})->purpose("Verify the configured primary database server meets Nexora's certified minimum server version.");
