<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-plan.php';

$json = in_array('--json', $argv, true);
$plan = nexoraBuildN10TargetPlan($root);
if ($json) {
    fwrite(STDOUT, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    exit(0);
}

$progress = (array) ($plan['target_progress'] ?? []);
$percent = (int) ($progress['percent'] ?? 0);
$filled = (int) round($percent / 5);
$bar = str_repeat('█', $filled).str_repeat('░', 20 - $filled);

fwrite(STDOUT, "[N1.0 Next Action] {$plan['platform_version']}\n");
fwrite(STDOUT, "Source: {$plan['source_tree_sha256']}\n");
fwrite(STDOUT, 'Target certification: '.($progress['passed'] ?? 0).'/'.($progress['total'] ?? 6)." — {$percent}%\n");
fwrite(STDOUT, $bar."\n");
fwrite(STDOUT, "Next: {$plan['next_action']['command']}\n");
fwrite(STDOUT, "Why: {$plan['next_action']['reason']}\n");
