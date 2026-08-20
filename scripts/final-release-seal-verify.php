<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/final-release-seal.php';$result=nexoraValidateFinalReleaseSeal($root);if(!$result['ok']){fwrite(STDERR,"[N1.0 Release Seal] FAIL\n - ".implode("\n - ",$result['errors'])."\n");exit(1);}fwrite(STDOUT,"[N1.0 Release Seal] PASS\nProduction SHA-256: {$result['production_sha256']}\nEvidence bundle SHA-256: {$result['bundle_sha256']}\n");
