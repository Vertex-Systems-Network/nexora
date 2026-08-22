<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/n1-certification-session.php';
require_once $root.'/scripts/lib/target-evidence-intake.php';
require_once $root.'/scripts/lib/final-evidence.php';

$input = '';
foreach ($argv as $arg) if (str_starts_with($arg, '--input=')) $input = trim(substr($arg, 8));
$path = $input !== '' ? $input : $root.'/storage/app/nexora/certification/web-standards-evidence.json';

$fail = static function (string $message): never {
    fwrite(STDERR, "[N1.0-C5 Web Standards Evidence] FAIL — {$message}\n");
    exit(1);
};

if (! is_file($path) || filesize($path) > 8 * 1024 * 1024) $fail('Evidence missing or oversized: '.$path);
try {
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    $fail('Invalid JSON: '.$e->getMessage());
}
if (! is_array($data)) $fail('Evidence must be an object.');

$errors = [];
$platform = require $root.'/config/nexora.php';
$config = require $root.'/config/nexora-browser-certification.php';
$requiredRoutes = array_values((array) ($config['standards']['routes'] ?? ['/', '/login']));

if (($data['schema'] ?? null) !== 1) $errors[] = 'web-standards evidence schema must be 1';
if (($data['status'] ?? null) !== 'pass') $errors[] = 'web-standards status must be pass';
if (($data['platform_version'] ?? null) !== ($platform['version'] ?? null)) $errors[] = 'web-standards platform_version mismatch';
if (trim((string) ($data['auditor'] ?? '')) === '' || ($data['auditor'] ?? '') === 'operator-name') $errors[] = 'web-standards requires a real auditor';
if (! nexoraEvidenceTimestampFresh($data['checked_at'] ?? null, nexoraEvidenceMaxAgeHours($root, 'browser', 72))) $errors[] = 'web-standards checked_at must be recent';
if (($data['wave']['not_an_accessibility_approval'] ?? null) !== true) $errors[] = 'WAVE evidence must explicitly state it is not an accessibility approval';

$base = nexoraNormalizeEvidenceBaseUrl($data['base_url'] ?? null);
if ($base === null) $errors[] = 'web-standards base_url must be valid';

$seen = [];
foreach ((array) ($data['routes'] ?? []) as $index => $row) {
    if (! is_array($row)) {
        $errors[] = "web-standards route {$index} must be an object";
        continue;
    }
    $route = '/'.ltrim((string) ($row['path'] ?? ''), '/');
    if ($route === '//') $route = '/';
    if ($route === '/') $seen['/'] = true; else $seen[$route] = true;

    $w3c = (array) ($row['w3c'] ?? []);
    if (($w3c['status'] ?? null) !== 'pass') $errors[] = "W3C route [{$route}] status must be pass";
    if (($w3c['errors'] ?? null) !== 0) $errors[] = "W3C route [{$route}] must have zero conformance errors";
    if (! is_int($w3c['warnings'] ?? null) && ! is_numeric($w3c['warnings'] ?? null)) $errors[] = "W3C route [{$route}] warnings must be recorded";

    $css = (array) ($row['css'] ?? []);
    if (($css['status'] ?? null) !== 'pass') $errors[] = "W3C CSS route [{$route}] status must be pass";
    if (($css['validity'] ?? null) !== true) $errors[] = "W3C CSS route [{$route}] validity must be true";
    if (($css['errors'] ?? null) !== 0) $errors[] = "W3C CSS route [{$route}] must have zero validation errors";
    if (! is_int($css['warnings'] ?? null) && ! is_numeric($css['warnings'] ?? null)) $errors[] = "W3C CSS route [{$route}] warnings must be recorded";

    $wave = (array) ($row['wave'] ?? []);
    if (($wave['status'] ?? null) !== 'reviewed') $errors[] = "WAVE route [{$route}] must be reviewed";
    if (($wave['errors'] ?? null) !== 0) $errors[] = "WAVE route [{$route}] must have zero errors";
    if (($wave['contrast_errors'] ?? null) !== 0) $errors[] = "WAVE route [{$route}] must have zero contrast errors";
    if (($wave['alerts_reviewed'] ?? null) !== true) $errors[] = "WAVE route [{$route}] alerts must be human-reviewed";
    if (! is_int($wave['alerts'] ?? null) && ! is_numeric($wave['alerts'] ?? null)) $errors[] = "WAVE route [{$route}] alert count must be recorded";

    if ($base !== null) {
        $url = trim((string) ($row['url'] ?? ''));
        if ($url === '' || ! str_starts_with(rtrim($url, '/').'/', rtrim($base, '/').'/')) $errors[] = "web-standards route [{$route}] URL must belong to certified base URL";
    }
}
foreach ($requiredRoutes as $route) {
    $normalized = '/'.ltrim((string) $route, '/');
    if ($normalized === '//') $normalized = '/';
    if (! isset($seen[$normalized])) $errors[] = "web-standards missing required route [{$normalized}]";
}

$errors = array_merge($errors, nexoraValidateEvidenceSourceBinding($root, $data, 'web-standards evidence'));
$errors = array_merge($errors, nexoraValidateEvidenceSessionBinding($root, $data, 'web-standards evidence'));
if ($errors !== []) $fail(implode('; ', array_values(array_unique($errors))));

fwrite(STDOUT, "[N1.0-C5 Web Standards Evidence] PASS — exact-source W3C Nu + W3C CSS zero-error evidence and WAVE zero-error/zero-contrast human-reviewed evidence are sealed. WAVE is not treated as an accessibility approval.\n");
