<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Public API / SDK source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Public API / SDK source file: {$relative}";
        return '';
    }
    return $contents;
};

$require = static function (string $source, array $needles, string $scope) use (&$errors): void {
    foreach ($needles as $needle => $label) {
        if ($source !== '' && ! str_contains($source, (string) $needle)) {
            $errors[] = "{$scope} contract missing: {$label}.";
        }
    }
};

$forbid = static function (string $source, array $needles, string $scope) use (&$errors): void {
    foreach ($needles as $needle => $label) {
        if ($source !== '' && str_contains($source, (string) $needle)) {
            $errors[] = "{$scope} still contains forbidden {$label}.";
        }
    }
};

$migration = $read('database/migrations/2026_08_21_000900_add_nexora_public_api.php');
$model = $read('app/Models/ApiAccessToken.php');
$abilities = $read('app/Nexora/Api/Services/ApiAbilityRegistry.php');
$manager = $read('app/Nexora/Api/Services/ApiTokenManager.php');
$authMiddleware = $read('app/Http/Middleware/AuthenticateApiToken.php');
$abilityMiddleware = $read('app/Http/Middleware/RequireApiAbility.php');
$bootstrap = $read('bootstrap/app.php');
$apiRoutes = $read('routes/api.php');
$adminRoutes = $read('routes/developer-api.php');
$adminController = $read('app/Http/Controllers/Admin/Developer/ApiTokenController.php');
$apiController = $read('app/Http/Controllers/Api/V1/DocumentController.php');
$ui = $read('resources/js/admin/pages/Admin/Developer/ApiTokens.tsx');
$publicContract = $read('app/Nexora/Api/Contracts/PublicApiContract.php');
$coreContract = $read('app/Nexora/Api/Services/CorePublicApiContract.php');
$provider = $read('app/Providers/ApiServiceProvider.php');
$providers = $read('bootstrap/providers.php');
$docs = $read('docs/PUBLIC_API_V1.md');
$test = $read('tests/Feature/Api/PublicApiTenantIsolationTest.php');
$inbound = $read('app/Http/Controllers/Public/InboundWebhookController.php');
$signer = $read('app/Nexora/Automation/Services/WebhookSigner.php');
$progress = $read('NEXORA_PROGRESS.md');

$require($migration, [
    "Schema::create('nx_api_access_tokens'" => 'tenant API-token table',
    "\$table->uuid('tenant_id')" => 'tenant ownership',
    "\$table->foreignId('user_id')" => 'issuing actor ownership',
    "\$table->string('token_hash', 64)->unique()" => 'unique SHA-256 token hash storage',
    "\$table->json('abilities')" => 'explicit token abilities',
    "\$table->timestamp('expires_at')->index()" => 'mandatory indexed expiry',
    "\$table->timestamp('revoked_at')->nullable()->index()" => 'revocation lifecycle',
], 'API-token schema');
$forbid($migration, [
    "\$table->string('token'" => 'plaintext token column',
    "\$table->text('token'" => 'plaintext token text column',
], 'API-token schema');

$require($model, [
    'use BelongsToTenant;' => 'tenant global scope',
    "protected \$hidden = ['token_hash'];" => 'hash serialization fence',
    "'abilities' => 'array'" => 'ability casting',
    'public function allows(string $ability): bool' => 'scope evaluation helper',
], 'API-token model');

$require($abilities, [
    "public const DOCUMENTS_READ = 'documents.read';" => 'bounded document-read ability',
    'The requested API ability is not supported.' => 'unknown ability rejection',
], 'API ability registry');

$require($manager, [
    "\$plain = 'nxapi_'.Str::random(64);" => 'namespaced one-time token issuance',
    "'token_hash' => hash('sha256', \$plain)" => 'hash-only token persistence',
    "if (\$expiresInDays < 1 || \$expiresInDays > 365)" => 'bounded expiry',
    "where('status', 'active')" => 'active membership revalidation',
    "whereNull('revoked_at')" => 'revoked-token rejection',
    "\$record->expires_at->isPast()" => 'expired-token rejection',
    "'api.token.issued'" => 'issuance audit event',
    "'api.token.revoked'" => 'revocation audit event',
], 'API token manager');
$forbid($manager, [
    "'token' => \$plain" => 'plaintext token persistence payload',
], 'API token manager');

$require($authMiddleware, [
    '$request->bearerToken()' => 'Bearer authentication',
    "'nexora-api-token:'.\$token->id" => 'per-token rate key',
    'RateLimiter::tooManyAttempts($rateKey, 120)' => '120 request/minute admission',
    '$this->tenant->set($organization);' => 'token tenant installation',
    'Auth::setUser($user);' => 'token actor installation',
    '$request->attributes->set(ApiAccessToken::class, $token);' => 'authenticated token request attribute',
    '$this->tenant->set($previousOrganization);' => 'tenant restoration',
    "'X-Nexora-Api-Version', 'v1'" => 'version response header',
], 'API token middleware');

$require($abilityMiddleware, [
    '! $token->allows($ability)' => 'explicit ability enforcement',
    "'code' => 'insufficient_scope'" => 'stable insufficient-scope error',
    '], 403);' => '403 scope denial',
], 'API ability middleware');

$require($bootstrap, [
    "api: __DIR__.'/../routes/api.php'" => 'Laravel API route registration',
    "'api.token' => AuthenticateApiToken::class" => 'token middleware alias',
    "'api.ability' => RequireApiAbility::class" => 'ability middleware alias',
], 'Application bootstrap');

$require($apiRoutes, [
    "Route::prefix('v1')" => 'versioned API prefix',
    "->middleware(['api.token'])" => 'stateless token authentication',
    "->middleware('api.ability:documents.read')" => 'document-read ability route fence',
    "->whereNumber('document')" => 'bounded document route key',
], 'Public API routes');

$require($apiController, [
    'min(100, (int) $request->query' => 'pagination maximum',
    '$query->cursorPaginate($perPage)' => 'cursor pagination',
    'public function show(string $document): JsonResponse' => 'scalar detail route argument',
    'Document::query()' => 'post-auth tenant-scoped resource query',
    '->whereKey($document)' => 'explicit resource re-resolution',
], 'Document API');
$forbid($apiController, [
    'public function show(Document $document)' => 'implicit document binding before token tenant context',
], 'Document API');

$require($adminRoutes, [
    "Route::prefix('admin/developer')" => 'developer Admin namespace',
    "permission:enterprise.identity.manage" => 'sensitive existing identity permission boundary',
    "EnsureTenantRouteBinding::class" => 'Admin tenant route binding',
], 'Admin API routes');

$require($adminController, [
    "return response()->json([" => 'direct one-time JSON issuance response',
    "'token' => \$created['token']" => 'one-time plaintext response only',
    'public function destroy(Request $request, string $token' => 'scalar revoke route argument',
    'ApiAccessToken::query()->whereKey($token)->firstOrFail()' => 'post-tenant revoke re-resolution',
], 'Admin API-token controller');
$forbid($adminController, [
    "->with('token'" => 'plaintext token session flash',
    'public function destroy(Request $request, ApiAccessToken $token' => 'implicit revoke model binding',
], 'Admin API-token controller');

$require($ui, [
    'const [issued, setIssued] = useState<Issued | null>(null);' => 'browser-local issued-token state',
    'await fetch("/admin/developer/api-tokens"' => 'direct JSON issuance request',
    'navigator.clipboard.writeText(issued.token)' => 'one-time copy UX',
    'setIssued(null)' => 'plaintext dismissal',
    'Plaintext stored' => 'credential persistence disclosure',
], 'Admin API-token UI');

$require($publicContract, [
    'interface PublicApiContract' => 'stable public API descriptor interface',
    'public function version(): string;' => 'version contract',
    'public function abilities(): array;' => 'ability descriptor contract',
    'public function resources(): array;' => 'resource descriptor contract',
], 'Public SDK contract');
$require($coreContract, [
    "return 'v1';" => 'v1 implementation',
    "'path' => '/api/v1/documents'" => 'document list descriptor',
    "'max_per_page' => 100" => 'pagination contract descriptor',
], 'Core public API descriptor');
$require($provider, [
    'PublicApiContract::class, CorePublicApiContract::class' => 'stable contract binding',
    "'href' => '/admin/developer/api-tokens'" => 'Admin integration navigation',
    "'permission' => 'enterprise.identity.manage'" => 'navigation permission fence',
], 'API service provider');
$require($providers, [
    'ApiServiceProvider::class' => 'API provider bootstrap',
], 'Provider bootstrap');

$require($docs, [
    'Base path: `/api/v1`' => 'documented versioned base path',
    'Nexora stores only a SHA-256 token hash' => 'documented hash-only credential contract',
    '`documents.read`' => 'documented first ability',
    'Breaking field/path/auth changes require a new major API prefix.' => 'major-version compatibility rule',
    'Internal Eloquent models, controllers and service implementations are not public SDK contracts.' => 'SDK/internal boundary',
], 'Public API documentation');

$require($test, [
    'test_plaintext_token_is_never_persisted_and_reads_only_current_tenant_documents' => 'hash-only/read acceptance',
    'test_guessed_document_id_from_another_tenant_is_not_resolved' => 'cross-tenant resource acceptance',
    'test_expired_revoked_and_stale_membership_tokens_fail_closed' => 'token lifecycle acceptance',
    'test_missing_document_scope_is_forbidden_and_pagination_is_capped' => 'scope/pagination acceptance',
    'test_admin_cannot_revoke_token_from_another_tenant_by_guessing_uuid' => 'cross-tenant revoke acceptance',
], 'Public API acceptance');

$require($signer, [
    "hash_hmac('sha256', \$timestamp.'.'.\$body, \$secret)" => 'HMAC-SHA256 webhook signing',
    'hash_equals(' => 'constant-time webhook verification',
], 'Webhook signer');
$require($inbound, [
    'strlen($body) > 1_048_576' => '1 MB inbound payload bound',
    'abs(now()->timestamp - (int) $timestamp) > 300' => 'five-minute replay window',
    "where('webhook_endpoint_id', \$endpoint->id)" => 'endpoint-scoped webhook idempotency',
    "where('idempotency_key', \$idempotency)" => 'webhook idempotency key',
    "collect(['content-type', 'user-agent', 'x-request-id', 'idempotency-key'])" => 'safe persisted webhook headers',
], 'Inbound webhook');

if ($progress !== '' && ! preg_match('/^##\s+\d+\.\s+Apply Log$/m', $progress)) {
    $errors[] = 'Progress dashboard contract missing: per-apply progress history.';
}
if ($progress !== '' && ! str_contains($progress, 'Target Power')) {
    $errors[] = 'Progress dashboard contract missing: Target Power evidence boundary.';
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Public API / SDK Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Public API / SDK Product Contract] PASS — public API v1 uses tenant-bound hash-only expiring/revocable bearer credentials, explicit abilities, bounded pagination, post-auth tenant resource resolution and a stable descriptor/SDK boundary; Admin issuance never persists plaintext, and existing Automation webhooks retain signature/replay/idempotency safeguards.'.PHP_EOL,
);
