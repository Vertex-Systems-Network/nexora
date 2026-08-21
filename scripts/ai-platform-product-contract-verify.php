<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required AI Platform source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read AI Platform source file: {$relative}";
        return '';
    }
    return $contents;
};

$config = $read('config/nexora.php');
$bootstrap = $read('bootstrap/providers.php');
$provider = $read('app/Providers/AiServiceProvider.php');
$module = $read('app/Nexora/Modules/Core/AiPlatformModule.php');
$connectionModel = $read('app/Models/AiConnection.php');
$runModel = $read('app/Models/AiGenerationRun.php');
$providerContract = $read('app/Nexora/Ai/Contracts/AiTextProviderContract.php');
$registry = $read('app/Nexora/Ai/Services/AiProviderRegistry.php');
$generation = $read('app/Nexora/Ai/Services/AiGenerationService.php');
$controller = $read('app/Http/Controllers/Admin/Ai/AiPlatformController.php');
$routes = $read('routes/ai.php');
$ui = $read('resources/js/admin/pages/Admin/Ai/Index.tsx');
$migration = $read('database/migrations/2026_08_21_000800_add_nexora_ai_platform.php');
$test = $read('tests/Feature/Ai/AiPlatformIsolationTest.php');
$composer = $read('composer.json');

foreach ([
    'use App\\Nexora\\Modules\\Core\\AiPlatformModule;' => 'AI Core module import',
    'AiPlatformModule::class' => 'AI Core module registration',
    "'ai.connections.read'" => 'AI read capability',
    "'ai.connections.write'" => 'AI write capability',
    "'ai.generate'" => 'AI generation capability',
    "'ai.providers.register'" => 'AI provider registration capability',
] as $needle => $label) {
    if ($config !== '' && ! str_contains($config, $needle)) $errors[] = "AI runtime config missing: {$label}.";
}

foreach ([
    'use App\\Providers\\AiServiceProvider;' => 'AI service provider import',
    'AiServiceProvider::class' => 'AI service provider bootstrap registration',
] as $needle => $label) {
    if ($bootstrap !== '' && ! str_contains($bootstrap, $needle)) $errors[] = "AI bootstrap contract missing: {$label}.";
}

foreach ([
    '$this->app->singleton(AiProviderRegistry::class)' => 'singleton provider registry',
    '$this->app->singleton(AiGenerationService::class)' => 'singleton generation service',
    "$this->loadRoutesFrom(base_path('routes/ai.php'))" => 'AI route loading',
] as $needle => $label) {
    if ($provider !== '' && ! str_contains($provider, $needle)) $errors[] = "AI service-provider contract missing: {$label}.";
}

foreach ([
    "identifier: 'nexora.ai'" => 'AI module identity',
    "'providers' => 'verified extension registered adapters'" => 'provider-neutral adapter declaration',
    "'prompt_storage' => 'sha256-and-length-only'" => 'prompt privacy declaration',
    "'output_storage' => 'sha256-and-length-only'" => 'output privacy declaration',
    "'automatic_provider_retry' => false" => 'no implicit provider retries',
    "'permission' => 'ai.view'" => 'AI Admin navigation permission',
] as $needle => $label) {
    if ($module !== '' && ! str_contains($module, $needle)) $errors[] = "AI module contract missing: {$label}.";
}

foreach ([
    'use BelongsToTenant;' => 'tenant global scope',
    "'credentials' => 'encrypted:array'" => 'encrypted credentials cast',
    "protected \$hidden = ['credentials'];" => 'credential serialization hiding',
] as $needle => $label) {
    if ($connectionModel !== '' && ! str_contains($connectionModel, $needle)) $errors[] = "AI connection model missing: {$label}.";
}
if ($runModel !== '' && ! str_contains($runModel, 'use BelongsToTenant;')) {
    $errors[] = 'AI generation runs are not tenant scoped.';
}

foreach ([
    'interface AiTextProviderContract' => 'provider-neutral text contract',
    'public function health(array $credentials, array $settings = []): array;' => 'provider health contract',
    'public function generate(AiTextGenerationRequest $request): AiTextGenerationResult;' => 'provider generation contract',
] as $needle => $label) {
    if ($providerContract !== '' && ! str_contains($providerContract, $needle)) $errors[] = "AI provider contract missing: {$label}.";
}

foreach ([
    "preg_match('/^[a-z0-9][a-z0-9._-]+$/', \$key)" => 'stable provider-key validation',
    'AI provider already registered:' => 'duplicate provider rejection',
    'ksort($this->providers);' => 'deterministic provider ordering',
] as $needle => $label) {
    if ($registry !== '' && ! str_contains($registry, $needle)) $errors[] = "AI provider registry missing: {$label}.";
}

foreach ([
    'AiConnection::query()->whereKey($connection->id)->first()' => 'current-tenant connection re-resolution',
    "if (\$scoped->last_health_status !== 'healthy')" => 'healthy-connection admission',
    "'ai.generate.'.hash('sha256'" => 'tenant/connection/day quota mutex',
    "whereBetween('started_at', [\$start, \$end])" => 'daily request accounting',
    "'prompt_sha256' => hash('sha256', \$prompt)" => 'prompt hashing only',
    "'output_sha256' => hash('sha256', \$result->text)" => 'output hashing only',
    "'error_message' => 'AI provider generation failed.'" => 'constant failure diagnostics',
    'private function validatedRequestId' => 'provider request-id validation',
    'AI provider returned an invalid request identifier.' => 'request-id fail closed',
] as $needle => $label) {
    if ($generation !== '' && ! str_contains($generation, $needle)) $errors[] = "AI generation contract missing: {$label}.";
}
if ($generation !== '' && (str_contains($generation, "'prompt' => \$prompt") || str_contains($generation, "'output' => \$result->text"))) {
    $errors[] = 'AI generation history persists raw prompt/output fields.';
}

foreach ([
    'public function generate(Request $request, AiConnection $connection): JsonResponse' => 'direct JSON generation response',
    "return response()->json([" => 'browser-local response transport',
    'private function assertSettingsContainNoSecrets' => 'settings secret-key rejection',
    'Changing AI provider requires an explicit Credentials JSON value' => 'cross-provider credential re-entry',
    "$message = $ok ? 'Healthy.' : 'AI provider health check failed.';" => 'generic provider health diagnostics',
    "'hasCredentials' => (array) \$connection->credentials !== []" => 'credential-presence boolean only',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) $errors[] = "AI Admin contract missing: {$label}.";
}
foreach (["session()->pull('ai_generation')", "with('ai_generation'", "'credentials' => \$connection->credentials"] as $forbidden) {
    if ($controller !== '' && str_contains($controller, $forbidden)) $errors[] = "AI Admin privacy contract forbids source marker: {$forbidden}.";
}

foreach ([
    "->middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])" => 'authenticated tenant-bound Admin route group',
    "->middleware('permission:ai.view')" => 'AI view permission',
    "->middleware(['permission:ai.connections.manage', 'throttle:12,1'])" => 'bounded provider health testing',
    "->middleware(['permission:ai.generate', 'throttle:30,1'])" => 'bounded generation endpoint',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) $errors[] = "AI route contract missing: {$label}.";
}

foreach ([
    'const [generation,setGeneration]=useState<Generation|null>(null);' => 'browser-local generated response state',
    'await fetch(`/admin/ai/connections/${selectedConnection}/generate`' => 'direct JSON generation request',
    'setGeneration(body as Generation);' => 'browser-local result assignment',
    'not persisted in Nexora generation history or session flash storage' => 'privacy UX disclosure',
] as $needle => $label) {
    if ($ui !== '' && ! str_contains($ui, $needle)) $errors[] = "AI Admin UI contract missing: {$label}.";
}

foreach ([
    "Schema::create('nx_ai_connections'" => 'tenant AI connection table',
    "Schema::create('nx_ai_generation_runs'" => 'AI metadata-run table',
    "\$table->text('credentials')->nullable();" => 'encrypted-cast credential storage column',
    "\$table->char('prompt_sha256', 64);" => 'prompt digest field',
    "\$table->char('output_sha256', 64)->nullable();" => 'output digest field',
    "['name' => 'Generate with AI', 'slug' => 'ai.generate', 'group' => 'ai']" => 'AI generation permission migration',
] as $needle => $label) {
    if ($migration !== '' && ! str_contains($migration, $needle)) $errors[] = "AI migration contract missing: {$label}.";
}
foreach (["\$table->text('prompt')", "\$table->text('output')", "\$table->longText('prompt')", "\$table->longText('output')"] as $forbidden) {
    if ($migration !== '' && str_contains($migration, $forbidden)) $errors[] = "AI migration persists forbidden raw content column: {$forbidden}.";
}

foreach ([
    'test_ai_connections_are_tenant_scoped_and_credentials_are_encrypted_at_rest' => 'tenant + encrypted credential acceptance',
    'test_generation_persists_only_bounded_metadata_and_counts_failed_retries_against_daily_admission' => 'metadata-only + quota acceptance',
    'test_generation_re_resolves_the_connection_inside_current_tenant' => 'cross-tenant generation rejection',
    "assertStringNotContainsString('primary-secret', \$rawCredentials)" => 'plaintext credential absence assertion',
    "assertFalse(Schema::hasColumn('nx_ai_generation_runs', 'prompt'))" => 'raw prompt column absence assertion',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) $errors[] = "AI acceptance contract missing: {$label}.";
}

foreach (['openai/', 'anthropic/', 'google/generative-ai', 'groq/', 'mistral/'] as $vendorPackageMarker) {
    if ($composer !== '' && str_contains(strtolower($composer), $vendorPackageMarker)) {
        $errors[] = "N1.15 Core must remain provider-neutral; vendor AI SDK marker found in composer.json: {$vendorPackageMarker}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora AI Platform Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora AI Platform Product Contract] PASS — AI connections/runs are tenant-owned, credentials are encrypted and never returned, Core remains provider-neutral, generation is health/quota/bounds admitted, raw prompts/outputs are not persisted or flashed to session storage, and provider diagnostics are fail-closed.'.PHP_EOL,
);
