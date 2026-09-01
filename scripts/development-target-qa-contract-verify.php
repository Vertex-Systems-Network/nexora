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
$sourceActivateCommand = $read('app/Console/Commands/Nexora/SourceActivateCommand.php');
$sourceActivationIdentity = $read('app/Nexora/Installation/SourceActivationIdentity.php');
$sourceActivateWindows = $read('scripts/n1-source-activate.bat');
$sourceActivateUnix = $read('scripts/n1-source-activate.sh');
$targetBootstrap = $read('scripts/target-environment-bootstrap.php');
$targetComposer = $read('scripts/lib/target-composer.php');

foreach ([
    "in_array('--tests', \$argv, true)" => 'explicit full-PHPUnit opt-in',
    "in_array('--evidence', \$argv, true)" => 'durable evidence opt-in',
    "[PHP_BINARY, 'artisan', 'test', '--colors=never', '--stop-on-failure', '--display-warnings', '--fail-on-warning']" => 'real warning-clean Laravel/PHPUnit fail-fast execution',
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
    'Nexora is not tied to Laragon or any other local-server vendor.' => 'explicit vendor-agnostic deployment boundary',
    'optional adapters' => 'optional local-server adapter boundary',
    'npm run dev:target-qa' => 'operator one-command target QA instruction',
    'development-readiness.json' => 'development evidence documentation',
    'database-target-matrix.json' => 'database evidence documentation',
    'Never merge a failing or target-unverified PR.' => 'documented merge fail-closed rule',
] as $needle => $label) {
    if ($docs !== '' && ! str_contains($docs, $needle)) {
        $errors[] = "Development target QA documentation missing: {$label}.";
    }
}

foreach ([
    $sourceActivateCommand => 'SourceActivateCommand',
    $sourceActivationIdentity => 'SourceActivationIdentity',
    $sourceActivateWindows => 'Windows source activation helper',
    $sourceActivateUnix => 'Unix source activation helper',
] as $source => $label) {
    if ($source !== '' && stripos($source, 'Laragon') !== false) {
        $errors[] = "{$label} must describe the active PHP/web service generically rather than require Laragon.";
    }
}

foreach ([
    'active PHP/web service' => 'generic active web/PHP restart guidance',
    "config('app.url', 'http://localhost')" => 'configured target URL selection',
    'PHP_OS_FAMILY' => 'platform-aware acknowledgement helper selection',
] as $needle => $label) {
    if ($sourceActivateCommand !== '' && ! str_contains($sourceActivateCommand, $needle)) {
        $errors[] = "Source activation command portability contract missing: {$label}.";
    }
}

foreach ([
    'BASE_URL' => 'explicit target URL input',
    'active PHP/web service' => 'vendor-neutral restart guidance',
] as $needle => $label) {
    if ($sourceActivateWindows !== '' && ! str_contains($sourceActivateWindows, $needle)) {
        $errors[] = "Windows source activation portability contract missing: {$label}.";
    }
    if ($sourceActivateUnix !== '' && ! str_contains($sourceActivateUnix, $needle)) {
        $errors[] = "Unix source activation portability contract missing: {$label}.";
    }
}

foreach ([
    "'local_server_adapter'" => 'optional adapter metadata',
    'Select or install a PHP build inside the certified range' => 'generic PHP remediation',
    'expose it on PATH' => 'generic PATH-first tool remediation',
    'Optional local-server adapters may be discovered without becoming a platform requirement.' => 'explicit adapter non-requirement',
] as $needle => $label) {
    if ($targetBootstrap !== '' && ! str_contains($targetBootstrap, $needle)) {
        $errors[] = "Target bootstrap portability contract missing: {$label}.";
    }
}

foreach ([
    "['composer', '--version', '--no-ansi']" => 'PATH-first Composer probe',
    "'composer.exe'" => 'Windows Composer executable resolution',
    "'composer.cmd'" => 'Windows Composer command-shim resolution',
    "'composer.bat'" => 'Windows Composer batch-shim resolution',
    "composer.phar" => 'Composer PHAR fallback resolution',
    'Laragon is an optional Windows local-server adapter, never a platform requirement.' => 'optional Laragon adapter boundary',
] as $needle => $label) {
    if ($targetComposer !== '' && ! str_contains($targetComposer, $needle)) {
        $errors[] = "Target Composer portability contract missing: {$label}.";
    }
}

foreach (['app', 'bootstrap', 'config', 'routes'] as $relativeDirectory) {
    $directory = $root.'/'.$relativeDirectory;
    if (! is_dir($directory)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile()) continue;
        $contents = file_get_contents($file->getPathname());
        if ($contents === false) continue;
        if (preg_match('~[A-Za-z]:[\\\\/]laragon[\\\\/]www[\\\\/]nexora~i', $contents) === 1) {
            $errors[] = 'Core runtime must not contain a concrete Laragon project-path dependency: '.$file->getPathname();
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Development Target QA Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Development Target QA Contract] PASS — development readiness is warning-clean and evidence-bound, disposable database QA remains fail-closed, core runtime/source activation is server-vendor agnostic with optional adapters only, Windows command shims remain portable, and final PR merging remains fail-closed on required target evidence.'.PHP_EOL,
);
