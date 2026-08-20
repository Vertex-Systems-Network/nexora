<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-installation-resume-fast-track-contracts.php';
$result = nexoraAnalyzeN10InstallationResumeFastTrackContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[N1.0 v5.0 Installation Resume/Fast Track] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, "[N1.0 v5.0 Installation Resume/Fast Track] PASS — exact resume provenance and safe fast-track closure runner are enforced.\n");
