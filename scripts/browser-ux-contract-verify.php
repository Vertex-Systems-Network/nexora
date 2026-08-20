<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/browser-ux-contracts.php';
$result = nexoraAnalyzeBrowserUxContracts($root);

fwrite(STDOUT, "[Nexora Browser/UX Contracts]\n");
if ($result['ok']) {
    fwrite(STDOUT, 'PASS — '.$result['metrics']['admin_files'].' Admin TS/TSX files; logical RTL/a11y source contracts aligned.'.PHP_EOL);
    exit(0);
}
foreach ($result['errors'] as $error) fwrite(STDERR, ' - '.$error.PHP_EOL);
exit(1);
