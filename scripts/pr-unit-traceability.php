<?php

declare(strict_types=1);

/**
 * AI-GOV-AUTOMATION-100 / SYS-AI-GOVERNANCE-AUTOMATION: PR-to-unit traceability.
 *
 * A pull request must name at least one registered development unit or canonical stage ID
 * in its title or body, so unregistered work cannot merge unnoticed. Dependency-bot PRs are exempt.
 *
 * CLI (CI): reads PR_TITLE, PR_BODY and PR_AUTHOR from the environment.
 * Usage: php scripts/pr-unit-traceability.php [repository-root]
 */

const NEXORA_TRACEABILITY_EXEMPT_AUTHORS = ['dependabot[bot]'];

/** @return array<string,true> registered unit ids and canonical stage ids */
function nexora_traceability_known_ids(string $root): array
{
    $known = [];
    $stages = (string) @file_get_contents($root.'/.ai/roadmap/stages.md');
    preg_match_all('/^\|\s*\d+\s*\|\s*`([A-Z][A-Z0-9-]*-\d{3})`/m', $stages, $matches);
    foreach ($matches[1] as $stage) {
        $known[$stage] = true;
    }
    foreach (glob($root.'/.ai/registry/*.json') ?: [] as $registryPath) {
        $registry = json_decode((string) file_get_contents($registryPath), true);
        foreach ((array) ($registry['units'] ?? []) as $unit) {
            if (is_array($unit) && is_string($unit['id'] ?? null) && $unit['id'] !== '') {
                $known[$unit['id']] = true;
            }
        }
    }

    return $known;
}

/**
 * @return array{ok:bool,exempt:bool,referenced:list<string>,errors:list<string>}
 */
function nexora_pr_traceability(string $root, string $title, string $body, string $author): array
{
    if (in_array($author, NEXORA_TRACEABILITY_EXEMPT_AUTHORS, true)) {
        return ['ok' => true, 'exempt' => true, 'referenced' => [], 'errors' => []];
    }

    $known = nexora_traceability_known_ids($root);
    if ($known === []) {
        return ['ok' => false, 'exempt' => false, 'referenced' => [], 'errors' => ['No registered units or canonical stages found; cannot verify traceability.']];
    }

    // Whole-token matches only: "SEC-BASELINE" must not match inside "SEC-BASELINE-X".
    preg_match_all('/(?<![A-Za-z0-9-])[A-Z][A-Z0-9]*(?:-[A-Z0-9]+)+(?![A-Za-z0-9-])/', $title."\n".$body, $tokens);
    $referenced = array_values(array_unique(array_filter($tokens[0], static fn (string $token): bool => isset($known[$token]))));
    sort($referenced);

    if ($referenced === []) {
        return ['ok' => false, 'exempt' => false, 'referenced' => [], 'errors' => [
            'The PR title or body names no registered development unit or canonical stage ID. '
            .'Register the work first (.ai/registry/*.json, .ai/governance/development-intake.md), then reference its ID, e.g. "SECURITY-BASELINE-200: …". IDs are case-sensitive.',
        ]];
    }

    return ['ok' => true, 'exempt' => false, 'referenced' => $referenced, 'errors' => []];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $result = nexora_pr_traceability(
        $argv[1] ?? dirname(__DIR__),
        (string) getenv('PR_TITLE'),
        (string) getenv('PR_BODY'),
        (string) getenv('PR_AUTHOR'),
    );
    if (! $result['ok']) {
        fwrite(STDERR, "PR-to-unit traceability failed:\n- ".implode("\n- ", $result['errors'])."\n");
        exit(1);
    }
    echo $result['exempt']
        ? "PR-to-unit traceability: exempt dependency-bot PR.\n"
        : 'PR-to-unit traceability passed: '.implode(', ', $result['referenced']).".\n";
}
