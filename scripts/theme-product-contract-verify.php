<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Theme Product source file missing: {$relative}";
        return '';
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Theme Product source file: {$relative}";
        return '';
    }

    return $contents;
};

$routes = $read('routes/web.php');
$controller = $read('app/Http/Controllers/Admin/Appearance/ThemeController.php');
$installer = $read('app/Nexora/Themes/Services/ThemePackageInstaller.php');
$manager = $read('app/Nexora/Themes/Services/ThemeManager.php');
$page = $read('resources/js/admin/pages/Admin/Appearance/Themes.tsx');
$filePicker = $read('resources/js/admin/ui/untitled/file-picker.tsx');
$test = $read('tests/Feature/Themes/ThemeEngineFlowTest.php');

foreach ([
    "Route::post('/appearance/themes/install', [ThemeController::class, 'install'])" => 'theme install route',
    "Route::post('/appearance/themes/versions/{version}/activate', [ThemeController::class, 'activate'])" => 'theme activation route',
    "Route::post('/appearance/themes/versions/{version}/preview', [ThemeController::class, 'preview'])" => 'theme preview route',
    "Route::post('/appearance/themes/rollback', [ThemeController::class, 'rollback'])" => 'theme rollback route',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Theme Product route contract missing: {$label}.";
    }
}

foreach ([
    '$this->quarantine->store(' => 'quarantine-before-scan boundary',
    '$this->scans->scan(' => 'Sentinel scan before installation',
    "if (\$scan->decision !== 'allow')" => 'Sentinel ALLOW requirement',
    '$this->installer->install(' => 'ThemePackageInstaller promotion',
    '$this->themes->createPreviewToken(' => 'private preview token generation',
    '$this->themes->activate(' => 'atomic activation path',
    '$this->themes->rollback(' => 'activation rollback path',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) {
        $errors[] = "Theme Product controller contract missing: {$label}.";
    }
}

foreach ([
    "if (\$scan->quarantine_package_id !== \$package->id || \$scan->decision !== 'allow' || \$scan->status !== 'completed')" => 'completed Sentinel ALLOW promotion gate',
    "if (\$manifest->engine !== 'nexora-safe-html')" => 'non-executable safe theme engine boundary',
    "foreach (['{{ nx_head }}', '{{ nx_theme_assets }}', '{{ nx_schema }}', '{{ nx_content }}'] as \$requiredSlot)" => 'required SEO/schema/content platform slots',
    'Theme archive changed after Sentinel approval.' => 'post-scan archive digest immutability',
] as $needle => $label) {
    if ($installer !== '' && ! str_contains($installer, $needle)) {
        $errors[] = "Theme Product installer contract missing: {$label}.";
    }
}

foreach ([
    'public function assertRuntimeIntegrity(ThemeVersion $version): void' => 'runtime integrity verifier',
    '$this->assertRuntimeIntegrity($version);' => 'activation/preview runtime integrity enforcement',
    'previous_theme_version_id' => 'rollback activation snapshot',
] as $needle => $label) {
    if ($manager !== '' && ! str_contains($manager, $needle)) {
        $errors[] = "Theme Product manager contract missing: {$label}.";
    }
}

foreach ([
    'Scan & install theme' => 'install UX',
    'error={upload.errors.package}' => 'theme package validation error UX',
    'setPreviewError(' => 'preview failure state',
    'Theme preview failed' => 'visible preview error UX',
    'Rollback theme' => 'rollback UX',
    'Save design tokens' => 'design token workflow',
] as $needle => $label) {
    if ($page !== '' && ! str_contains($page, $needle)) {
        $errors[] = "Theme Product UI contract missing: {$label}.";
    }
}

foreach ([
    'error?: string;' => 'shared FilePicker error API',
    'aria-invalid={Boolean(error)}' => 'FilePicker accessibility error state',
    'role="alert"' => 'FilePicker announced error text',
] as $needle => $label) {
    if ($filePicker !== '' && ! str_contains($filePicker, $needle)) {
        $errors[] = "Shared FilePicker contract missing: {$label}.";
    }
}

foreach ([
    'test_uploaded_safe_theme_is_scanned_installed_previewed_activated_and_rolled_back' => 'end-to-end theme acceptance test',
    '/admin/appearance/themes/install' => 'theme test install request',
    '/preview' => 'theme test preview request',
    '/activate' => 'theme test activate request',
    '/admin/appearance/themes/rollback' => 'theme test rollback request',
    "'decision' => 'allow'" => 'theme test Sentinel evidence assertion',
    'Acme E2E Theme Shell' => 'theme test public render assertion',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Theme Product acceptance-test contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Theme Product Contract] FAILED\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Theme Product Contract] PASS — upload/quarantine/Sentinel/install/preview/activate/rollback/token UX and end-to-end acceptance-test source are aligned.\n");
