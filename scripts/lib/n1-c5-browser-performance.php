<?php

declare(strict_types=1);

require_once __DIR__.'/final-evidence.php';
require_once __DIR__.'/source-attestation.php';

/** @return list<string> */
function nexoraValidateC5WebVitalsEvidence(string $root, array $data): array
{
    $platform = require $root.'/config/nexora.php';
    $config = require $root.'/config/nexora-browser-certification.php';
    $errors = [];
    if (($data['schema'] ?? null) !== 1) $errors[] = 'web-vitals evidence schema must be 1';
    if (($data['platform_version'] ?? null) !== ($platform['version'] ?? null)) $errors[] = 'web-vitals platform_version mismatch';
    if (trim((string)($data['auditor'] ?? '')) === '' || ($data['auditor'] ?? '') === 'operator-name') $errors[] = 'web-vitals evidence requires a real auditor';
    if (!nexoraEvidenceTimestampFresh($data['completed_at'] ?? null, nexoraEvidenceMaxAgeHours($root,'web_vitals',24))) $errors[] = 'web-vitals completed_at must be recent';
    $thresholds = (array)($config['web_vitals']['thresholds'] ?? []);
    $requiredRoutes = (array)($config['web_vitals']['routes'] ?? []);
    $minimumRuns = max(1, (int)($config['web_vitals']['minimum_runs_per_route'] ?? 3));
    $seen = [];
    foreach ((array)($data['routes'] ?? []) as $index => $row) {
        if (!is_array($row)) { $errors[] = "web-vitals route {$index} must be an object"; continue; }
        $path = trim((string)($row['path'] ?? ''));
        if ($path === '') { $errors[] = "web-vitals route {$index} requires path"; continue; }
        $seen[$path] = true;
        if (($row['status'] ?? null) !== 'pass') $errors[] = "web-vitals route [{$path}] status must be pass";
        if ((int)($row['runs'] ?? 0) < $minimumRuns) $errors[] = "web-vitals route [{$path}] requires at least {$minimumRuns} runs";
        foreach (['lcp_ms','inp_ms','cls','ttfb_ms'] as $metric) {
            if (!is_numeric($row[$metric] ?? null)) { $errors[] = "web-vitals route [{$path}] metric [{$metric}] must be numeric"; continue; }
            $value = (float)$row[$metric];
            $max = (float)($thresholds[$metric] ?? INF);
            if ($value < 0) $errors[] = "web-vitals route [{$path}] metric [{$metric}] cannot be negative";
            if ($value > $max) $errors[] = "web-vitals route [{$path}] {$metric} {$value} exceeds C5 ceiling {$max}";
        }
        if (trim((string)($row['browser'] ?? '')) === '') $errors[] = "web-vitals route [{$path}] requires observed browser";
        if (trim((string)($row['profile'] ?? '')) === '') $errors[] = "web-vitals route [{$path}] requires observed profile";
    }
    foreach ($requiredRoutes as $path) if (!isset($seen[(string)$path])) $errors[] = "web-vitals missing required route [{$path}]";
    $errors = array_merge($errors, nexoraEvidenceBaseUrlErrors($root,$data,'web-vitals evidence'));
    return array_merge($errors, nexoraValidateEvidenceSourceBinding($root, $data, 'web-vitals evidence'), nexoraValidateEvidenceSessionBinding($root,$data,'web-vitals evidence'));
}

/** @return list<string> */
function nexoraValidateC5BrowserEvidence(string $root, array $data): array
{
    $config = require $root.'/config/nexora-browser-certification.php';
    $platform = require $root.'/config/nexora.php';
    $errors = [];
    if (($data['schema'] ?? null) !== 2) $errors[] = 'C5 browser evidence schema must be 2';
    if (($data['platform_version'] ?? null) !== ($platform['version'] ?? null)) $errors[] = 'browser evidence version mismatch';
    if (trim((string)($data['auditor'] ?? '')) === '' || ($data['auditor'] ?? '') === 'operator-name') $errors[] = 'browser evidence requires a real auditor';
    if (!nexoraEvidenceTimestampFresh($data['completed_at'] ?? null, nexoraEvidenceMaxAgeHours($root,'browser',72))) $errors[] = 'browser completed_at must be recent';
    $required = [];
    foreach ((array)$config['browsers'] as $browser) foreach ((array)$config['viewports'] as $viewport) foreach ((array)$config['directions'] as $direction) foreach ((array)$config['themes'] as $theme) {
        $required[$browser.'|'.$viewport['name'].'|'.$viewport['width'].'|'.$direction.'|'.$theme] = false;
    }
    foreach ((array)($data['matrix'] ?? []) as $row) {
        if (!is_array($row)) continue;
        $key = strtolower((string)($row['browser'] ?? '')).'|'.($row['viewport'] ?? '').'|'.($row['width'] ?? '').'|'.($row['direction'] ?? '').'|'.($row['theme'] ?? '');
        if (array_key_exists($key, $required) && ($row['status'] ?? null) === 'pass') $required[$key] = true;
    }
    foreach ($required as $key => $pass) if (!$pass) $errors[] = 'browser matrix missing PASS '.$key;
    foreach ((array)$config['browsers'] as $browser) {
        $entry = null;
        foreach ((array)($data['environments'] ?? []) as $row) if (is_array($row) && strtolower((string)($row['browser'] ?? '')) === $browser) { $entry = $row; break; }
        if (!is_array($entry)) { $errors[] = "browser environment missing [{$browser}]"; continue; }
        if (trim((string)($entry['version'] ?? '')) === '') $errors[] = "browser environment [{$browser}] requires version";
        if (trim((string)($entry['os'] ?? '')) === '') $errors[] = "browser environment [{$browser}] requires OS";
    }
    foreach ((array)$config['checks'] as $check) if (($data['checks'][$check] ?? null) !== 'pass') $errors[] = "browser check [{$check}] must be pass";
    $at = (array)($data['assistive_technology'] ?? []);
    if (($at['status'] ?? null) !== 'pass') $errors[] = 'assistive_technology status must be pass';
    if (trim((string)($at['name'] ?? '')) === '' || ($at['name'] ?? '') === 'screen-reader-name') $errors[] = 'assistive_technology requires a real screen reader/tool';
    if (trim((string)($at['browser'] ?? '')) === '') $errors[] = 'assistive_technology requires observed browser';
    $errors = array_merge($errors, nexoraEvidenceBaseUrlErrors($root,$data,'browser evidence'));
    return array_merge($errors, nexoraValidateEvidenceSourceBinding($root, $data, 'browser evidence'), nexoraValidateEvidenceSessionBinding($root,$data,'browser evidence'));
}
