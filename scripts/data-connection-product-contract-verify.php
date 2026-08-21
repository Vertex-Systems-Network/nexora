<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Data Connections source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Data Connections source file: {$relative}";
        return '';
    }
    return $contents;
};

$enterpriseMigration = $read('database/migrations/2026_08_16_002000_add_nexora_enterprise_tenancy.php');
$migration = $read('database/migrations/2026_08_21_000300_tenant_scope_nexora_data_connections.php');
$model = $read('app/Models/DataConnection.php');
$catalog = $read('app/Nexora/Data/ConnectionCatalog.php');
$tester = $read('app/Nexora/Data/ConnectionTester.php');
$controller = $read('app/Http/Controllers/Admin/Data/DataConnectionController.php');
$page = $read('resources/js/admin/pages/Admin/Data/Connections.tsx');
$routes = $read('routes/web.php');
$test = $read('tests/Feature/DataConnections/DataConnectionFlowTest.php');

if ($enterpriseMigration !== '' && ! str_contains($enterpriseMigration, "'nx_data_connections'")) {
    $errors[] = 'Canonical enterprise tenancy manifest must own nx_data_connections.';
}
foreach ([
    "Schema::hasColumn('nx_data_connections', 'tenant_id')" => 'upgrade-safe tenant-column detection',
    "'nx_tenant_'.substr(hash('sha256', 'nx_data_connections'), 0, 12).'_idx'" => 'canonical tenant index naming',
    "'nx_tenant_'.substr(hash('sha256', 'nx_data_connections'), 0, 12).'_fk'" => 'upgrade-path enterprise foreign key',
    "where('is_default', true)" => 'legacy default-tenant backfill',
    "'credential-rotation-required'" => 'legacy plaintext credential quarantine',
    "'is_enabled' => false" => 'legacy unsafe connection disablement',
    "dropUnique(['provider', 'name'])" => 'global uniqueness removal',
    "['tenant_id', 'provider', 'name']" => 'tenant-local provider/name uniqueness',
] as $needle => $label) {
    if ($migration !== '' && ! str_contains($migration, $needle)) {
        $errors[] = "Data connection tenancy migration missing: {$label}.";
    }
}
if ($migration !== '' && str_contains($migration, "dropColumn('tenant_id')")) {
    $errors[] = 'Data connection hardening rollback must not remove enterprise-owned tenant_id.';
}

foreach ([
    'use BelongsToTenant;' => 'tenant model scope',
    "'tenant_id', 'name'" => 'tenant mass-assignment field',
    "'secret_payload' => 'encrypted:array'" => 'encrypted credentials cast',
] as $needle => $label) {
    if ($model !== '' && ! str_contains($model, $needle)) {
        $errors[] = "Data connection model contract missing: {$label}.";
    }
}

foreach ([
    "'mongodb' =>" => 'MongoDB connector',
    "'mongodb_atlas' =>" => 'MongoDB Atlas connector',
    "'redis' =>" => 'Redis connector',
    "'aws_documentdb' =>" => 'DocumentDB connector',
    "'aws_elasticache_redis' =>" => 'ElastiCache Redis connector',
    "'aws_dynamodb' =>" => 'DynamoDB connector',
] as $needle => $label) {
    if ($catalog !== '' && ! str_contains($catalog, $needle)) {
        $errors[] = "Data connector catalog missing: {$label}.";
    }
}

foreach ([
    "\$uriOptions['username'] = \$username" => 'Mongo encrypted username injection',
    "\$uriOptions['password'] = \$password" => 'Mongo encrypted password injection',
    'hasEmbeddedCredentials' => 'embedded URI credential rejection',
    "'$1[redacted]@'" => 'URI userinfo error redaction',
    "foreach (['password', 'access_key', 'secret_key']" => 'known-secret value redaction',
    "str_starts_with(strtolower(\$endpoint), 'rediss://')" => 'Redis TLS endpoint handling',
] as $needle => $label) {
    if ($tester !== '' && ! str_contains($tester, $needle)) {
        $errors[] = "Connection tester contract missing: {$label}.";
    }
}

foreach ([
    "'hasPassword' =>" => 'secret-presence-only browser payload',
    "'hasAccessKey' =>" => 'access-key presence-only browser payload',
    "'hasSecretKey' =>" => 'secret-key presence-only browser payload',
    'safeEndpoint' => 'legacy endpoint redaction before browser output',
    'assertEndpointDoesNotContainCredentials' => 'plaintext credential rejection',
    'assertUniqueName' => 'tenant-local friendly uniqueness validation',
    "'connectivity_changed' => \$connectivityChanged" => 'safe audit metadata',
    "'is_enabled' => false" => 'forced disable after connectivity changes',
    "'last_tested_at' => null" => 'fresh health-test requirement',
    "if (\$connection->is_enabled)" => 'enabled delete guard',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) {
        $errors[] = "Data connection controller contract missing: {$label}.";
    }
}
if ($controller !== '' && preg_match("/'password'\s*=>\s*\(string\)\s*\(\$secret\[/", $controller) === 1) {
    $errors[] = 'Data connection browser payload must never return decrypted stored passwords.';
}

foreach ([
    "Route::get('/data/connections'" => 'Data Connections admin route',
    "permission:data.connections.view" => 'view permission',
    "permission:data.connections.manage" => 'manage permission',
    "permission:data.connections.test" => 'test permission',
    "throttle:30,1" => 'connection-test rate limit',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Data connection route contract missing: {$label}.";
    }
}

foreach ([
    'setEditTarget' => 'edit/rotation workflow',
    'Secret values are never returned to the browser.' => 'secret non-disclosure UX',
    'Leave blank to preserve the current encrypted value.' => 'safe secret-preservation UX',
    'Credentials inside the endpoint are rejected.' => 'plaintext endpoint warning',
    'passes a fresh test' => 're-test requirement UX',
    '<ConfirmDialog' => 'destructive removal confirmation',
    '!connection.enabled' => 'enabled removal suppression',
] as $needle => $label) {
    if ($page !== '' && ! str_contains($page, $needle)) {
        $errors[] = "Data Connections Admin UX contract missing: {$label}.";
    }
}
if ($page !== '' && ! str_contains($page, '@nexora/admin-ui')) {
    $errors[] = 'Data Connections UI must consume the shared Nexora Admin UI surface.';
}
if ($page !== '' && preg_match('/<(button|input|select|textarea)\b/', $page) === 1) {
    $errors[] = 'Data Connections feature UI must not bypass shared interactive components.';
}

foreach ([
    'test_credentials_are_rejected_from_plaintext_endpoint_and_encrypted_at_rest' => 'plaintext/encryption acceptance test',
    'assertStringNotContainsString' => 'raw secret non-disclosure assertion',
    'test_rotating_connectivity_preserves_blank_secret_and_forces_fresh_health_test' => 'rotation/re-test acceptance test',
    'test_enabled_connection_cannot_be_deleted' => 'enabled-delete guard test',
    'test_connection_names_and_records_are_scoped_per_organization' => 'tenant isolation acceptance test',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Data Connections acceptance-test contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Data Connections Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Data Connections Product Contract] PASS — auxiliary connectors are enterprise-tenant-scoped, secrets remain encrypted/non-disclosed, plaintext endpoint credentials are quarantined, rotation invalidates stale health and destructive removal is guarded.'.PHP_EOL,
);
