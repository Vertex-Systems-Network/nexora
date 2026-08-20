<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/security-contracts.php';
$result = nexoraAnalyzeSecurityContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Security Contracts] FAILED\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
$m = $result['metrics'];
fwrite(STDOUT, sprintf(
    "[Nexora Security Contracts] PASS — CSRF exceptions: %d; auth rotation paths: %d; external auth boundaries: %d; tenant route guard: %d; raw tenant exists: %d; raw tenant user exists: %d.\n",
    $m['csrf_exceptions'], $m['session_rotation_paths'], $m['external_auth_boundaries'], $m['tenant_route_binding_guards'], $m['raw_tenant_exists'], $m['raw_tenant_member_exists']
));
