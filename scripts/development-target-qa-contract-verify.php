<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required development target QA source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read development target QA source file: {$relative}";
        return '';
    }
    return $contents;
};

$readiness = $read('scripts/development-readiness.php');
$matrix = $read('scripts/database-target-matrix.php');
$agents = $read('AGENTS.md');
$package = $read('package.json');
$docs = $read('docs/DEVELOPMENT_TARGET_QA.md');

foreach ([
    "in_array('--tests', \$argv, true)" => 'explicit full-PHPUnit opt-in',
    "in_array('--evidence', \$argv, true)" => 'durable evidence opt-in',
    "[PHP_BINARY, 'artisan', 'test', '--colors=never']" => 'real Laravel/PHPUnit execution',
    "nexoraComputeSourceAttestation(\$root)" => 'source identity in target evidence',
    "storage/app/nexora/qa/development-readiness.json" => 'canonical development QA evidence path',
    "'scope' => 'development-target-functional-qa'" => 'evidence scope identity',
    "'source_tree_sha256'" => 'source tree digest in evidence',
    "'source_file_count'" => 'source file count in evidence',
    "'tests_requested'" => 'evidence records PHPUnit intent',
    "LOCK_EX" => 'atomic evidence write',
    "does not promote dependency locks or grant final C1-C6 release certification" => 'development-vs-release boundary',
] as $needle => $label) {
    if ($readiness !== '' && ! str_contains($readiness, $needle)) {
        $errors[] = "Development readiness evidence contract missing: {$label}.";
    }
}

if ($readiness !== '' && preg_match("/'detail'\s*=>\s*\$check\['detail'\]/", $readiness) === 1) {
    $errors[] = 'Durable development QA evidence must not serialize raw command detail output.';
}

foreach ([
    "--evidence" => 'database matrix evidence option',
    "storage/app/nexora/qa/database-target-matrix.json" => 'database matrix evidence path',
    "Only empty databases/files whose names match nexora_matrix_* are accepted" => 'database matrix destructive-scope guard',
] as $needle => $label) {
    if ($matrix !== '' && ! str_contains($matrix, $needle)) {
        $errors[] = "Database target matrix evidence contract missing: {$label}.";
    }
}

foreach ([
    'mark it Ready for review and merge it without waiting for a separate merge confirmation' => 'automatic final PR merge policy',
    'Never merge a target-unverified or failing PR.' => 'merge fail-closed policy',
] as $needle => $label) {
    if ($agents !== '' && ! str_contains($agents, $needle)) {
        $errors[] = "Agent governance missing: {$label}.";
    }
}

if ($package !== '' && ! str_contains($package, '"dev:target-qa": "php scripts/development-readiness.php --full --tests --evidence"')) {
    $errors[] = 'package.json must expose the canonical dev:target-qa command.';
}

foreach ([
    'npm run dev:target-qa' => 'operator one-command target QA instruction',
    'development-readiness.json' => 'development evidence documentation',
    'database-target-matrix.json' => 'database evidence documentation',
    'Never merge a failing or target-unverified PR.' => 'documented merge fail-closed rule',
] as $needle => $label) {
    if ($docs !== '' && ! str_contains($docs, $needle)) {
        $errors[] = "Development target QA documentation missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Development Target QA Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Development Target QA Contract] PASS — development readiness can execute real PHPUnit/build checks, persist source-bound detail-minimal evidence, compose with the disposable DB matrix, expose a one-command operator workflow, and final PR merging remains fail-closed on required target evidence.'.PHP_EOL,
);
