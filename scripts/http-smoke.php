<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
$baseArg = '';
$requireBase = in_array('--require-base-url', $argv, true);
foreach ($argv as $arg) if (str_starts_with($arg, '--base-url=')) $baseArg = trim(substr($arg, 11));
$base = rtrim((string) ($baseArg !== '' ? $baseArg : (getenv('NEXORA_CERT_BASE_URL') ?: '')), '/');
if ($base === '') {
    if ($requireBase) { fwrite(STDERR, "[Nexora HTTP Smoke] FAIL — target base URL is required. Use --base-url=https://target or NEXORA_CERT_BASE_URL.\n"); exit(2); }
    fwrite(STDOUT, "[Nexora HTTP Smoke] SKIP — NEXORA_CERT_BASE_URL is not configured.\n");
    exit(0);
}
if (!preg_match('#^https?://#i', $base)) { fwrite(STDERR, "[Nexora HTTP Smoke] FAIL — base URL must start with http:// or https://.\n"); exit(2); }
$performance = require $root.'/config/nexora-performance.php';
$maxMs = max(100, (int) ($performance['http']['smoke_max_ms'] ?? 2000));
$isHttps = str_starts_with(strtolower($base), 'https://');
$paths = [
    '/' => [200, 302, 303],
    '/login' => [200, 302, 303],
    '/health/live' => [200],
    '/health/ready' => [200, 503],
];
$failures = [];
$observed = [];

$headerMap = static function (array $headers): array {
    $map = [];
    foreach ($headers as $line) {
        if (! is_string($line) || ! str_contains($line, ':')) continue;
        [$name,$value] = explode(':',$line,2);
        $name = strtolower(trim($name));
        if ($name === '') continue;
        $map[$name][] = trim($value);
    }
    return $map;
};

foreach ($paths as $path => $allowed) {
    $url = $base.$path;
    $status = 0;
    $body = '';
    $headers = [];
    $durationMs = 0.0;
    $started = microtime(true);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/json;q=0.9,*/*;q=0.8'],
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => '',
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $failures[] = "{$path}: ".curl_error($ch);
            curl_close($ch);
            continue;
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $durationMs = round(((float)curl_getinfo($ch,CURLINFO_TOTAL_TIME))*1000,2);
        $headers = preg_split('/\r\n|\n|\r/', substr((string) $response, 0, $headerSize)) ?: [];
        $body = substr((string) $response, $headerSize);
        curl_close($ch);
    } else {
        $context = stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true,'timeout'=>15,'header'=>"Accept: text/html,application/json;q=0.9,*/*;q=0.8\r\nAccept-Encoding: gzip, deflate\r\n"]]);
        $response = @file_get_contents($url, false, $context);
        $durationMs = round((microtime(true)-$started)*1000,2);
        $body = is_string($response) ? $response : '';
        $headers = $http_response_header ?? [];
        if (isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $match)) $status=(int)$match[1];
    }

    $map = $headerMap($headers);
    if (! in_array($status, $allowed, true)) $failures[] = "{$path}: unexpected HTTP {$status}";
    if ($durationMs > $maxMs) $failures[] = "{$path}: {$durationMs}ms exceeds RC smoke ceiling {$maxMs}ms";
    foreach (['vendor/laravel/framework/src/', 'Whoops, looks like something went wrong', 'Illuminate\\Foundation\\'] as $leak) {
        if ($body !== '' && stripos($body, $leak) !== false) $failures[] = "{$path}: raw framework exception content leaked";
    }
    foreach ([
        'x-request-id',
        'x-content-type-options',
        'referrer-policy',
        'x-frame-options',
        'permissions-policy',
        'cache-control',
    ] as $requiredHeader) {
        if (! isset($map[$requiredHeader][0]) || trim((string)$map[$requiredHeader][0]) === '') $failures[] = "{$path}: missing {$requiredHeader} header";
    }
    if (($map['x-content-type-options'][0] ?? '') !== 'nosniff') $failures[] = "{$path}: X-Content-Type-Options must be nosniff";
    if ($isHttps && (bool)($performance['headers']['hsts'] ?? true) && ! isset($map['strict-transport-security'][0])) $failures[] = "{$path}: HTTPS response missing Strict-Transport-Security";
    if (in_array($path,['/login','/health/live','/health/ready'],true)) {
        $cache = strtolower((string)($map['cache-control'][0] ?? ''));
        if (! str_contains($cache,'no-store')) $failures[] = "{$path}: sensitive/health response must be no-store";
    }

    $observed[] = [
        'path'=>$path,
        'status'=>$status,
        'duration_ms'=>$durationMs,
        'request_id'=>$map['x-request-id'][0] ?? null,
        'cache_control'=>$map['cache-control'][0] ?? null,
        'content_encoding'=>$map['content-encoding'][0] ?? null,
    ];
    fwrite(STDOUT, "[Nexora HTTP Smoke] {$path} -> {$status} {$durationMs}ms".(isset($map['x-request-id'][0]) ? " request={$map['x-request-id'][0]}" : '')."\n");
}

$reportDir=$root.'/storage/app/nexora/certification';
if (!is_dir($reportDir)) @mkdir($reportDir,0775,true);
if (is_dir($reportDir)) {
    $platform=require $root.'/config/nexora.php';
    file_put_contents($reportDir.'/http-performance.json',json_encode([
        'schema'=>1,
        'status'=>$failures===[]?'pass':'fail',
        'platform_version'=>$platform['version']??null,
        'source_tree_sha256'=>nexoraComputeSourceAttestation($root)['tree_sha256'],
        'base_url'=>$base,
        'max_ms'=>$maxMs,
        'observed'=>$observed,
        'failures'=>$failures,
        'checked_at'=>gmdate(DATE_ATOM),
    ],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
}

if ($failures !== []) {
    fwrite(STDERR, "[Nexora HTTP Smoke] FAILED\n - ".implode("\n - ",$failures)."\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora HTTP Smoke] PASS — headers/cache/security + {$maxMs}ms route ceiling.\n");
