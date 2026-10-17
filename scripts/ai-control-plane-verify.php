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
