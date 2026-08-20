<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-historical-typescript-remediation.php';

$result = nexoraAnalyzeHistoricalTypeScriptRemediation($root);

fwrite(STDOUT, sprintf(
    "[Historical TypeScript] Source remediated: %d/%d errors across %d/%d files.\n",
    $result['source_remediated_errors'],
    $result['historical_error_total'],
    $result['source_remediated_files'],
    $result['historical_file_total'],
));
fwrite(STDOUT, sprintf(
    "[Historical TypeScript] Real C1 verified: %d/%d errors across %d/%d files.\n",
    $result['target_verified_errors'],
    $result['historical_error_total'],
    $result['target_verified_files'],
    $result['historical_file_total'],
));

foreach ($result['rows'] as $row) {
    fwrite(STDOUT, sprintf(
        "%s  %2d  %s\n",
        $row['source_remediated'] ? 'PASS' : 'FAIL',
        $row['historical_errors'],
        $row['file'],
    ));
}

if ($result['errors'] !== []) {
    fwrite(STDERR, " - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

exit(0);
