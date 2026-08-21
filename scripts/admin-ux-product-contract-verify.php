<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/browser-ux-contracts.php';

$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Admin UX source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Admin UX source file: {$relative}";
        return '';
    }
    return $contents;
};

$app = $read('resources/js/app.tsx');
$layout = $read('resources/js/admin/layout/AdminLayout.tsx');
$organization = $read('resources/js/admin/components/OrganizationSwitcher.tsx');
$language = $read('resources/js/admin/components/LanguageSwitcher.tsx');
$theme = $read('resources/js/admin/components/ThemeSwitcher.tsx');
$toast = $read('resources/js/admin/providers/ToastProvider.tsx');
$select = $read('resources/js/admin/ui/untitled/select.tsx');
$pageHeader = $read('resources/js/admin/components/PageHeader.tsx');

foreach ([
    '<ThemeProvider appearance={props.appearance}>' => 'global appearance provider',
    '<ToastProvider flash={props.flash}>' => 'global flash notification provider',
    '<RouteProgress />' => 'global route progress feedback',
] as $needle => $label) {
    if ($app !== '' && ! str_contains($app, $needle)) $errors[] = "Admin app contract missing: {$label}.";
}

foreach ([
    'href="#nexora-main-content"' => 'skip link',
    'window.localStorage.setItem(SIDEBAR_KEY' => 'persistent sidebar collapse state',
    'tooltipPlacement={tooltipPlacement}' => 'collapsed navigation tooltip placement',
    'OrganizationSwitcher enterprise={props.enterprise} label' => 'mobile organization switching',
    'LanguageSwitcher localization={props.localization} label' => 'mobile language switching',
    'className="hidden min-w-48 lg:grid"' => 'desktop organization switcher breakpoint',
    'className="hidden w-44 sm:grid"' => 'responsive header language switcher',
    'OverlayDismiss aria-label="Close navigation"' => 'accessible mobile navigation dismissal',
] as $needle => $label) {
    if ($layout !== '' && ! str_contains($layout, $needle)) $errors[] = "Admin layout contract missing: {$label}.";
}

if ($organization !== '') {
    foreach (['className?: string', 'label?: boolean', 'label={label ? "Organization" : undefined}', 'cx("min-w-0", className)'] as $needle) {
        if (! str_contains($organization, $needle)) $errors[] = "Organization switcher responsive contract missing: {$needle}.";
    }
    if (str_contains($organization, 'hidden min-w-48 lg:block')) $errors[] = 'OrganizationSwitcher must not hard-code desktop-only visibility.';
}

if ($language !== '') {
    foreach (['className?: string', 'label?: boolean', 'locale.flagUrl', 'loading="lazy"', 'decoding="async"'] as $needle) {
        if (! str_contains($language, $needle)) $errors[] = "Language switcher contract missing: {$needle}.";
    }
}

foreach ([
    '{ value: "system"' => 'System appearance option',
    '{ value: "light"' => 'Light appearance option',
    '{ value: "dark"' => 'Dark appearance option',
    '<IconButton label={label}>' => 'icon-button tooltip/label trigger',
] as $needle => $label) {
    if ($theme !== '' && ! str_contains($theme, $needle)) $errors[] = "Theme switcher contract missing: {$label}.";
}

foreach ([
    '<IconButton' => 'shared dismiss button',
    'label="Dismiss notification"' => 'accessible manual toast dismissal',
    '<ToastIcon kind={toast.kind} />' => 'canonical notification icon',
    'aria-live=' => 'live-region feedback',
    'aria-atomic="true"' => 'atomic live notification',
    'w-[min(24rem,calc(100vw-2rem))]' => 'mobile-safe toast width',
] as $needle => $label) {
    if ($toast !== '' && ! str_contains($toast, $needle)) $errors[] = "Toast contract missing: {$label}.";
}
if ($toast !== '' && str_contains($toast, '●')) $errors[] = 'ToastProvider must use the canonical icon layer instead of a literal status glyph.';

foreach ([
    'isInvalid={Boolean(error)}' => 'select invalid state',
    'slot="errorMessage"' => 'select error-message slot',
    'role="alert"' => 'select error announcement',
    'data-[invalid]:border-[var(--nx-danger)]' => 'select invalid visual state',
] as $needle => $label) {
    if ($select !== '' && ! str_contains($select, $needle)) $errors[] = "Select accessibility contract missing: {$label}.";
}

foreach (['flex flex-col gap-4', 'flex shrink-0 flex-wrap items-center gap-2'] as $needle) {
    if ($pageHeader !== '' && ! str_contains($pageHeader, $needle)) $errors[] = "PageHeader responsive actions contract missing: {$needle}.";
}

$browser = nexoraAnalyzeBrowserUxContracts($root);
foreach ($browser['errors'] as $error) $errors[] = 'Browser/UX base contract: '.$error;

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Admin UX Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Admin UX Product Contract] PASS — responsive navigation/tenant/language controls, appearance, global feedback, select validation, shared interaction primitives and base browser accessibility contracts are aligned.'.PHP_EOL,
);
