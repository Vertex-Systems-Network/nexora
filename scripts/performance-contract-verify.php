<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/performance-contracts.php';
$result = nexoraAnalyzePerformanceContracts($root);

fwrite(STDOUT, "[Nexora Performance/Packaging Contracts]\n");
if ($result['ok']) {
    $m = $result['metrics'];
    fwrite(STDOUT, 'PASS — '.$m['static_public_assets'].' static assets; largest '.$m['largest_static_public_asset_bytes'].' bytes; release policy '.$m['release_required_entries'].' required / '.$m['release_forbidden_prefixes'].' forbidden prefixes.'.PHP_EOL);
    exit(0);
}
foreach ($result['errors'] as $error) fwrite(STDERR, ' - '.$error.PHP_EOL);
exit(1);
