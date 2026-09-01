<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required DEV-4 source file missing: {$relative}";
        return '';
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read DEV-4 source file: {$relative}";
        return '';
    }

    return $contents;
};

$routes = $read('routes/web.php');

$routeContracts = [
    "Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');" => 'login GET route',
    "Route::post('/login', [AuthenticatedSessionController::class, 'store'])" => 'login POST route',
    "Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');" => 'registration GET route',
    "Route::post('/register', [RegisteredUserController::class, 'store'])" => 'registration POST route',
    "Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');" => 'forgot-password GET route',
    "Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])" => 'forgot-password POST route',
    "Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');" => 'password reset token route',
    "Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');" => 'logout route',
    "Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin', EnsureTenantRouteBinding::class])" => 'protected admin route group',
    "Route::get('/', DashboardController::class)->name('dashboard');" => 'admin dashboard route',
    "Route::get('/users', [UserController::class, 'index'])" => 'users route',
    "Route::get('/roles', [RoleController::class, 'index'])" => 'roles route',
    "Route::get('/profile', [ProfileController::class, 'edit'])" => 'profile route',
    "Route::get('/settings', [SettingsController::class, 'edit'])" => 'settings route',
    "Route::get('/media', [AdminMediaController::class, 'index'])" => 'media route',
    "Route::get('/appearance/themes', [ThemeController::class, 'index'])" => 'themes route',
    "Route::post('/appearance/themes/install', [ThemeController::class, 'install'])" => 'theme upload/install route',
    "Route::get('/extensions', [ExtensionController::class, 'index'])" => 'extensions route',
    "Route::post('/extensions/install/{artifact}', [ExtensionController::class, 'install'])" => 'extension install route',
    "Route::get('/studio', [StudioController::class, 'index'])" => 'Studio route',
];

foreach ($routeContracts as $needle => $label) {
    if (! str_contains($routes, $needle)) {
        $errors[] = "DEV-4 route contract missing: {$label}.";
    }
}

$controllerContracts = [
    'app/Http/Controllers/Auth/AuthenticatedSessionController.php' => ['create', 'store', 'destroy'],
    'app/Http/Controllers/Auth/RegisteredUserController.php' => ['create', 'store'],
    'app/Http/Controllers/Auth/PasswordResetLinkController.php' => ['create', 'store'],
    'app/Http/Controllers/Auth/NewPasswordController.php' => ['create', 'store'],
    'app/Http/Controllers/Admin/UserController.php' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'bulk'],
    'app/Http/Controllers/Admin/RoleController.php' => ['index', 'create', 'store', 'edit', 'update', 'destroy'],
    'app/Http/Controllers/Admin/ProfileController.php' => ['edit', 'update', 'password', 'destroyOtherSessions'],
    'app/Http/Controllers/Admin/SettingsController.php' => ['edit', 'update'],
    'app/Http/Controllers/Admin/Media/MediaController.php' => ['index', 'upload', 'update', 'destroy', 'restore', 'forceDelete'],
    'app/Http/Controllers/Admin/Appearance/ThemeController.php' => ['index', 'install', 'activate', 'preview', 'rollback', 'updateTokens'],
    'app/Http/Controllers/Admin/Extensions/ExtensionController.php' => ['index', 'show', 'install', 'capabilities', 'enable', 'disable', 'rollback', 'uninstall'],
    'app/Http/Controllers/Admin/Studio/StudioController.php' => ['index', 'store', 'edit', 'update', 'publish', 'unpublish', 'component', 'destroy'],
];

foreach ($controllerContracts as $relative => $methods) {
    $source = $read($relative);
    foreach ($methods as $method) {
        if ($source !== '' && preg_match('/function\s+'.preg_quote($method, '/').'\s*\(/', $source) !== 1) {
            $errors[] = "DEV-4 controller method missing: {$relative}::{$method}().";
        }
    }
}

$pageContracts = [
    'resources/js/admin/pages/Auth/Login.tsx',
    'resources/js/admin/pages/Auth/Register.tsx',
    'resources/js/admin/pages/Auth/ForgotPassword.tsx',
    'resources/js/admin/pages/Auth/ResetPassword.tsx',
    'resources/js/admin/pages/Auth/VerifyEmail.tsx',
    'resources/js/admin/pages/Admin/Dashboard.tsx',
    'resources/js/admin/pages/Admin/Users/Index.tsx',
    'resources/js/admin/pages/Admin/Users/Form.tsx',
    'resources/js/admin/pages/Admin/Roles/Index.tsx',
    'resources/js/admin/pages/Admin/Roles/Form.tsx',
    'resources/js/admin/pages/Admin/Profile/Edit.tsx',
    'resources/js/admin/pages/Admin/Settings/Index.tsx',
    'resources/js/admin/pages/Admin/Media/Index.tsx',
    'resources/js/admin/pages/Admin/Appearance/Themes.tsx',
    'resources/js/admin/pages/Admin/Extensions/Index.tsx',
    'resources/js/admin/pages/Admin/Extensions/Show.tsx',
    'resources/js/admin/pages/Admin/Studio/Index.tsx',
    'resources/js/admin/pages/Admin/Studio/Editor.tsx',
];

foreach ($pageContracts as $relative) {
    $read($relative);
}

$auth = $read('app/Http/Controllers/Auth/AuthenticatedSessionController.php');
foreach ([
    'rotateAuthenticatedSession($request)' => 'authenticated session rotation',
    "status !== 'active'" => 'inactive-account login rejection',
    "hasRole('super-admin')" => 'installer-owner super-admin recovery',
    "route('admin.dashboard')" => 'post-login dashboard redirect',
] as $needle => $label) {
    if ($auth !== '' && ! str_contains($auth, $needle)) {
        $errors[] = "DEV-4 authentication safety contract missing: {$label}.";
    }
}

$adminSourceRoot = $root.'/resources/js/admin/pages';
if (is_dir($adminSourceRoot)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminSourceRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile() || strtolower($file->getExtension()) !== 'tsx') {
            continue;
        }

        $source = (string) file_get_contents($file->getPathname());
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

        if (preg_match('/<(?:button|input|select|textarea)\b/', $source) === 1) {
            $errors[] = "Raw interactive HTML control found outside shared admin UI: {$relative}.";
        }

        if (preg_match('/<Button\b[^>]*>\s*<Icon\b[^>]*\/>\s*<\/Button>/s', $source) === 1) {
            $errors[] = "Icon-only Button must use IconButton with tooltip/label: {$relative}.";
        }
    }
}

$settingsController = $read('app/Http/Controllers/Admin/SettingsController.php');
$settingsPage = $read('resources/js/admin/pages/Admin/Settings/Index.tsx');
$sharedPageTypes = $read('resources/js/admin/types/page.ts');
foreach ([
    "app.logo_url" => 'site logo setting',
    "app.default_timezone" => 'default timezone setting',
    "app.default_locale" => 'default language setting',
] as $needle => $label) {
    if ($settingsController !== '' && ! str_contains($settingsController, $needle)) {
        $errors[] = "DEV-4 settings contract missing: {$label}.";
    }
}
foreach (['logoUrl', 'defaultTimezone', 'defaultLocale'] as $field) {
    if ($settingsPage !== '' && ! str_contains($settingsPage, $field)) {
        $errors[] = "DEV-4 settings UI field missing: {$field}.";
    }
    if ($sharedPageTypes !== '' && ! str_contains($sharedPageTypes, $field)) {
        $errors[] = "DEV-4 shared app prop missing: {$field}.";
    }
}

$mediaController = $read('app/Http/Controllers/Admin/Media/MediaController.php');
$mediaPicker = $read('resources/js/admin/components/MediaPicker.tsx');
foreach ([
    "request->boolean('picker')" => 'media picker request mode',
    "response()->json" => 'media picker JSON response',
] as $needle => $label) {
    if ($mediaController !== '' && ! str_contains($mediaController, $needle)) {
        $errors[] = "DEV-4 media reuse contract missing: {$label}.";
    }
}
foreach ([
    'picker: "1"' => 'MediaPicker JSON query mode',
    'Choose media' => 'MediaPicker chooser UI',
    'onChange(asset.url, asset)' => 'MediaPicker reusable selection callback',
] as $needle => $label) {
    if ($mediaPicker !== '' && ! str_contains($mediaPicker, $needle)) {
        $errors[] = "DEV-4 MediaPicker contract missing: {$label}.";
    }
}
if ($settingsPage !== '' && ! str_contains($settingsPage, '<MediaPicker')) {
    $errors[] = 'DEV-4 settings logo must consume the reusable MediaPicker component.';
}

$uiIndex = $read('resources/js/admin/ui/index.ts');
foreach (['Button', 'IconButton', 'Input', 'Select', 'Textarea', 'Checkbox', 'Tooltip'] as $export) {
    if ($uiIndex !== '' && ! str_contains($uiIndex, $export)) {
        $errors[] = "Shared admin UI export missing for DEV-4: {$export}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora DEV-4 Core Functional Contract] FAILED\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora DEV-4 Core Functional Contract] PASS — auth/admin routes, controller surfaces, site settings, reusable media selection and shared UI interaction boundaries are source-aligned.\n");
