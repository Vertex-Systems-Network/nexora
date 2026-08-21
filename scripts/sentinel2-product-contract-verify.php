<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    'controller' => $root.'/app/Http/Controllers/Admin/Security/SentinelController.php',
    'recorder' => $root.'/app/Nexora/Security/Sentinel/Support/ScanRecorder.php',
    'failure' => $root.'/app/Nexora/Security/Sentinel/Support/SentinelFailureReference.php',
    'approval' => $root.'/app/Nexora/Security/Sentinel/Support/SentinelApprovalGuard.php',
    'theme' => $root.'/app/Nexora/Themes/Services/ThemePackageInstaller.php',
    'extension' => $root.'/app/Nexora/Extensions/Services/ExtensionPackageInstaller.php',
    'migration' => $root.'/database/migrations/2026_08_22_000300_sanitize_sentinel_scan_failures.php',
    'test' => $root.'/tests/Feature/Security/SentinelTrustHardeningTest.php',
];

$failures = [];
$files = [];
foreach ($paths as $key => $path) {
    if (! is_file($path)) {
        $failures[] = "Missing Sentinel 2.0 source file [{$key}].";
        $files[$key] = '';
        continue;
    }
    $content = file_get_contents($path);
    $files[$key] = is_string($content) ? $content : '';
    if ($files[$key] === '') $failures[] = "Unable to read Sentinel 2.0 source file [{$key}].";
}
$require = static function (string $key, string $needle, string $message) use (&$files, &$failures): void {
    if (! str_contains($files[$key] ?? '', $needle)) $failures[] = $message;
};
$forbid = static function (string $key, string $needle, string $message) use (&$files, &$failures): void {
    if (str_contains($files[$key] ?? '', $needle)) $failures[] = $message;
};

// Failure privacy: package-controlled/parser exception text must not become durable/UI audit metadata.
$require('failure', "'SNT-'", 'Sentinel failure references must use a stable opaque SNT prefix.');
$require('failure', 'class_fingerprint', 'Sentinel failure references must retain a non-secret exception-class fingerprint.');
$forbid('failure', 'getMessage()', 'Sentinel public failure-reference service must never include raw exception messages.');
$require('recorder', "'error' => $failure['message']", 'ScanRecorder must persist only the privacy-safe failure message.');
$require('recorder', "'class_fingerprint' => $failure['class_fingerprint']", 'ScanRecorder must retain the non-secret failure fingerprint.');
$forbid('recorder', "'error' => $exception->getMessage()", 'ScanRecorder must not persist raw exception messages.');
$forbid('controller', "['error' => $exception->getMessage()]", 'Sentinel audit events must not persist raw exception messages.');
$require('controller', "'error_reference' =>", 'Sentinel failed-scan audit must use the opaque error reference.');
$require('migration', 'Historical raw diagnostic details were removed', 'Legacy Sentinel scan errors must be scrubbed by a forward migration.');
$forbid('migration', 'DB::statement', 'Sentinel privacy migration must remain portable and avoid raw SQL.');
$forbid('migration', '->after(', 'Sentinel privacy migration must not depend on column-placement syntax.');

// SQL portability.
$require('controller', "CASE severity WHEN 'critical' THEN 1", 'Sentinel finding severity ordering must use portable SQL CASE ordering.');
$forbid('controller', 'FIELD(severity', 'MySQL-only FIELD severity ordering is forbidden.');

// Promotion trust: stale ALLOW scans and digest mutation must fail closed at UI and installer boundaries.
$require('approval', "latest('created_at')->value('id')", 'Sentinel approval guard must re-resolve the latest package scan.');
$require('approval', "'scanned', 'installed'", 'Sentinel approval guard must bound promotable package states.');
$require('approval', "hash_file('sha256'", 'Sentinel approval guard must re-hash the quarantined package before promotion.');
$require('approval', 'hash_equals((string) $package->sha256', 'Sentinel approval guard must compare current bytes to the quarantine baseline digest.');
$require('approval', 'hash_equals((string) $scan->source_sha256', 'Sentinel approval guard must compare current bytes to the approved scan digest.');
$require('controller', '$this->approval->assertCurrent(', 'Sentinel UI promotion discovery must require current approval.');
$require('theme', '$this->approval->assertCurrent($package, $scan);', 'Theme installation must enforce current Sentinel approval server-side.');
$require('extension', '$this->approval->assertCurrent($artifact->package, $artifact->scan);', 'Extension installation must enforce current Sentinel approval server-side.');

foreach ([
    'test_current_allow_scan_can_be_promoted',
    'test_old_allow_scan_is_rejected_after_newer_scan_exists',
    'test_approved_package_digest_mutation_is_rejected',
    'test_failure_reference_never_contains_raw_exception_message',
] as $method) {
    $require('test', $method, 'Missing Sentinel 2.0 acceptance regression: '.$method);
}

if ($failures !== []) {
    fwrite(STDERR, "Nexora Sentinel 2.0 Product Contract: FAIL\n");
    foreach ($failures as $failure) fwrite(STDERR, ' - '.$failure."\n");
    exit(1);
}

fwrite(STDOUT, "Nexora Sentinel 2.0 Product Contract: PASS\n");
fwrite(STDOUT, " - privacy-safe scan failure persistence + legacy scrub\n");
fwrite(STDOUT, " - portable cross-database finding ordering\n");
fwrite(STDOUT, " - latest-ALLOW + immutable digest promotion guard\n");
fwrite(STDOUT, " - Theme/Extension server-side stale-scan replay prevention\n");
