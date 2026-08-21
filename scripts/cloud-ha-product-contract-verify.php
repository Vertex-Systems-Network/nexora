<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    'leases' => $root.'/app/Nexora/Cloud/Services/RuntimeLeaseManager.php',
    'leadership' => $root.'/app/Nexora/Cloud/Services/ClusterLeadership.php',
    'readiness' => $root.'/app/Nexora/Cloud/Services/HaReadinessService.php',
    'health' => $root.'/app/Nexora/Cloud/Services/HealthProbeService.php',
    'console' => $root.'/routes/console.php',
    'test' => $root.'/tests/Feature/Cloud/DistributedRuntimeHardeningTest.php',
];

$failures = [];
$files = [];
foreach ($paths as $key => $path) {
    if (! is_file($path)) {
        $failures[] = "Missing Cloud/HA source file [{$key}].";
        $files[$key] = '';
        continue;
    }

    $content = file_get_contents($path);
    $files[$key] = is_string($content) ? $content : '';
    if ($files[$key] === '') {
        $failures[] = "Unable to read Cloud/HA source file [{$key}].";
    }
}

$require = static function (string $key, string $needle, string $message) use (&$files, &$failures): void {
    if (! str_contains($files[$key] ?? '', $needle)) {
        $failures[] = $message;
    }
};
$forbid = static function (string $key, string $needle, string $message) use (&$files, &$failures): void {
    if (str_contains($files[$key] ?? '', $needle)) {
        $failures[] = $message;
    }
};

// Coordination is fail-closed: absent lease storage can never imply ownership.
$missingTableFalse = "if (! Schema::hasTable('nx_runtime_leases')) return false;";
if (substr_count($files['leases'] ?? '', $missingTableFalse) < 2) {
    $failures[] = 'Runtime lease leadership and barrier-aware acquisition must both fail closed when the lease table is unavailable.';
}
$forbid(
    'leases',
    "if (! Schema::hasTable('nx_runtime_leases')) return true;",
    'Runtime lease acquisition must never fail open when coordination storage is unavailable.',
);
$require('leases', 'lockForUpdate()->first()', 'Runtime lease ownership must serialize contenders with a database row lock.');
$require('leases', 'if (! $expired && ! $sameOwner) return false;', 'Runtime lease ownership must reject a live competing owner.');
$require('leases', "where('owner_node_key', $owner)->update", 'Lease release must be owner-bound.');

// Scheduler leadership uses the shared lease boundary and runtime compatibility admission.
$require('leadership', 'private RuntimeLeaseManager $leases', 'Cluster leadership must use the shared runtime lease manager.');
$require('leadership', "acquireOrRenew('scheduler-leader'", 'Scheduler leadership must be represented by the scheduler-leader runtime lease.');
$require('leadership', '!$this->nodes->isReady()||!$this->versions->compatible()', 'Scheduler leadership must require node readiness and runtime-version compatibility.');

// HA readiness must reject ghost/stale scheduler owners, not just a live timestamp.
$require('readiness', "Schema::hasTable('nx_runtime_leases')", 'HA readiness must verify runtime lease storage exists.');
$require('readiness', "Schema::hasTable('nx_runtime_nodes')", 'HA readiness must verify runtime node storage exists.');
$require('readiness', "where('node_key', $lease->owner_node_key)", 'HA readiness must resolve the scheduler lease owner as an observed runtime node.');
$require('readiness', "where('status', 'active')", 'HA readiness must require the scheduler lease owner to be active.');
$require('readiness', "where('last_heartbeat_at', '>=', $now->copy()->subSeconds($freshSeconds))", 'HA readiness must require a fresh scheduler lease owner heartbeat.');
$require('readiness', 'scheduler lease owner is missing, stale, or inactive', 'HA readiness must fail closed for ghost/stale/inactive scheduler owners.');

// All coordinated schedules share one leadership decision. Heartbeats remain per-node and must not be leader-gated.
$require('console', '$leaderCheck = static fn (): bool => app(ClusterLeadership::class)->isSchedulerLeader();', 'Scheduled work must share the ClusterLeadership gate.');
$require('console', "Schedule::command('nexora:publishing:run')->everyMinute()->withoutOverlapping()->when($leaderCheck);", 'Publishing scheduler must be leader-gated.');
$require('console', "Schedule::command('nexora:runtime:metrics')->everyFiveMinutes()->withoutOverlapping()->when($leaderCheck);", 'Runtime metrics aggregation must be leader-gated.');
$require('console', "Schedule::command('nexora:runtime:process-heartbeat scheduler')->everyMinute();", 'Scheduler process heartbeat must run independently on every scheduler process.');
$require('console', "Schedule::command('nexora:node:heartbeat')->everyMinute();", 'Node heartbeat must run independently on every node.');
$forbid('console', "Schedule::command('nexora:node:heartbeat')->everyMinute()->when($leaderCheck)", 'Node heartbeat must never be restricted to the elected scheduler leader.');

// Public readiness does not disclose exception strings.
$require('health', "return ['name' => $name, 'status' => 'unhealthy', 'duration_ms' =>", 'Health probes must return bounded generic unhealthy state.');
$forbid('health', "'error' => $e->getMessage()", 'Public health probes must not disclose raw exception messages.');

foreach ([
    'test_lease_and_barrier_acquisition_fail_closed_when_coordination_table_is_unavailable',
    'test_runtime_lease_enforces_single_owner_and_allows_failover_after_release',
] as $method) {
    $require('test', $method, 'Missing N1.24 distributed-runtime regression: '.$method);
}

if ($failures !== []) {
    fwrite(STDERR, "Nexora Cloud / HA Product Contract: FAIL\n");
    foreach (array_values(array_unique($failures)) as $failure) {
        fwrite(STDERR, ' - '.$failure."\n");
    }
    exit(1);
}

fwrite(STDOUT, "Nexora Cloud / HA Product Contract: PASS\n");
fwrite(STDOUT, " - lease/barrier coordination fails closed without durable lease storage\n");
fwrite(STDOUT, " - scheduler leadership is serialized and runtime-compatible\n");
fwrite(STDOUT, " - HA readiness binds the scheduler lease to a fresh active node\n");
fwrite(STDOUT, " - coordinated schedules are leader-gated while per-node heartbeats remain independent\n");
