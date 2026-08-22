<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/n1-certification-session.php';
require_once $root.'/scripts/lib/target-evidence-intake.php';
require_once $root.'/app/Nexora/Foundation/Filesystem/AtomicFileWriter.php';

$baseUrl = '';
$auditor = '';
$w3cUrl = 'https://validator.w3.org/nu/';
$waveUrl = 'https://wave.webaim.org/api/request';
$waveKeyEnv = 'WAVE_API_KEY';
$alertsReviewed = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base-url=')) $baseUrl = trim(substr($arg, 11));
    elseif (str_starts_with($arg, '--auditor=')) $auditor = trim(substr($arg, 10));
    elseif (str_starts_with($arg, '--w3c-validator-url=')) $w3cUrl = trim(substr($arg, 20));
    elseif (str_starts_with($arg, '--wave-api-url=')) $waveUrl = trim(substr($arg, 15));
    elseif (str_starts_with($arg, '--wave-key-env=')) $waveKeyEnv = trim(substr($arg, 15));
    elseif ($arg === '--wave-alerts-reviewed') $alertsReviewed = true;
}

$fail = static function (string $message): never {
    fwrite(STDERR, "[N1.0-C5 Web Standards] FAIL — {$message}\n");
    exit(1);
};

$baseUrl = nexoraNormalizeEvidenceBaseUrl($baseUrl);
if ($baseUrl === null) $fail('A valid target --base-url=http(s)://... is required.');
if ($auditor === '' || $auditor === 'operator-name') $fail('A real --auditor=<name-or-id> is required.');
if (! filter_var($w3cUrl, FILTER_VALIDATE_URL)) $fail('Invalid W3C validator URL.');
if (! filter_var($waveUrl, FILTER_VALIDATE_URL)) $fail('Invalid WAVE API URL.');
if (! preg_match('/^[A-Z][A-Z0-9_]{2,80}$/', $waveKeyEnv)) $fail('Invalid WAVE key environment variable name.');

$waveKey = trim((string) getenv($waveKeyEnv));
if ($waveKey === '') {
    $fail("WAVE credential missing from environment [{$waveKeyEnv}]. Shared WAVE API requires a key; use a licensed stand-alone API endpoint if the target is private.");
}
if (! $alertsReviewed) {
    $fail('WAVE alerts require explicit human review. Re-run only after review with --wave-alerts-reviewed.');
}

$config = require $root.'/config/nexora-browser-certification.php';
$routes = (array) ($config['standards']['routes'] ?? ['/', '/login']);
if ($routes === []) $fail('No W3C/WAVE routes are configured.');

$requestJson = static function (string $url, int $timeout = 45): array {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\nUser-Agent: Nexora-C5-Standards/1.0\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents($url, false, $context);
    $status = 0;
    foreach ((array) ($http_response_header ?? []) as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#i', (string) $header, $m) === 1) $status = (int) $m[1];
    }
    if ($body === false) throw new RuntimeException('HTTP request failed.');
    try {
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        throw new RuntimeException('Remote checker returned invalid JSON: '.$e->getMessage());
    }
    if (! is_array($data)) throw new RuntimeException('Remote checker returned a non-object JSON payload.');
    return [$status, $data];
};

$source = nexoraComputeSourceAttestation($root);
$session = nexoraEnsureCertificationSession($root);
$version = (string) ((require $root.'/config/nexora.php')['version'] ?? 'unknown');
$rows = [];
$blocked = [];

foreach ($routes as $route) {
    $path = '/'.ltrim((string) $route, '/');
    if ($path === '//') $path = '/';
    $target = rtrim($baseUrl, '/').($path === '/' ? '/' : $path);

    $w3c = ['status' => 'fail', 'errors' => null, 'warnings' => null, 'messages' => []];
    try {
        [$httpStatus, $payload] = $requestJson(rtrim($w3cUrl, '?&').'?'.http_build_query(['out' => 'json', 'doc' => $target]));
        $messages = (array) ($payload['messages'] ?? []);
        $errors = 0;
        $warnings = 0;
        $summaries = [];
        foreach ($messages as $message) {
            if (! is_array($message)) continue;
            $type = strtolower((string) ($message['type'] ?? ''));
            $subType = strtolower((string) ($message['subType'] ?? ''));
            if ($type === 'error') $errors++;
            elseif ($type === 'info' && $subType === 'warning') $warnings++;
            if (count($summaries) < 25) {
                $summaries[] = [
                    'type' => $type,
                    'sub_type' => $subType !== '' ? $subType : null,
                    'message' => mb_substr(trim((string) ($message['message'] ?? '')), 0, 500),
                    'first_line' => isset($message['firstLine']) ? (int) $message['firstLine'] : null,
                    'last_line' => isset($message['lastLine']) ? (int) $message['lastLine'] : null,
                ];
            }
        }
        $w3c = [
            'status' => $httpStatus >= 200 && $httpStatus < 300 && $errors === 0 ? 'pass' : 'fail',
            'http_status' => $httpStatus,
            'errors' => $errors,
            'warnings' => $warnings,
            'messages' => $summaries,
        ];
        if ($w3c['status'] !== 'pass') $blocked[] = "W3C Nu validation failed for {$path} ({$errors} errors).";
    } catch (Throwable $e) {
        $w3c['request_error'] = mb_substr($e->getMessage(), 0, 300);
        $blocked[] = "W3C Nu checker could not evaluate {$path}.";
    }

    $wave = [
        'status' => 'fail',
        'errors' => null,
        'contrast_errors' => null,
        'alerts' => null,
        'alerts_reviewed' => true,
        'items' => [],
    ];
    try {
        [$httpStatus, $payload] = $requestJson(rtrim($waveUrl, '?&').'?'.http_build_query([
            'key' => $waveKey,
            'url' => $target,
            'format' => 'json',
            'reporttype' => 2,
        ]), 60);
        $success = (bool) ($payload['status']['success'] ?? false);
        $hostStatus = (int) ($payload['status']['httpstatuscode'] ?? 0);
        $errors = (int) ($payload['categories']['error']['count'] ?? -1);
        $contrast = (int) ($payload['categories']['contrast']['count'] ?? -1);
        $alerts = (int) ($payload['categories']['alert']['count'] ?? -1);
        $items = [];
        foreach (['error', 'contrast', 'alert'] as $category) {
            foreach ((array) ($payload['categories'][$category]['items'] ?? []) as $id => $item) {
                if (! is_array($item)) continue;
                $items[] = [
                    'category' => $category,
                    'id' => (string) $id,
                    'count' => (int) ($item['count'] ?? 0),
                    'description' => mb_substr(trim((string) ($item['description'] ?? '')), 0, 300),
                ];
            }
        }
        $gatePass = $httpStatus >= 200 && $httpStatus < 300 && $success && $hostStatus >= 200 && $hostStatus < 400 && $errors === 0 && $contrast === 0;
        $wave = [
            'status' => $gatePass ? 'reviewed' : 'fail',
            'api_http_status' => $httpStatus,
            'target_http_status' => $hostStatus,
            'errors' => $errors,
            'contrast_errors' => $contrast,
            'alerts' => $alerts,
            'alerts_reviewed' => true,
            'items' => $items,
            'wave_report_url' => isset($payload['statistics']['waveurl']) ? (string) $payload['statistics']['waveurl'] : null,
        ];
        if (! $gatePass) $blocked[] = "WAVE evaluation failed project gate for {$path} ({$errors} errors, {$contrast} contrast errors).";
    } catch (Throwable $e) {
        $wave['request_error'] = mb_substr($e->getMessage(), 0, 300);
        $blocked[] = "WAVE could not evaluate {$path}.";
    }

    $rows[] = ['path' => $path, 'url' => $target, 'w3c' => $w3c, 'wave' => $wave];
}

$status = $blocked === [] ? 'pass' : 'blocked';
$evidence = [
    'schema' => 1,
    'scope' => 'W3C Nu + WAVE accessibility evaluation',
    'status' => $status,
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'certification_session_id' => (string) $session['session_id'],
    'base_url' => $baseUrl,
    'auditor' => $auditor,
    'checked_at' => gmdate(DATE_ATOM),
    'w3c' => [
        'tool' => 'Nu Html Checker',
        'validator_url' => $w3cUrl,
        'project_gate' => 'zero HTML conformance errors on every required route',
    ],
    'wave' => [
        'tool' => 'WAVE',
        'api_url' => $waveUrl,
        'report_type' => 2,
        'project_gate' => 'zero WAVE errors and zero contrast errors; all alerts human-reviewed',
        'not_an_accessibility_approval' => true,
    ],
    'routes' => $rows,
    'blockers' => array_values(array_unique($blocked)),
];

$dest = $root.'/storage/app/nexora/certification';
$writer = new App\Nexora\Foundation\Filesystem\AtomicFileWriter();
$writer->ensureDirectory($dest);
$writer->write($dest.'/web-standards-evidence.json', json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, 0755, 0640);

if ($status !== 'pass') {
    fwrite(STDERR, "[N1.0-C5 Web Standards] BLOCKED\n - ".implode("\n - ", array_values(array_unique($blocked)))."\n");
    exit(1);
}

fwrite(STDOUT, "[N1.0-C5 Web Standards] PASS — project gate satisfied: W3C Nu has zero errors; WAVE has zero errors/contrast errors and alerts were human-reviewed. This is not a WAVE accessibility approval.\n");
