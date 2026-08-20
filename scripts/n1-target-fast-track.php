<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/n1-target-plan.php';
require_once $root.'/scripts/lib/n1-target-progress.php';
require_once $root.'/scripts/lib/n1-installation-progress.php';

$installDependencies = in_array('--install-deps', $argv, true);
$statusOnly = in_array('--status-only', $argv, true);
$operator = '';
$baseUrl = '';
$c4Evidence = '';
$c5Evidence = '';
$c6Evidence = '';

foreach ($argv as $argument) {
    if (str_starts_with($argument, '--operator=')) {
        $operator = trim(substr($argument, 11));
    } elseif (str_starts_with($argument, '--base-url=')) {
        $baseUrl = trim(substr($argument, 11));
    } elseif (str_starts_with($argument, '--c4-evidence=')) {
        $c4Evidence = trim(substr($argument, 14));
    } elseif (str_starts_with($argument, '--c5-evidence=')) {
        $c5Evidence = trim(substr($argument, 14));
    } elseif (str_starts_with($argument, '--c6-evidence=')) {
        $c6Evidence = trim(substr($argument, 14));
    }
}

function fastTrackBar(int $percent): string
{
    $filled = (int) round(max(0, min(100, $percent)) / 5);
    return str_repeat('█', $filled).str_repeat('░', 20 - $filled);
}

/** @param array<string,mixed> $plan */
function printFastTrackPlan(array $plan): void
{
    $installation = nexoraBuildInstallationProgress(dirname(__DIR__));
    nexoraPersistInstallationProgress(dirname(__DIR__), $installation);
    fwrite(STDOUT, "
[N1.0 Fast Track] Installation execution progress
");
    fwrite(STDOUT, nexoraRenderInstallationProgress($installation)."
");
    fwrite(STDOUT, 'Installation: '.(string) ($installation['message'] ?? 'unknown')."
");

    $progress = (array) ($plan['target_progress'] ?? []);
    $percent = (int) ($progress['percent'] ?? 0);
    $passed = (int) ($progress['passed'] ?? 0);
    $total = (int) ($progress['total'] ?? 6);

    fwrite(STDOUT, "\n[N1.0 Fast Track] Strict chunk certification {$passed}/{$total} — {$percent}%\n");
    fwrite(STDOUT, fastTrackBar($percent)."\n");

    $granular = (array) ($progress['granular'] ?? []);
    if ($granular !== []) {
        nexoraPersistN10GranularProgress(dirname(__DIR__), $granular);
        fwrite(STDOUT, "[N1.0 Fast Track] Granular exact-source gate progress\n");
        fwrite(STDOUT, nexoraRenderN10GranularProgress($granular)."\n");
        fwrite(STDOUT, "Progress file: storage/app/nexora/n1-target-execution/target-progress.json\n");
    }

    fwrite(STDOUT, 'Next: '.(string) ($plan['next_action']['command'] ?? 'unknown')."\n");
    fwrite(STDOUT, 'Why: '.(string) ($plan['next_action']['reason'] ?? 'unknown')."\n");
}

$initialPlan = nexoraBuildN10TargetPlan($root);
printFastTrackPlan($initialPlan);

$promotionJournalPath = $root.'/storage/app/nexora/dependency-intake/lock-promotion-journal.json';
if (is_file($promotionJournalPath)) {
    try {
        $promotionJournal = json_decode((string) file_get_contents($promotionJournalPath), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        $promotionJournal = ['status' => 'invalid'];
    }
    if (! in_array((string) ($promotionJournal['status'] ?? ''), ['complete', 'rolled-back'], true)) {
        fwrite(STDERR, "[N1.0 Fast Track] Incomplete dependency lock promotion detected. Run `scripts\\recover-dependency-lock-promotion.bat --confirm=ROLLBACK` before target execution.\n");
        exit(2);
    }
}

if ($statusOnly) {
    exit(((int) ($initialPlan['target_progress']['passed'] ?? 0)) === 6 ? 0 : 2);
}

$lockState = (array) ($initialPlan['locks'] ?? []);
$prerequisites = (array) ($initialPlan['prerequisites'] ?? []);
if (($prerequisites['restart_ticket'] ?? false) === true) {
    fwrite(STDERR, "[N1.0 Fast Track] A Laragon restart ticket is pending. Verify the restart first; fast-track will not pretend the new PHP runtime is active.\n");
    exit(2);
}
if (($prerequisites['composer_available'] ?? false) !== true) {
    fwrite(STDERR, "[N1.0 Fast Track] Composer is unavailable. Install/expose trusted Composer 2.x, then rerun this command.\n");
    exit(2);
}
if (($lockState['composer_lock'] ?? false) !== true || ($lockState['package_lock'] ?? false) !== true) {
    fwrite(STDERR, "[N1.0 Fast Track] Deterministic dependency locks are missing. Run `scripts\\refresh-dependency-locks.bat --confirm=REFRESH`, review both staged candidates, then run `scripts\\promote-reviewed-dependency-locks.bat --reviewer=\"YOUR NAME\" --confirm=PROMOTE-REVIEWED`. Fast-track never creates or promotes unreviewed locks automatically.\n");
    exit(2);
}
if (($lockState['reviewed'] ?? false) !== true) {
    fwrite(STDERR, "[N1.0 Fast Track] Reviewed-lock attestation is not ready. If staged candidates were reviewed, run `scripts\\promote-reviewed-dependency-locks.bat --reviewer=\"YOUR NAME\" --confirm=PROMOTE-REVIEWED`; otherwise refresh/review first. Human review remains mandatory.\n");
    exit(2);
}

$parts = [PHP_BINARY, 'scripts/n1-target-execution.php', '--resume-latest'];
if ($installDependencies) {
    $parts[] = '--install-deps';
}
if ($operator !== '') {
    $parts[] = '--prepare-kits';
    $parts[] = '--operator='.$operator;
}

$evidence = array_values(array_filter([$c4Evidence, $c5Evidence, $c6Evidence], static fn (string $value): bool => $value !== ''));
if ($evidence !== []) {
    if (count($evidence) !== 3 || $baseUrl === '' || $operator === '') {
        fwrite(STDERR, "[N1.0 Fast Track] C4/C5/C6 evidence requires all three evidence paths plus --base-url and --operator.\n");
        exit(2);
    }
    $parts = [PHP_BINARY, 'scripts/n1-target-execution.php', '--resume-latest'];
    $parts[] = '--base-url='.$baseUrl;
    $parts[] = '--operator='.$operator;
    $parts[] = '--c4-evidence='.$c4Evidence;
    $parts[] = '--c5-evidence='.$c5Evidence;
    $parts[] = '--c6-evidence='.$c6Evidence;
}

$command = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $parts));
fwrite(STDOUT, "\n[N1.0 Fast Track] Running the maximum safe automated closure path\n> {$command}\n");
$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
$process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root, $environment, ['bypass_shell' => false]);
if (! is_resource($process)) {
    fwrite(STDERR, "[N1.0 Fast Track] Unable to start target execution.\n");
    exit(1);
}
$exit = proc_close($process);

$finalPlan = nexoraBuildN10TargetPlan($root);
printFastTrackPlan($finalPlan);

if ($exit === 0 && ((int) ($finalPlan['target_progress']['passed'] ?? 0)) === 6) {
    fwrite(STDOUT, "[N1.0 Fast Track] C1-C6 target evidence is complete. Continue with signed final closure.\n");
}

exit($exit);
