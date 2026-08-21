<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Customer Portal source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Customer Portal source file: {$relative}";
        return '';
    }
    return $contents;
};

$routes = $read('routes/portal.php');
$provider = $read('app/Providers/CustomerPortalServiceProvider.php');
$bootstrap = $read('bootstrap/providers.php');
$controller = $read('app/Http/Controllers/Portal/CustomerPortalController.php');
$auth = $read('app/Http/Controllers/Auth/AuthenticatedSessionController.php');
$layout = $read('resources/js/admin/layout/CustomerPortalLayout.tsx');
$page = $read('resources/js/admin/pages/Portal/Dashboard.tsx');
$test = $read('tests/Feature/Portal/CustomerPortalFlowTest.php');

foreach ([
    "Route::middleware(['web', 'auth', 'verified'])" => 'verified authenticated portal boundary',
    "Route::get('/account', CustomerPortalController::class)" => 'customer portal dashboard route',
    "->name('portal.dashboard')" => 'stable portal route name',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) $errors[] = "Customer Portal route contract missing: {$label}.";
}

foreach ([
    "loadRoutesFrom(base_path('routes/portal.php'))" => 'modular portal route loading',
] as $needle => $label) {
    if ($provider !== '' && ! str_contains($provider, $needle)) $errors[] = "Customer Portal provider missing: {$label}.";
}
if ($bootstrap !== '' && ! str_contains($bootstrap, 'CustomerPortalServiceProvider::class')) {
    $errors[] = 'Customer Portal provider is not registered in bootstrap/providers.php.';
}

foreach ([
    "CommerceCustomer::query()" => 'Commerce customer source',
    "->where('user_id', \$user->id)" => 'current-user Commerce identity filter',
    "Membership::query()" => 'Membership source',
    "->where('user_id', \$user->id)" => 'current-user membership filter',
    "CommerceOrder::query()" => 'order history source',
    "->where('customer_id', \$customer->id)" => 'customer-scoped order history',
    "CommerceInvoice::query()" => 'invoice history source',
    "CommerceSubscription::query()" => 'subscription history source',
    "Inertia::render('Portal/Dashboard'" => 'portal Inertia surface',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) $errors[] = "Customer Portal controller missing: {$label}.";
}
if ($controller !== '' && (str_contains($controller, "where('email', \$user->email)") || str_contains($controller, "where('email', strtolower"))) {
    $errors[] = 'Customer Portal must not infer customer ownership from email matching; explicit user/customer identity is required.';
}
if ($controller !== '' && preg_match('/(?:update|delete|create|save)\s*\(/', $controller) === 1) {
    $errors[] = 'First Customer Portal dashboard must remain read-only; mutations belong to explicit future self-service actions.';
}

foreach ([
    'if ($user?->canAccessAdmin())' => 'admin-aware post-login routing',
    "return redirect()->intended(route('admin.dashboard'))" => 'admin intended-destination preservation',
    "\$request->session()->forget('url.intended')" => 'non-admin Admin-intended URL clearing',
    "return redirect()->route('portal.dashboard')" => 'non-admin portal destination',
] as $needle => $label) {
    if ($auth !== '' && ! str_contains($auth, $needle)) $errors[] = "Authentication redirect contract missing: {$label}.";
}

foreach ([
    'Customer portal' => 'portal branding',
    'router.post("/logout")' => 'safe sign-out action',
    'user?.permissions.includes("admin.access")' => 'conditional Admin navigation',
    '<ThemeSwitcher />' => 'appearance control',
    '<LanguageSwitcher' => 'locale control',
] as $needle => $label) {
    if ($layout !== '' && ! str_contains($layout, $needle)) $errors[] = "Customer Portal layout missing: {$label}.";
}
foreach ([
    '<CustomerPortalLayout>' => 'dedicated portal layout',
    'Account profile' => 'account identity section',
    'Memberships' => 'membership section',
    'Recent orders' => 'orders section',
    'Invoices' => 'invoice section',
    'Subscriptions' => 'subscription section',
] as $needle => $label) {
    if ($page !== '' && ! str_contains($page, $needle)) $errors[] = "Customer Portal UI missing: {$label}.";
}
if ($page !== '' && preg_match('/<(button|input|select|textarea)\b/', $page) === 1) {
    $errors[] = 'Customer Portal dashboard must not bypass shared UI controls or introduce mutation controls in the read-only first workflow.';
}

foreach ([
    'test_guest_is_redirected_to_login' => 'guest protection regression',
    'test_standard_user_login_goes_to_customer_portal_instead_of_admin' => 'ordinary-user login destination regression',
    'test_portal_exposes_only_current_users_linked_customer_and_memberships' => 'identity isolation regression',
    'test_membership_only_user_gets_safe_empty_commerce_state' => 'membership-only empty Commerce state regression',
    "->component('Portal/Dashboard')" => 'real portal component assertion',
    "->where('customer.email', 'portal-customer@example.test')" => 'explicit linked-customer assertion',
    "->where('memberships.0.plan', 'Portal Plan')" => 'current-user membership assertion',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) $errors[] = "Customer Portal acceptance contract missing: {$label}.";
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Customer Portal Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(STDOUT, '[Nexora Customer Portal Product Contract] PASS — verified ordinary users land on a read-only tenant-safe self-service portal backed only by explicit User → CommerceCustomer/Membership identity, while Admin routing remains intact.'.PHP_EOL);
