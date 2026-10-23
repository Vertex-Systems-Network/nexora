<?php

declare(strict_types=1);

/**
 * AI-GOV-AUTOMATION-100: machine-check the .ai control plane so CI rejects
 * missing, unplanned or inconsistent stage/registry/state records.
 *
 * Usage: php scripts/ai-control-plane-verify.php [repository-root]
 */
function nexora_ai_control_plane_errors(string $root): array
{
    $errors = [];
    $ai = $root.'/.ai';

    $readJson = static function (string $path) use (&$errors): ?array {
        if (! is_file($path)) {
            $errors[] = "Missing {$path}.";

            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            $errors[] = "Invalid JSON in {$path}.";

            return null;
        }

        return $data;
    };

    $stagesPath = $ai.'/roadmap/stages.md';
    $stages = [];
    if (is_file($stagesPath)) {
        preg_match_all('/^\|\s*\d+\s*\|\s*`([A-Z][A-Z0-9-]*-\d{3})`/m', (string) file_get_contents($stagesPath), $matches);
        $stages = array_fill_keys($matches[1], true);
    }
    if ($stages === []) {
        $errors[] = "No canonical stages found in {$stagesPath}.";
    }

    $units = [];
    foreach (glob($ai.'/registry/*.json') ?: [] as $registryPath) {
        $registry = $readJson($registryPath);
        if (! is_array($registry['units'] ?? null)) {
            $errors[] = "Registry {$registryPath} has no units array.";

            continue;
        }
        foreach ($registry['units'] as $index => $unit) {
            $id = is_array($unit) ? ($unit['id'] ?? null) : null;
            if (! is_string($id) || $id === '') {
                $errors[] = "Registry {$registryPath} unit #{$index} has no id.";

                continue;
            }
            if (isset($units[$id])) {
                $errors[] = "Duplicate development unit id {$id}.";
            }
            foreach (['status', 'parent_stage_id'] as $field) {
                if (! is_string($unit[$field] ?? null) || $unit[$field] === '') {
                    $errors[] = "Unit {$id} is missing {$field}.";
                }
            }
            $units[$id] = $unit;
        }
    }
    if ($units === []) {
        $errors[] = 'No registered development units found.';
    }

    // RESEARCH-DISCOVERY-100: machine-readable ResearchBrief rules.
    $briefSections = ['Problem', 'Users & VOC evidence', 'Baseline', 'Alternatives', 'CTQs', 'Confidence', 'Sources'];
    foreach ($units as $id => $unit) {
        $research = $unit['research'] ?? null;
        $risk = $unit['security']['risk'] ?? null;
        if ($research !== null && ! is_array($research)) {
            $errors[] = "Unit {$id} research must be an object.";
            $research = null;
        }
        if (is_array($research)) {
            $brief = $research['brief'] ?? null;
            if ($brief !== null) {
                $briefPath = is_string($brief) ? $root.'/'.ltrim($brief, '/') : '';
                $real = $briefPath !== '' ? realpath($briefPath) : false;
                $researchDir = realpath($ai.'/research');
                if (! is_string($brief) || $real === false || $researchDir === false || ! is_file($real)
                    || ! str_ends_with($real, '.md') || ! str_starts_with($real, $researchDir.DIRECTORY_SEPARATOR)) {
                    $errors[] = "Unit {$id} research.brief must be an existing .md file under .ai/research/.";
                } else {
                    // Headings inside fenced code blocks do not count as sections.
                    $text = (string) preg_replace('/^```.*?^```/ms', '', (string) file_get_contents($real));
                    foreach ($briefSections as $section) {
                        if (! preg_match('/^## '.preg_quote($section, '/').'\s*$/m', $text)) {
                            $errors[] = "Unit {$id} ResearchBrief {$brief} is missing section \"## {$section}\".";
                        }
                    }
                }
            }
            $confidence = $research['confidence'] ?? 'unknown';
            if (! in_array($confidence, ['unknown', 'low', 'medium', 'high'], true)) {
                $errors[] = "Unit {$id} research.confidence must be unknown, low, medium or high.";
            } elseif ($confidence !== 'unknown' && (! is_array($research['voc_evidence'] ?? null) || ! array_is_list($research['voc_evidence'])
                || array_filter($research['voc_evidence'], static fn ($v): bool => is_string($v) && trim($v) !== '') === [])) {
                // Anti-fabrication: confidence must be backed by cited evidence.
                $errors[] = "Unit {$id} claims research confidence {$confidence} without research.voc_evidence.";
            }
            if (array_key_exists('baseline', $research) && (! is_string($research['baseline']) || trim($research['baseline']) === '')) {
                $errors[] = "Unit {$id} research.baseline is empty; write UNKNOWN when there is no measurement.";
            }
        }
        // Gated at ACTIVE: existing SOURCE_DONE units predate this rule and are not retro-gated.
        if (in_array($risk, ['high', 'critical'], true) && ($unit['status'] ?? null) === 'ACTIVE') {
            $hasBrief = is_array($research) && is_string($research['brief'] ?? null) && $research['brief'] !== '';
            $exception = is_array($research) ? ($research['exception'] ?? null) : null;
            if ($exception !== null) {
                // A research exception is a waiver: scoped reason, named approver, expiry (AGENTS.md "Waivers").
                $valid = is_array($exception)
                    && is_string($exception['reason'] ?? null) && trim($exception['reason']) !== ''
                    && is_string($exception['approver'] ?? null) && trim($exception['approver']) !== ''
                    && is_string($exception['expires'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $exception['expires']) === 1
                    && $exception['expires'] >= gmdate('Y-m-d');
                if (! $valid) {
                    $errors[] = "Unit {$id} research.exception must be {reason, approver, expires: YYYY-MM-DD} and not expired.";
                }
            } elseif (! $hasBrief) {
                $errors[] = "Unit {$id} is ACTIVE with {$risk} risk but has no research.brief or research.exception.";
            }
        }
    }

    // QUALITY-GOVERNANCE-100: quality evidence references must resolve; high/critical work declares its method.
    $markdownUnder = static function (mixed $path, string $dir) use ($root): bool {
        if (! is_string($path) || $path === '') {
            return false;
        }
        $real = realpath($root.'/'.ltrim($path, '/'));
        $base = realpath($root.'/'.$dir);

        return $real !== false && $base !== false && is_file($real) && str_ends_with($real, '.md')
            && str_starts_with($real, $base.DIRECTORY_SEPARATOR);
    };
    $fmeaSections = ['Context', 'Failure-mode table', 'Control evidence'];
    foreach ($units as $id => $unit) {
        $status = $unit['status'] ?? null;
        $risk = $unit['security']['risk'] ?? null;
        $security = is_array($unit['security'] ?? null) ? $unit['security'] : [];
        $quality = $unit['quality'] ?? null;
        if ($quality !== null && ! is_array($quality)) {
            $errors[] = "Unit {$id} quality must be an object.";
            $quality = null;
        }

        if (array_key_exists('threat_model', $security) && ! $markdownUnder($security['threat_model'], '.ai/security/threat-models')) {
            $errors[] = "Unit {$id} security.threat_model must be an existing .md file under .ai/security/threat-models/.";
        }
        if ($status === 'ACTIVE' && ($security['threat_model_required'] ?? false) === true && ! array_key_exists('threat_model', $security)) {
            $errors[] = "Unit {$id} is ACTIVE and requires a threat model but has no security.threat_model.";
        }

        if (is_array($quality) && array_key_exists('method', $quality) && ! in_array($quality['method'], ['lightweight', 'DMADV', 'DMAIC'], true)) {
            $errors[] = "Unit {$id} quality.method must be lightweight, DMADV or DMAIC.";
        }
        $highRiskActive = $status === 'ACTIVE' && in_array($risk, ['high', 'critical'], true);
        if ($highRiskActive && ! (is_array($quality) && array_key_exists('method', $quality))) {
            $errors[] = "Unit {$id} is ACTIVE with {$risk} risk but declares no quality.method.";
        }
        if ($status === 'ACTIVE' && $risk === 'critical' && ! (is_array($quality) && is_bool($quality['fmea_required'] ?? null))) {
            $errors[] = "Unit {$id} is ACTIVE with critical risk but does not state quality.fmea_required (true/false).";
        }
        $fmeaRequired = is_array($quality) && ($quality['fmea_required'] ?? false) === true;
        if (is_array($quality) && ($fmeaRequired || array_key_exists('fmea_ref', $quality))) {
            if (! $markdownUnder($quality['fmea_ref'] ?? null, '.ai/quality/fmea')) {
                $errors[] = "Unit {$id} quality.fmea_ref is not an existing .md file under .ai/quality/fmea/.";
            } else {
                $fmea = (string) preg_replace('/^```.*?^```/ms', '', (string) file_get_contents($root.'/'.ltrim($quality['fmea_ref'], '/')));
                foreach ($fmeaSections as $section) {
                    if (! preg_match('/^## '.preg_quote($section, '/').'\s*$/m', $fmea)) {
                        $errors[] = "Unit {$id} FMEA {$quality['fmea_ref']} is missing section \"## {$section}\".";
                    }
                }
            }
        }
        if (is_array($quality) && array_key_exists('control_plan', $quality)) {
            $plan = $quality['control_plan'];
            $real = is_string($plan) && $plan !== '' ? realpath($root.'/'.ltrim($plan, '/')) : false;
            if ($real === false || ! is_file($real) || ! str_starts_with($real, (string) realpath($root).DIRECTORY_SEPARATOR)) {
                $errors[] = "Unit {$id} quality.control_plan must be an existing repository file.";
            }
        }
    }

    foreach ($units as $id => $unit) {
        $parent = $unit['parent_stage_id'] ?? null;
        if (is_string($parent) && $parent !== '' && ! isset($stages[$parent])) {
            $errors[] = "Unit {$id} references unknown stage {$parent}.";
        }
        foreach ((array) ($unit['depends_on'] ?? []) as $dependency) {
            if (! isset($stages[$dependency]) && ! isset($units[$dependency])) {
                $errors[] = "Unit {$id} depends on unknown stage/unit {$dependency}.";
            }
        }
    }

    $state = $readJson($ai.'/state.json');
    if ($state !== null) {
        $current = $state['current_stage']['id'] ?? null;
        if (! is_string($current) || ! isset($stages[$current])) {
            $errors[] = 'state.json current_stage.id is not a canonical stage.';
        }
        foreach ((array) ($state['active_development_units'] ?? []) as $active) {
            if (! isset($units[$active])) {
                $errors[] = "state.json active unit {$active} is not registered.";
            } elseif (($units[$active]['parent_stage_id'] ?? null) !== $current) {
                $errors[] = "state.json active unit {$active} does not belong to current stage {$current}.";
            }
        }
        foreach ((array) ($state['completed_control_stages'] ?? []) as $completed) {
            $waiver = is_array($completed) ? ($completed['waiver'] ?? null) : null;
            if (is_string($waiver) && ! is_file($ai.'/waivers/'.$waiver.'.json')) {
                $errors[] = "Completed stage {$completed['id']} cites missing waiver {$waiver}.";
            }
        }

        $resumePath = $ai.'/resume/CURRENT-STATE.yaml';
        if (is_file($resumePath) && preg_match('/^current_stage:\R\s+id:\s*(\S+)\R\s+unit:\s*(\S+)/m', (string) file_get_contents($resumePath), $resume)) {
            if ($resume[1] !== $current) {
                $errors[] = "CURRENT-STATE.yaml stage {$resume[1]} disagrees with state.json {$current}.";
            }
            if (! in_array($resume[2], (array) ($state['active_development_units'] ?? []), true)) {
                $errors[] = "CURRENT-STATE.yaml unit {$resume[2]} is not an active unit in state.json.";
            }
        } else {
            $errors[] = 'CURRENT-STATE.yaml has no readable current_stage id/unit.';
        }

        // The newest (first) section of the active plan must cover the current stage and its units.
        $planPath = $ai.'/plans/active.md';
        $plan = is_file($planPath) ? (string) file_get_contents($planPath) : '';
        $latest = preg_split('/^(?=# )/m', ltrim($plan), 2, PREG_SPLIT_NO_EMPTY)[0] ?? '';
        if ($latest === '') {
            $errors[] = 'plans/active.md is missing or empty.';
        } else {
            foreach (array_merge([$current], (array) ($state['active_development_units'] ?? [])) as $reference) {
                if (is_string($reference) && ! str_contains($latest, $reference)) {
                    $errors[] = "Latest plans/active.md section does not reference {$reference}.";
                }
            }
        }
    }

    if (trim((string) @file_get_contents($ai.'/quality/definition-of-done.md')) === '') {
        $errors[] = 'quality/definition-of-done.md is missing or empty.';
    }

    return $errors;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $errors = nexora_ai_control_plane_errors($argv[1] ?? dirname(__DIR__));
    if ($errors !== []) {
        fwrite(STDERR, "AI control-plane verification failed:\n- ".implode("\n- ", $errors)."\n");
        exit(1);
    }
    echo "AI control-plane verification passed.\n";
}
