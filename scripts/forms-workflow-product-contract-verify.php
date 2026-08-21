<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Forms/Data/Workflows source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Forms/Data/Workflows source file: {$relative}";
        return '';
    }
    return $contents;
};

$migration = $read('database/migrations/2026_08_21_000200_add_nexora_forms.php');
$formModel = $read('app/Models/FormDefinition.php');
$submissionModel = $read('app/Models/FormSubmission.php');
$definition = $read('app/Nexora/Forms/Services/FormDefinitionValidator.php');
$submission = $read('app/Nexora/Forms/Services/FormSubmissionManager.php');
$admin = $read('app/Http/Controllers/Admin/Forms/FormController.php');
$public = $read('app/Http/Controllers/Public/FormController.php');
$routes = $read('routes/forms.php');
$provider = $read('app/Providers/FormsServiceProvider.php');
$providers = $read('bootstrap/providers.php');
$automationModule = $read('app/Nexora/Modules/Core/AutomationModule.php');
$triggers = $read('app/Nexora/Automation/Services/AutomationTriggerRegistry.php');
$indexPage = $read('resources/js/admin/pages/Admin/Forms/Index.tsx');
$formPage = $read('resources/js/admin/pages/Admin/Forms/Form.tsx');
$submissionPage = $read('resources/js/admin/pages/Admin/Forms/Submissions.tsx');
$test = $read('tests/Feature/Forms/FormWorkflowTest.php');

foreach ([
    "Schema::create('nx_forms'" => 'form definition table',
    "Schema::create('nx_form_submissions'" => 'submission table',
    "'forms.view'" => 'forms read permission',
    "'forms.manage'" => 'forms manage permission',
    "'forms.submissions.view'" => 'submissions permission',
    "unique(['tenant_id', 'slug']" => 'tenant-local form slug uniqueness',
] as $needle => $label) {
    if ($migration !== '' && ! str_contains($migration, $needle)) {
        $errors[] = "Forms migration contract missing: {$label}.";
    }
}

foreach ([
    'use BelongsToTenant;' => 'tenant model scope',
    "protected \$table = 'nx_forms'" => 'forms table mapping',
    "return 'slug';" => 'slug route binding',
    'isPubliclySubmittable' => 'public status gate',
] as $needle => $label) {
    if ($formModel !== '' && ! str_contains($formModel, $needle)) {
        $errors[] = "Form model contract missing: {$label}.";
    }
}
foreach ([
    'use BelongsToTenant;' => 'tenant submission scope',
    "protected \$table = 'nx_form_submissions'" => 'submission table mapping',
    "'submitted_at' => 'datetime'" => 'submission timestamp cast',
] as $needle => $label) {
    if ($submissionModel !== '' && ! str_contains($submissionModel, $needle)) {
        $errors[] = "Submission model contract missing: {$label}.";
    }
}

foreach ([
    "private const TYPES = ['text', 'email', 'textarea', 'number', 'select', 'checkbox', 'date']" => 'controlled field type allow-list',
    'count($fields) > 50' => 'field-count budget',
    "preg_match('/^[a-z][a-z0-9_]{0,63}$/'" => 'stable payload-key validation',
    'Select option values and labels are required and values must be unique.' => 'select-option uniqueness validation',
] as $needle => $label) {
    if ($definition !== '' && ! str_contains($definition, $needle)) {
        $errors[] = "Form definition validation contract missing: {$label}.";
    }
}

foreach ([
    'Validator::make($input, $rules' => 'schema-derived submission validation',
    "'tenant_id' => \$form->tenant_id" => 'tenant-preserving submission write',
    "'form.submitted'" => 'automation event bridge',
    "'form-submission:'.\$submission->uuid" => 'automation idempotency key',
    "'values' => \$values" => 'validated-only stored values',
    "'locale' =>" => 'minimal locale metadata',
    "'authenticated' => \$user !== null" => 'minimal authentication metadata',
] as $needle => $label) {
    if ($submission !== '' && ! str_contains($submission, $needle)) {
        $errors[] = "Form submission manager contract missing: {$label}.";
    }
}
if ($submission !== '' && (str_contains($submission, '->ip()') || str_contains($submission, 'userAgent()'))) {
    $errors[] = 'Form submission storage must not persist raw IP address or user-agent metadata.';
}

foreach ([
    "Rule::unique('nx_forms', 'slug')" => 'tenant-aware admin slug validation',
    'FormDefinitionValidator $definition' => 'server schema validator usage',
    "'forms.definition.created'" => 'form create audit event',
    "'forms.definition.updated'" => 'form update audit event',
    "Rule::in(['draft', 'active', 'paused', 'archived'])" => 'non-destructive lifecycle statuses',
] as $needle => $label) {
    if ($admin !== '' && ! str_contains($admin, $needle)) {
        $errors[] = "Forms admin controller contract missing: {$label}.";
    }
}

foreach ([
    'abort_unless($form->isPubliclySubmittable(), 404)' => 'fail-closed public status gate',
    "'_nx_website'" => 'honeypot anti-spam field',
    "csrf_token()" => 'CSRF token output',
    'aria-invalid="true"' => 'public validation accessibility',
    'role="alert"' => 'public error announcement',
    'content="noindex,follow"' => 'noindex-first public policy marker',
] as $needle => $label) {
    if ($public !== '' && ! str_contains($public, $needle)) {
        $errors[] = "Public form contract missing: {$label}.";
    }
}

foreach ([
    "Route::get('/forms/{form}'" => 'public render route',
    "Route::post('/forms/{form}'" => 'public submission route',
    "->middleware('throttle:10,1')" => 'public submission throttling',
    "permission:forms.view" => 'admin forms read guard',
    "permission:forms.manage" => 'admin forms write guard',
    "permission:forms.submissions.view" => 'submission inbox guard',
    'EnsureTenantRouteBinding::class' => 'tenant route-binding guard',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Forms route contract missing: {$label}.";
    }
}
if ($routes !== '' && str_contains($routes, 'Route::delete')) {
    $errors[] = 'Initial Forms lifecycle must remain non-destructive; archive/pause instead of exposing delete routes.';
}

if ($provider !== '' && ! str_contains($provider, "loadRoutesFrom(base_path('routes/forms.php'))")) {
    $errors[] = 'Forms route provider must load the isolated forms route surface.';
}
if ($providers !== '' && ! str_contains($providers, 'FormsServiceProvider::class')) {
    $errors[] = 'Forms service provider is not registered in bootstrap/providers.php.';
}
foreach ([
    "['id'=>'forms','label'=>'Forms','href'=>'/admin/forms'" => 'Forms admin navigation',
    "name:'Nexora Forms, Automation & Webhooks'" => 'shared Forms/Automation module identity',
] as $needle => $label) {
    if ($automationModule !== '' && ! str_contains($automationModule, $needle)) {
        $errors[] = "Automation module Forms integration missing: {$label}.";
    }
}
if ($triggers !== '' && ! str_contains($triggers, "['key'=>'form.submitted'")) {
    $errors[] = 'Automation trigger registry must expose form.submitted.';
}

foreach ([$indexPage, $formPage, $submissionPage] as $page) {
    if ($page !== '' && ! str_contains($page, '@nexora/admin-ui')) {
        $errors[] = 'Forms Admin pages must consume the shared Nexora Admin UI surface.';
    }
    if ($page !== '' && preg_match('/<(button|input|select|textarea)\b/', $page) === 1) {
        $errors[] = 'Forms Admin pages must not bypass shared UI components with raw interactive controls.';
    }
}
foreach ([
    'Up to 50 fields.' => 'schema field budget UX',
    'form.submitted' => 'Automation bridge guidance',
    'require_auth' => 'authenticated-only form option',
    'indexable' => 'search indexing option',
] as $needle => $label) {
    if ($formPage !== '' && ! str_contains($formPage, $needle)) {
        $errors[] = "Forms editor UX contract missing: {$label}.";
    }
}

foreach ([
    'test_public_submission_is_validated_stored_and_emits_automation_event' => 'public submission + Automation acceptance test',
    'assertArrayNotHasKey' => 'unknown payload discard assertion',
    "'event_key' => 'form.submitted'" => 'Automation event assertion',
    'Queue::assertPushed(ExecuteWorkflowRunJob::class)' => 'queued workflow assertion',
    'test_auth_required_form_rejects_guest_and_honeypot_does_not_store_data' => 'auth/honeypot regression test',
    'test_paused_form_is_not_publicly_available' => 'inactive public gate test',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Forms acceptance-test contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Forms/Data/Workflows Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Forms/Data/Workflows Product Contract] PASS — tenant-safe form definitions, controlled public validation/storage, privacy-minimal submissions, Automation event bridging, non-destructive lifecycle and shared Admin UX are source-aligned.'.PHP_EOL,
);
