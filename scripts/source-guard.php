<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$sourceOnly = in_array('--source-only', $argv ?? [], true);

function nexoraFiles(string $root, array $extensions): iterable
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), $extensions, true)) {
            continue;
        }
        yield $file->getPathname();
    }
}

foreach (['app', 'bootstrap', 'config', 'database', 'routes'] as $directory) {
    foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.$directory, ['php']) as $path) {
        $source = (string) file_get_contents($path);
        if (preg_match('/\bbootstrap_path\s*\(/', $source) === 1) {
            $errors[] = "bootstrap_path() is unavailable in this Laravel 13 project during early bootstrap; use an application-independent project path: {$path}";
        }
    }
}

$controllers = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers';
foreach (nexoraFiles($controllers, ['php']) as $path) {
    $source = (string) file_get_contents($path);
    if (preg_match('/\breadonly\s+class\s+\w+\s+extends\s+Controller\b/', $source) === 1 || preg_match('/\bfinal\s+readonly\s+class\s+\w+\s+extends\s+Controller\b/', $source) === 1) {
        $errors[] = "Laravel controllers extend a non-readonly base Controller and must not be declared readonly: {$path}";
    }
}

$installerRoot = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Installation';
foreach (nexoraFiles($installerRoot, ['php']) as $path) {
    $source = (string) file_get_contents($path);
    if (preg_match('/(?<!->)(?<!::)\b(?:shell_exec|exec|system|passthru|proc_open|popen)\s*\(/', $source) === 1 || str_contains($source, 'Process::run(')) {
        $errors[] = "Browser installation services must never execute shell/package-manager commands: {$path}";
    }
}

foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations', ['php']) as $path) {
    $source = (string) file_get_contents($path);
    if (preg_match('/Schema::create\([\'\"](?:phase|milestone)_/i', $source) === 1) {
        $errors[] = "Forbidden phase/milestone table naming: {$path}";
    }
}

$pages = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages';
foreach (nexoraFiles($pages, ['ts', 'tsx']) as $path) {
    $source = (string) file_get_contents($path);
    if (str_contains($source, '@untitledui/') || str_contains($source, '@admin/ui/untitled') || str_contains($source, '/ui/untitled/')) {
        $errors[] = "Admin feature bypasses @nexora/admin-ui abstraction: {$path}";
    }
    if (preg_match('/\b(?:window\.)?confirm\s*\(/', $source) === 1) {
        $errors[] = "Native confirm() is forbidden in premium admin features; use ConfirmDialog: {$path}";
    }
}




$viewConfig = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'view.php';
if (! is_file($viewConfig)) {
    $errors[] = 'Missing explicit config/view.php; clean ZIP installs require a deterministic compiled-view path.';
} else {
    $viewSource = (string) file_get_contents($viewConfig);
    if (! str_contains(str_replace('\\', '/', $viewSource), 'storage/framework/views')) {
        $errors[] = 'config/view.php must use storage/framework/views as the compiled view path.';
    }
    if (preg_match('/\benv\s*\(/', $viewSource) === 1 || preg_match('/\b(?:storage_path|base_path|app)\s*\(/', $viewSource) === 1) {
        $errors[] = 'config/view.php must remain framework-helper independent for earliest bootstrap safety.';
    }
}

$runtimeBootstrap = $root.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'nexora-runtime-bootstrap.php';
if (! is_file($runtimeBootstrap)) {
    $errors[] = 'Missing pre-Laravel runtime bootstrap: bootstrap/nexora-runtime-bootstrap.php';
} else {
    $runtimeSource = (string) file_get_contents($runtimeBootstrap);
    foreach (['storage/framework/views', 'storage/framework/sessions', 'storage/framework/cache/data', 'storage/logs', 'bootstrap/cache'] as $requiredRuntimePath) {
        if (! str_contains(str_replace('\\', '/', $runtimeSource), $requiredRuntimePath)) {
            $errors[] = "Runtime bootstrap does not guarantee required path {$requiredRuntimePath}.";
        }
    }
}

foreach (['artisan', 'public/index.php'] as $entryPoint) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $entryPoint);
    $source = is_file($path) ? (string) file_get_contents($path) : '';
    if (! str_contains($source, 'nexora-runtime-bootstrap.php')) {
        $errors[] = "{$entryPoint} must execute the Nexora runtime bootstrap before Laravel boot.";
    }
}

$processEnvironmentBootstrap = $root.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'nexora-process-environment.php';
if (! is_file($processEnvironmentBootstrap)) {
    $errors[] = 'Missing framework-independent child-process environment bootstrap.';
} else {
    $processEnvironmentSource = (string) file_get_contents($processEnvironmentBootstrap);
    foreach (['COMPOSER_HOME', 'COMPOSER_CACHE_DIR', 'NPM_CONFIG_CACHE'] as $requiredEnvironment) {
        if (! str_contains($processEnvironmentSource, $requiredEnvironment)) {
            $errors[] = "Process environment bootstrap must define/fallback {$requiredEnvironment}.";
        }
    }
}

$bootstrapUi = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'nexora-bootstrap.php';
$publicIndex = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'index.php';
if (! is_file($bootstrapUi)) {
    $errors[] = 'Missing framework-independent deployment bootstrap: public/nexora-bootstrap.php';
} else {
    $bootstrapSource = (string) file_get_contents($bootstrapUi);
    foreach (['install --no-interaction --prefer-dist --optimize-autoloader', 'run build', 'nxInstallPrivateComposer', 'nxInstallPrivateNode'] as $fixedTask) {
        if (! str_contains($bootstrapSource, $fixedTask)) {
            $errors[] = "Deployment bootstrap is missing controlled task/capability: {$fixedTask}";
        }
    }
    foreach (['https://composer.github.io/installer.sig', 'https://getcomposer.org/installer', 'https://nodejs.org/download/release/latest-v24.x/'] as $trustedUrl) {
        if (! str_contains($bootstrapSource, $trustedUrl)) {
            $errors[] = "Deployment bootstrap is missing trusted bootstrap endpoint: {$trustedUrl}";
        }
    }
    if (! str_contains($bootstrapSource, "defined('NEXORA_BOOTSTRAP_INTERNAL')")) {
        $errors[] = 'Direct access to public/nexora-bootstrap.php must be collapsed to the canonical site URL.';
    }
    if (! str_contains($bootstrapSource, 'nexora-process-environment.php')) {
        $errors[] = 'Deployment bootstrap must load the normalized child-process environment layer.';
    }
    foreach (['application/x-ndjson', 'nxStreamFixedCommand', 'nxStreamDeploymentTask', 'data-deployment-form', 'deployment-cancel', "'type' => 'heartbeat'", 'connection_aborted()'] as $observableDeploymentMarker) {
        if (! str_contains($bootstrapSource, $observableDeploymentMarker)) {
            $errors[] = "Deployment bootstrap is missing observable progress capability: {$observableDeploymentMarker}";
        }
    }
    if (str_contains($bootstrapSource, 'proc_open($command, $descriptors, $pipes, $cwd, null')) {
        $errors[] = 'Deployment bootstrap must never launch Composer/Node with an unnormalized null environment.';
    }
    if (preg_match('/proc_open\s*\(\s*\$_(?:POST|GET|REQUEST)/', $bootstrapSource) === 1) {
        $errors[] = 'Deployment bootstrap must never pass request input directly to proc_open().';
    }
    if (preg_match('/(?:shell_exec|exec|system|passthru)\s*\(\s*\$_(?:POST|GET|REQUEST)/', $bootstrapSource) === 1) {
        $errors[] = 'Deployment bootstrap must never pass request input to a shell execution primitive.';
    }
}

$indexSource = is_file($publicIndex) ? (string) file_get_contents($publicIndex) : '';
if (! str_contains($indexSource, "define('NEXORA_BOOTSTRAP_INTERNAL', true)")) {
    $errors[] = 'public/index.php must render deployment preparation internally at the canonical domain URL.';
}
if (str_contains($indexSource, "Location: /nexora-bootstrap.php")) {
    $errors[] = 'public/index.php must not expose nexora-bootstrap.php in the customer URL.';
}

foreach ([
    'storage/framework/cache/.gitkeep',
    'storage/framework/cache/data/.gitkeep',
    'storage/framework/sessions/.gitkeep',
    'storage/framework/views/.gitkeep',
    'storage/logs/.gitkeep',
    'storage/app/public/.gitkeep',
] as $marker) {
    if (! is_file($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $marker))) {
        $errors[] = "Missing runtime directory marker: {$marker}";
    }
}

$installerController = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Install'.DIRECTORY_SEPARATOR.'InstallerController.php';
$installerView = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'index.blade.php';
$routesFile = $root.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';
foreach ([
    [$installerController, 'application/x-ndjson', 'Main installer must expose streamed progress.'],
    [$installerView, 'install-progress', 'Main installer UI must render installation progress.'],
    [$installerView, "route('install.stream')", 'Main installer UI must call the streamed install endpoint.'],
    [$routesFile, "Route::post('/stream'", 'Main installer streamed route is missing.'],
] as [$path, $required, $message]) {
    $contents = is_file($path) ? (string) file_get_contents($path) : '';
    if (! str_contains($contents, $required)) {
        $errors[] = $message;
    }
}


// N0.11 installation-environment and premium-brand regression gates.
$envExample = $root.DIRECTORY_SEPARATOR.'.env.example';
if (! is_file($envExample) || filesize($envExample) === 0) {
    $errors[] = '.env.example must exist and be non-empty in every source/release package.';
}

$installerBootstrap = $root.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'nexora-installer-bootstrap.php';
if (! is_file($installerBootstrap)) {
    $errors[] = 'Missing bootstrap/nexora-installer-bootstrap.php.';
} else {
    $installerBootstrapSource = (string) file_get_contents($installerBootstrap);
    if (str_contains($installerBootstrapSource, 'cannot create or write the .env file')) {
        $errors[] = 'Installer bootstrap must not hard-fail merely because the project-root .env is not writable.';
    }
    foreach (['storage/app/nexora/environment', 'NEXORA_ENV_FALLBACK_PATH', 'NEXORA_ENV_ACTIVE_MODE', "'fallback'", "'root'"] as $requiredMarker) {
        if (! str_contains($installerBootstrapSource, $requiredMarker)) {
            $errors[] = "Installer bootstrap is missing protected environment fallback/active-location handling: {$requiredMarker}";
        }
    }
}

foreach (['artisan', 'public/index.php'] as $entryPoint) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $entryPoint);
    $source = is_file($path) ? (string) file_get_contents($path) : '';
    if (! str_contains($source, 'nexora-installer-bootstrap.php')) {
        $errors[] = "{$entryPoint} must execute the installer environment bootstrap before Laravel boot.";
    }
}

$environmentWriter = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Installation'.DIRECTORY_SEPARATOR.'EnvironmentWriter.php';
$environmentWriterSource = is_file($environmentWriter) ? (string) file_get_contents($environmentWriter) : '';
foreach (['environment_fallback_path', 'environment_marker_path', 'writeActiveMarker'] as $requiredEnvironmentWriterMarker) {
    if (! str_contains($environmentWriterSource, $requiredEnvironmentWriterMarker)) {
        $errors[] = "EnvironmentWriter is missing resilient environment persistence capability: {$requiredEnvironmentWriterMarker}";
    }
}

foreach ([
    'public/brand/nexora-mark.svg',
    'public/brand/nexora-logo.svg',
    'public/favicon.svg',
    'public/favicon.ico',
    'public/favicon-32x32.png',
    'public/apple-touch-icon.png',
    'public/site.webmanifest',
] as $brandArtifact) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $brandArtifact);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing or empty Nexora brand artifact: {$brandArtifact}";
    }
}

$packageJsonPath = $root.DIRECTORY_SEPARATOR.'package.json';
$packageJsonSource = is_file($packageJsonPath) ? (string) file_get_contents($packageJsonPath) : '';
if (! str_contains($packageJsonSource, '"lucide-react"')) {
    $errors[] = 'Admin UI must use lucide-react as the canonical icon library.';
}
if (str_contains($packageJsonSource, '"@untitledui/icons"')) {
    $errors[] = 'Legacy @untitledui/icons dependency must not coexist with the canonical lucide-react icon layer.';
}

$adminIcon = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'Icon.tsx';
$adminIconSource = is_file($adminIcon) ? (string) file_get_contents($adminIcon) : '';
if (! str_contains($adminIconSource, 'lucide-react')) {
    $errors[] = 'Nexora Admin Icon compatibility layer must be backed by lucide-react.';
}

$installerViewSource = is_file($installerView) ? (string) file_get_contents($installerView) : '';
foreach (['/brand/nexora-mark.svg', '<x-lucide', 'install-stage-icon'] as $premiumInstallerMarker) {
    if (! str_contains($installerViewSource, $premiumInstallerMarker)) {
        $errors[] = "Premium installer UI is missing required branded/icon capability: {$premiumInstallerMarker}";
    }
}
if (preg_match('/<x-lucide-[a-z0-9-]+/i', $installerViewSource) === 1) {
    $errors[] = 'Installer must use the shared <x-lucide name="…"> component; x-lucide-* aliases are not registered Blade components.';
}
if (preg_match_all('/<x-([A-Za-z0-9_.-]+)/', $installerViewSource, $installerComponentMatches) > 0) {
    foreach (array_unique($installerComponentMatches[1]) as $installerComponentName) {
        if ($installerComponentName === 'slot') {
            continue;
        }
        $componentRelative = 'resources/views/components/'.str_replace('.', '/', $installerComponentName).'.blade.php';
        if (! is_file($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $componentRelative))) {
            $errors[] = "Installer references an unresolved Blade component [{$installerComponentName}] at [{$componentRelative}].";
        }
    }
}

$releaseBuilder = $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'build-production-release.php';
$releaseBuilderSource = is_file($releaseBuilder) ? (string) file_get_contents($releaseBuilder) : '';
$releasePolicyPath = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora-release.php';
$releasePolicySource = is_file($releasePolicyPath) ? (string) file_get_contents($releasePolicyPath) : '';
if (! str_contains(str_replace('\\', '/', $releaseBuilderSource), 'config/nexora-release.php') || ! str_contains(str_replace('\\', '/', $releasePolicySource), 'storage/app/nexora/environment/')) {
    $errors[] = 'Production release policy must exclude protected installer environment state.';
}

if (! $sourceOnly && ! is_file($root.DIRECTORY_SEPARATOR.'composer.lock')) {
    $errors[] = 'composer.lock is missing. Use the explicit maintainer lock-refresh workflow and review/commit the lock before certification.';
}
if (! $sourceOnly && ! is_file($root.DIRECTORY_SEPARATOR.'package-lock.json')) {
    $errors[] = 'package-lock.json is missing. Use the explicit maintainer lock-refresh workflow and review/commit the lock before certification.';
}

if (! $sourceOnly && ! class_exists(ZipArchive::class)) {
    $errors[] = 'PHP ext-zip is required by Nexora Sentinel but ZipArchive is unavailable.';
}


// N0.12 deployment recovery, localization and premium upload regression gates.
foreach (['cancel_stream', 'deployment_status', 'nxDeploymentCancellationRequested', 'deployment-control', 'active_run_id', 'run_id'] as $recoveryMarker) {
    if (! str_contains($bootstrapSource ?? '', $recoveryMarker)) {
        $errors[] = "Deployment bootstrap is missing cancellation/recovery capability: {$recoveryMarker}";
    }
}
foreach (['bootstrap/nexora-locales.php', 'config/localization.php', 'app/Http/Middleware/SetLocale.php', 'app/Http/Controllers/LocaleController.php', 'resources/js/admin/components/LanguageSwitcher.tsx'] as $localizationArtifact) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $localizationArtifact);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing localization foundation artifact: {$localizationArtifact}";
    }
}
if (! str_contains($bootstrapSource ?? '', 'upload-surface') || ! str_contains($bootstrapSource ?? '', 'release-dropzone')) {
    $errors[] = 'Prebuilt release upload must use the Nexora premium file picker/dropzone rather than a visible native file input.';
}
$localizationConfig = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'localization.php';
$localizationSource = is_file($localizationConfig) ? (string) file_get_contents($localizationConfig) : '';
foreach (['en', 'ur', 'tr', 'ar', 'ru'] as $requiredLocale) {
    if (! str_contains($localizationSource, "'{$requiredLocale}'")) {
        $errors[] = "Localization config is missing required starter locale: {$requiredLocale}";
    }
}


// N0.13 zero-test stabilization, identity UX and database safety gates.
$appEntry = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'app.tsx';
$appEntrySource = is_file($appEntry) ? (string) file_get_contents($appEntry) : '';
foreach (['pages:', 'withApp(app', 'strictMode: true'] as $inertiaV3Marker) {
    if (! str_contains($appEntrySource, $inertiaV3Marker)) {
        $errors[] = "React/Inertia application bootstrap is missing the Inertia v3 setup marker: {$inertiaV3Marker}";
    }
}
if (str_contains($appEntrySource, 'resolvePageComponent')) {
    $errors[] = 'Legacy manual resolvePageComponent setup is forbidden with the Nexora Inertia v3 application bootstrap.';
}
foreach (['ThemeProvider.tsx', 'ToastProvider.tsx'] as $providerFile) {
    $providerPath = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'providers'.DIRECTORY_SEPARATOR.$providerFile;
    $providerSource = is_file($providerPath) ? (string) file_get_contents($providerPath) : '';
    if (str_contains($providerSource, 'usePage(')) {
        $errors[] = "{$providerFile} is wrapped outside the Inertia page component and must receive shared props explicitly instead of calling usePage().";
    }
}
if (str_contains($bootstrapSource ?? '', 'name="db_host"') || str_contains($bootstrapSource ?? '', 'name="db_password"')) {
    $errors[] = 'Deployment preparation must not request application database credentials; database configuration belongs to the Laravel installer Database step.';
}
foreach ([
    'app/Nexora/Installation/DatabaseBackupManager.php',
    'app/Nexora/Security/Password/PasswordStrengthEvaluator.php',
] as $stabilizationArtifact) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $stabilizationArtifact);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.13 stabilization artifact: {$stabilizationArtifact}";
    }
}
foreach (["route('install.database.backup.stream')", 'db_reset_existing', 'password_strength_consent', 'data-password-toggle', 'name="language"'] as $installerUxMarker) {
    if (! str_contains($installerViewSource, $installerUxMarker)) {
        $errors[] = "Installer is missing N0.13 database/identity UX capability: {$installerUxMarker}";
    }
}
$backupManager = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Installation'.DIRECTORY_SEPARATOR.'DatabaseBackupManager.php';
$backupManagerSource = is_file($backupManager) ? (string) file_get_contents($backupManager) : '';
if (! str_contains($backupManagerSource, "'downloaded_at'")) {
    $errors[] = 'Existing-database reset must require a server-recorded backup download before destructive installation.';
}


// N0.14 database portability, optional backup consent, and installer cancellation gates.
foreach ([
    'app/Nexora/Installation/Database/DatabaseDriverRegistry.php',
    'app/Nexora/Installation/InstallationRunControl.php',
    'app/Nexora/Installation/Exceptions/InstallationCancelledException.php',
] as $artifact) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.14 installer portability artifact: {$artifact}";
    }
}
foreach (['mysql', 'mariadb', 'pgsql', 'sqlite', 'sqlsrv'] as $driver) {
    $registryPath = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Installation'.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'DatabaseDriverRegistry.php';
    $registrySource = is_file($registryPath) ? (string) file_get_contents($registryPath) : '';
    if (! str_contains($registrySource, "'{$driver}'")) {
        $errors[] = "Database driver registry is missing Laravel first-party driver: {$driver}";
    }
}
foreach (['name="db_driver"', 'db_skip_backup_consent', 'db_skip_backup_database', "route('install.cancel')", 'cancel-install'] as $marker14) {
    if (! str_contains($installerViewSource, $marker14)) {
        $errors[] = "Installer is missing N0.14 database/cancellation UX capability: {$marker14}";
    }
}
$routesPath = $root.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';
$routesSource = is_file($routesPath) ? (string) file_get_contents($routesPath) : '';
if (str_contains($routesSource, "throttle:4,10")) {
    $errors[] = 'Database backup stream still uses the old overly aggressive throttle that caused HTTP 429 during installation.';
}
if (! str_contains($routesSource, "throttle:12,1") || ! str_contains($routesSource, "'cancel'")) {
    $errors[] = 'Installer routes are missing the N0.14 backup allowance or cancellation endpoint.';
}
$installerConfigPath = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'installer.php';
$installerConfigSource = is_file($installerConfigPath) ? (string) file_get_contents($installerConfigPath) : '';
if (str_contains($installerConfigSource, "'pdo_mysql'")) {
    $errors[] = 'System readiness must not hard-require pdo_mysql now that the installer supports multiple database drivers.';
}
foreach (glob($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*.php') ?: [] as $migrationPath) {
    if (str_contains((string) file_get_contents($migrationPath), '->after(')) {
        $errors[] = 'Database-portable migrations must not use MySQL-only after() column modifiers: '.basename($migrationPath);
    }
}


// N0.15 data-connections, premium-select and installer-owner onboarding gates.
foreach ([
    'app/Nexora/Data/ConnectionCatalog.php',
    'app/Nexora/Data/ConnectionTester.php',
    'app/Http/Controllers/Admin/Data/DataConnectionController.php',
    'app/Models/DataConnection.php',
    'resources/js/admin/pages/Admin/Data/Connections.tsx',
    'database/migrations/2026_08_15_000500_add_nexora_data_connections.php',
] as $artifact15) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact15);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.15 data-connection artifact: {$artifact15}";
    }
}
$driverRegistry15 = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Installation'.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'DatabaseDriverRegistry.php';
$driverRegistry15Source = is_file($driverRegistry15) ? (string) file_get_contents($driverRegistry15) : '';
foreach (['aws_rds_mysql', 'aws_rds_pgsql', 'aws_aurora_mysql', 'aws_aurora_pgsql'] as $awsDriver) {
    if (! str_contains($driverRegistry15Source, "'{$awsDriver}'")) {
        $errors[] = "N0.15 primary database registry is missing AWS managed preset: {$awsDriver}";
    }
}
$connectionCatalog15 = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Data'.DIRECTORY_SEPARATOR.'ConnectionCatalog.php';
$connectionCatalog15Source = is_file($connectionCatalog15) ? (string) file_get_contents($connectionCatalog15) : '';
foreach (['mongodb', 'mongodb_atlas', 'redis', 'aws_documentdb', 'aws_elasticache_redis', 'aws_dynamodb'] as $connector15) {
    if (! str_contains($connectionCatalog15Source, "'{$connector15}'")) {
        $errors[] = "N0.15 auxiliary data catalog is missing connector: {$connector15}";
    }
}
foreach (['<x-ui.select name="db_driver"', '<x-ui.select name="language"', 'Additional data services', 'db-driver-health'] as $installerSelectMarker15) {
    if (! str_contains($installerViewSource, $installerSelectMarker15)) {
        $errors[] = "N0.15 installer is missing premium database/language UX marker: {$installerSelectMarker15}";
    }
}
$installer15 = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Installation'.DIRECTORY_SEPARATOR.'Installer.php';
$installer15Source = is_file($installer15) ? (string) file_get_contents($installer15) : '';
if (! str_contains($installer15Source, "forceFill(['email_verified_at' => now()])")) {
    $errors[] = 'The first installer-created Super Admin must be explicitly email verified.';
}
$sessionController15 = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Auth'.DIRECTORY_SEPARATOR.'AuthenticatedSessionController.php';
$sessionController15Source = is_file($sessionController15) ? (string) file_get_contents($sessionController15) : '';
if (! str_contains($sessionController15Source, 'auth.installer_owner_verified')) {
    $errors[] = 'Installer-owner verification recovery is missing from the login flow.';
}
$select15 = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'ui'.DIRECTORY_SEPARATOR.'untitled'.DIRECTORY_SEPARATOR.'select.tsx';
if (! is_file($select15) || filesize($select15) === 0) {
    $errors[] = 'Premium React select abstraction is missing.';
}
foreach (nexoraFiles($pages, ['ts', 'tsx']) as $path) {
    if (preg_match('/<select\b/', (string) file_get_contents($path)) === 1) {
        $errors[] = "Admin feature page uses a native select instead of @nexora/admin-ui Select: {$path}";
    }
}
foreach (['us', 'pk', 'tr', 'sa', 'ru'] as $flag15) {
    $flagPath = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'brand'.DIRECTORY_SEPARATOR.'flags'.DIRECTORY_SEPARATOR.$flag15.'.svg';
    if (! is_file($flagPath) || filesize($flagPath) === 0) {
        $errors[] = "Missing local language flag asset: {$flag15}.svg";
    }
}


// N0.16 UI-library governance, stable database-driver keys, and Document Engine foundation.
$installerUiLibrary = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'installer'.DIRECTORY_SEPARATOR.'nexora-ui.js';
if (! is_file($installerUiLibrary) || filesize($installerUiLibrary) === 0) {
    $errors[] = 'Installer UI library asset is missing.';
}
if (preg_match('/<(button|input|select|textarea)\b/i', $installerViewSource) === 1) {
    $errors[] = 'Installer feature view contains raw interactive HTML. Use resources/views/components/ui library components instead.';
}
foreach (nexoraFiles($pages, ['ts', 'tsx']) as $path) {
    if (preg_match('/<(button|input|select|textarea)\b/', (string) file_get_contents($path)) === 1) {
        $errors[] = "Admin feature page contains a raw interactive control instead of @nexora/admin-ui: {$path}";
    }
}

foreach (nexoraFiles($pages, ['ts', 'tsx']) as $path) {
    $pageSource = (string) file_get_contents($path);
    if (preg_match('/import\s+\{[^}]*\bLink\b[^}]*\}\s+from\s+["\']@inertiajs\/react["\']/', $pageSource) === 1 || preg_match('/<Link\b/', $pageSource) === 1) {
        $errors[] = "Admin feature page imports Inertia Link directly. Use @nexora/admin-ui TextLink/ButtonLink/IconLink: {$path}";
    }
}

$installerController16 = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Install'.DIRECTORY_SEPARATOR.'InstallerController.php';
$installerController16Source = is_file($installerController16) ? (string) file_get_contents($installerController16) : '';
if (! str_contains($installerController16Source, "'value' => (string) \$driver['key']")) {
    $errors[] = 'Database selector options must submit the registry driver key instead of grouped collection indexes.';
}
if (str_contains($installerViewSource, "groupBy('group')")) {
    $errors[] = 'Installer must not derive database option values from a groupBy() loop because Collection grouping can reindex keys.';
}
foreach ([
    'app/Nexora/Modules/Core/DocumentEngineModule.php',
    'app/Nexora/Documents/Contracts/DocumentRepositoryContract.php',
    'app/Nexora/Documents/Types/DocumentTypeRegistry.php',
    'app/Nexora/Documents/Blocks/BlockRegistry.php',
    'app/Nexora/Documents/Services/DocumentRevisionManager.php',
    'app/Nexora/Documents/Services/DocumentContentValidator.php',
    'app/Models/Document.php',
    'app/Models/DocumentRevision.php',
    'app/Http/Controllers/Admin/Content/DocumentController.php',
    'resources/js/admin/pages/Admin/Documents/Index.tsx',
    'resources/js/admin/pages/Admin/Documents/Form.tsx',
    'database/migrations/2026_08_15_000600_add_nexora_document_engine.php',
] as $artifact16) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact16);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.16 Document Engine artifact: {$artifact16}";
    }
}
foreach (['content.documents.read', 'content.documents.write', 'content.revisions.read', 'content.revisions.write'] as $capability16) {
    $nexoraConfig16 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
    if (! str_contains($nexoraConfig16, "'{$capability16}'")) {
        $errors[] = "Document Engine capability is missing: {$capability16}";
    }
}

// N0.17 premium Admin shell + Writer foundation.
$adminRoot17 = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin';
foreach (nexoraFiles($adminRoot17, ['ts', 'tsx']) as $path) {
    $normalized = str_replace('\\', '/', $path);
    if (str_contains($normalized, '/ui/')) {
        continue;
    }
    $source = (string) file_get_contents($path);
    if (preg_match('/<(button|input|select|textarea)\b/', $source) === 1) {
        $errors[] = "Admin UI surface contains a raw interactive control outside @nexora/admin-ui: {$path}";
    }
    if (preg_match('/import\s+\{[^}]*\bLink\b[^}]*\}\s+from\s+["\']@inertiajs\/react["\']/', $source) === 1 || preg_match('/<Link\b/', $source) === 1) {
        $errors[] = "Admin UI surface imports Inertia Link directly outside @nexora/admin-ui: {$path}";
    }
}
$linkButton17 = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'ui'.DIRECTORY_SEPARATOR.'untitled'.DIRECTORY_SEPARATOR.'link-button.tsx';
$linkButton17Source = is_file($linkButton17) ? (string) file_get_contents($linkButton17) : '';
if (! str_contains($linkButton17Source, '"children" | "size"')) {
    $errors[] = 'ButtonLink must omit the conflicting InertiaLinkProps size field before declaring Nexora ButtonSize.';
}
foreach ([
    'resources/js/admin/components/ThemeSwitcher.tsx',
    'resources/js/admin/ui/untitled/tooltip.tsx',
    'resources/js/admin/ui/untitled/nav-link.tsx',
    'resources/js/admin/components/writer/BlockEditor.tsx',
    'app/Nexora/Modules/Core/WriterModule.php',
] as $artifact17) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact17);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.17 Admin/Writer artifact: {$artifact17}";
    }
}
$layout17 = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'layout'.DIRECTORY_SEPARATOR.'AdminLayout.tsx';
$layout17Source = is_file($layout17) ? (string) file_get_contents($layout17) : '';
if (! str_contains($layout17Source, 'nexora.admin.sidebar.collapsed') || ! str_contains($layout17Source, '<ThemeSwitcher')) {
    $errors[] = 'Admin shell must include persistent sidebar collapse and the Light/Dark/System switcher.';
}
$config17 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
if (! str_contains($config17, "'content.writer.use'") || ! str_contains($config17, 'WriterModule::class')) {
    $errors[] = 'Nexora Writer module/capability is missing from runtime configuration.';
}


// N0.18 editorial workflow, autosave and revision safety.
foreach ([
    'app/Nexora/Modules/Core/EditorialModule.php',
    'app/Nexora/Documents/Editorial/EditorialWorkflowRegistry.php',
    'app/Nexora/Documents/Services/DocumentAutosaveManager.php',
    'app/Nexora/Documents/Services/DocumentRevisionComparator.php',
    'app/Http/Controllers/Admin/Content/DocumentAutosaveController.php',
    'app/Http/Controllers/Admin/Content/DocumentRevisionController.php',
    'app/Http/Controllers/Admin/Content/DocumentReviewController.php',
    'resources/js/admin/pages/Admin/Documents/Revisions.tsx',
    'database/migrations/2026_08_15_000700_add_nexora_editorial_workflow.php',
] as $artifact18) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact18);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.18 Editorial artifact: {$artifact18}";
    }
}
$config18 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['content.editorial.review', 'content.autosave.write', 'EditorialModule::class'] as $marker18) {
    if (! str_contains($config18, $marker18)) {
        $errors[] = "N0.18 runtime registration is missing: {$marker18}";
    }
}
$routes18 = is_file($routesFile) ? (string) file_get_contents($routesFile) : '';
foreach (['documents.autosave', 'documents.revisions.index', 'documents.revisions.restore', 'documents.review-comments.store'] as $route18) {
    if (! str_contains($routes18, $route18)) {
        $errors[] = "N0.18 editorial route is missing: {$route18}";
    }
}


// N0.19 SEO Core, Schema Graph, sitemap, internal-link and external-roadmap boundaries.
foreach ([
    'app/Nexora/Modules/Core/SeoModule.php',
    'app/Nexora/Seo/Contracts/SeoRepositoryContract.php',
    'app/Nexora/Seo/Contracts/SeoManagerContract.php',
    'app/Nexora/Seo/Schema/SchemaGraph.php',
    'app/Nexora/Seo/Schema/SchemaGraphBuilder.php',
    'app/Nexora/Seo/Services/SeoAuditService.php',
    'app/Nexora/Seo/Services/InternalLinkAnalyzer.php',
    'app/Nexora/Seo/Sitemap/SitemapService.php',
    'app/Models/SeoEntry.php',
    'app/Models/SeoSchemaNode.php',
    'app/Models/SeoInternalLinkSuggestion.php',
    'app/Http/Controllers/Admin/Seo/SeoDashboardController.php',
    'app/Http/Controllers/Admin/Seo/DocumentSeoController.php',
    'resources/js/admin/pages/Admin/Seo/Index.tsx',
    'resources/js/admin/pages/Admin/Seo/Document.tsx',
    'resources/js/admin/pages/Admin/Seo/Settings.tsx',
    'database/migrations/2026_08_15_000800_add_nexora_seo_core.php',
    'tests/Unit/Seo/SchemaGraphTest.php',
    'tests/Feature/Seo/SeoCoreFlowTest.php',
    'tests/Architecture/N019SeoArchitectureTest.php',
] as $artifact19) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact19);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.19 SEO Core artifact: {$artifact19}";
    }
}
$config19 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['SeoModule::class', 'seo.metadata.read', 'seo.metadata.write', 'seo.schema.read', 'seo.schema.write', 'seo.sitemap.read', 'seo.links.analyze'] as $marker19) {
    if (! str_contains($config19, $marker19)) {
        $errors[] = "N0.19 runtime registration is missing: {$marker19}";
    }
}
$routes19 = is_file($routesFile) ? (string) file_get_contents($routesFile) : '';
foreach (['/sitemap.xml', "'/seo'", 'seo.documents.edit', 'seo.internal-links.refresh'] as $route19) {
    if (! str_contains($routes19, $route19)) {
        $errors[] = "N0.19 SEO route is missing: {$route19}";
    }
}
$plan19Path = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md';
$plan19 = is_file($plan19Path) ? (string) file_get_contents($plan19Path) : '';
foreach (['EXT-B01', 'EXT-P01', 'EXT-L01', 'EXT-BK01', 'EXT-PR01'] as $externalPlanId) {
    if (! str_contains($plan19, $externalPlanId)) {
        $errors[] = "External roadmap boundary is missing: {$externalPlanId}";
    }
}
if (! str_contains($plan19, '| N0.19 |') || ! str_contains($plan19, '| DONE |')) {
    $errors[] = 'Master plan status must include N0.19 as DONE.';
}
if (str_contains($plan19, '| N0.23 | Book publishing, chapters, editions, EPUB/PDF/export foundations | PLANNED |')) {
    $errors[] = 'Books must not remain an internal planned Nexora base milestone; it is externalized in N0.19.';
}
if (str_contains($plan19, '| N0.24 | Profile/CV/Resume/Biography publishing and privacy controls | PLANNED |')) {
    $errors[] = 'CV/Profile must not remain an internal planned Nexora base milestone; it is externalized in N0.19.';
}

// N0.20 theme-engine security and architecture gates.
foreach ([
    'app/Nexora/Themes/Contracts/ThemeManagerContract.php',
    'app/Nexora/Themes/Contracts/ThemeRendererContract.php',
    'app/Nexora/Themes/Services/ThemePackageInstaller.php',
    'app/Nexora/Themes/Services/ThemeManifestValidator.php',
    'app/Nexora/Themes/Services/SafeThemeRenderer.php',
    'app/Nexora/Modules/Core/ThemeEngineModule.php',
    'resources/js/admin/pages/Admin/Appearance/Themes.tsx',
    'themes/nexora-base/theme.json',
    'themes/nexora-base/templates/home.html',
    'themes/nexora-base/templates/document.html',
] as $themeArtifact) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $themeArtifact);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.20 Theme Engine artifact: {$themeArtifact}";
    }
}
$themeInstallerPath = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Themes'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'ThemePackageInstaller.php';
$themeInstallerSource = is_file($themeInstallerPath) ? (string) file_get_contents($themeInstallerPath) : '';
foreach (["decision !== 'allow'", 'hash_equals', 'unsupported executable or undeclared file', 'nexora-safe-html'] as $themeSecurityMarker) {
    if (! str_contains($themeInstallerSource, $themeSecurityMarker)) {
        $errors[] = "Theme installer is missing N0.20 security boundary: {$themeSecurityMarker}";
    }
}
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'themes', ['php', 'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx']) as $path) {
    $errors[] = "Built-in N0.20 themes must remain non-executable: {$path}";
}

$plan20Path = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md';
$plan20 = is_file($plan20Path) ? (string) file_get_contents($plan20Path) : '';
if (! str_contains($plan20, '| N0.20 | Theme Engine + Design Tokens + theme install/preview/switch/rollback | DONE |')) {
    $errors[] = 'Master plan must mark N0.20 Theme Engine as DONE.';
}
if (! str_contains($plan20, '| N0.21 | Nexora Studio visual builder + responsive/dynamic data foundations | DONE |')) {
    $errors[] = 'Master plan must mark N0.21 Studio as DONE.';
}
if (! str_contains($plan20, '| N0.22 | Blog & Article publishing, authors, taxonomy, series, scheduling and archives | DONE |')) {
    $errors[] = 'Master plan must mark N0.22 Blog & Article publishing as DONE.';
}
if (! str_contains($plan20, '| N0.25 | Media, newsletter, syndication and distribution adapters | DONE |')) {
    $errors[] = 'Master plan must mark N0.25 Media/Newsletter/Distribution as DONE.';
}


// N0.21 Studio visual-builder architecture gates.
foreach ([
    'app/Nexora/Modules/Core/StudioModule.php',
    'app/Nexora/Studio/Contracts/StudioManagerContract.php',
    'app/Nexora/Studio/Services/StudioElementRegistry.php',
    'app/Nexora/Studio/Services/StudioBindingRegistry.php',
    'app/Nexora/Studio/Services/StudioCanvasValidator.php',
    'app/Nexora/Studio/Services/StudioManager.php',
    'app/Nexora/Studio/Services/StudioCanvasRenderer.php',
    'app/Http/Controllers/Admin/Studio/StudioController.php',
    'app/Models/StudioCanvas.php',
    'app/Models/StudioRevision.php',
    'app/Models/StudioComponent.php',
    'resources/js/admin/pages/Admin/Studio/Index.tsx',
    'resources/js/admin/pages/Admin/Studio/Editor.tsx',
    'database/migrations/2026_08_15_001000_add_nexora_studio.php',
    'tests/Unit/Studio/StudioCanvasValidatorTest.php',
    'tests/Feature/Studio/StudioFlowTest.php',
    'tests/Architecture/N021StudioArchitectureTest.php',
] as $artifact21) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact21);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.21 Studio artifact: {$artifact21}";
    }
}
$config21 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['StudioModule::class', 'studio.canvas.read', 'studio.canvas.write', 'studio.components.read', 'studio.components.write', 'studio.bindings.read'] as $marker21) {
    if (! str_contains($config21, $marker21)) {
        $errors[] = "N0.21 Studio runtime registration is missing: {$marker21}";
    }
}
$routes21 = is_file($routesFile) ? (string) file_get_contents($routesFile) : '';
foreach (["name('studio.index')", "name('studio.edit')", "name('studio.update')", "name('studio.publish')", "name('studio.components.store')"] as $route21) {
    if (! str_contains($routes21, $route21)) {
        $errors[] = "N0.21 Studio route is missing: {$route21}";
    }
}
$studioEditor21 = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Studio'.DIRECTORY_SEPARATOR.'Editor.tsx';
$studioEditor21Source = is_file($studioEditor21) ? (string) file_get_contents($studioEditor21) : '';
foreach (['@nexora/admin-ui', 'desktop', 'tablet', 'mobile', 'application/x-nexora-studio-element', 'Dynamic text binding', 'Save selected as component'] as $studioMarker21) {
    if (! str_contains($studioEditor21Source, $studioMarker21)) {
        $errors[] = "Studio editor is missing N0.21 foundation marker: {$studioMarker21}";
    }
}
if (preg_match('/<(button|input|select|textarea)\b/', $studioEditor21Source) === 1) {
    $errors[] = 'N0.21 Studio feature UI must use @nexora/admin-ui instead of raw interactive controls.';
}
$studioValidator21 = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Studio'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'StudioCanvasValidator.php';
$studioValidator21Source = is_file($studioValidator21) ? (string) file_get_contents($studioValidator21) : '';
foreach (['MAX_NODES', 'MAX_DEPTH', 'Unknown Studio element', 'sanitizeBindings'] as $validatorMarker21) {
    if (! str_contains($studioValidator21Source, $validatorMarker21)) {
        $errors[] = "Studio validator is missing safety control: {$validatorMarker21}";
    }
}


// N0.22 Blog & Article publishing architecture gates.
foreach ([
    'app/Nexora/Modules/Core/PublishingModule.php',
    'app/Nexora/Publishing/Services/ArticlePublishingManager.php',
    'app/Nexora/Publishing/Services/RelatedContentService.php',
    'app/Models/AuthorProfile.php',
    'app/Models/TaxonomyTerm.php',
    'app/Models/ContentSeries.php',
    'app/Models/ArticleMetadata.php',
    'app/Http/Controllers/Admin/Publishing/ArticleController.php',
    'app/Http/Controllers/Admin/Publishing/TaxonomyController.php',
    'app/Http/Controllers/Admin/Publishing/AuthorProfileController.php',
    'app/Http/Controllers/Admin/Publishing/SeriesController.php',
    'app/Http/Controllers/Public/BlogController.php',
    'resources/js/admin/pages/Admin/Publishing/Articles.tsx',
    'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx',
    'resources/js/admin/pages/Admin/Publishing/Taxonomy.tsx',
    'resources/js/admin/pages/Admin/Publishing/Authors.tsx',
    'resources/js/admin/pages/Admin/Publishing/Series.tsx',
    'database/migrations/2026_08_15_001100_add_nexora_blog_publishing.php',
    'tests/Feature/Publishing/BlogPublishingFlowTest.php',
    'tests/Architecture/N022PublishingArchitectureTest.php',
] as $artifact22) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact22);
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing N0.22 publishing artifact: {$artifact22}";
    }
}
$config22 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['PublishingModule::class', 'publishing.articles.read', 'publishing.articles.write', 'publishing.taxonomy.manage', 'publishing.authors.manage', 'publishing.series.manage'] as $marker22) {
    if (! str_contains($config22, $marker22)) {
        $errors[] = "N0.22 publishing runtime registration is missing: {$marker22}";
    }
}
$routes22 = is_file($routesFile) ? (string) file_get_contents($routesFile) : '';
foreach (['publishing.articles.index', 'publishing.taxonomy.index', 'publishing.authors.index', 'publishing.series.index', "'/blog'", "'/authors/{author:slug}'", "'/articles/{document:slug}'"] as $route22) {
    if (! str_contains($routes22, $route22)) {
        $errors[] = "N0.22 publishing route is missing: {$route22}";
    }
}
$appProvider22 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'AppServiceProvider.php');
foreach (["DocumentTypeDefinition('article'", "DocumentTypeDefinition('blog_post'"] as $documentType22) {
    if (! str_contains($appProvider22, $documentType22)) {
        $errors[] = "N0.22 publishing document type is not registered: {$documentType22}";
    }
}
$publishingModule22 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'PublishingModule.php');
if (! str_contains($publishingModule22, "new ModuleDependency('nexora.documents'") || ! str_contains($publishingModule22, "new ModuleDependency('nexora.seo'") || ! str_contains($publishingModule22, "new ModuleDependency('nexora.themes'")) {
    $errors[] = 'N0.22 Publishing must extend Documents, SEO and Themes rather than duplicate those systems.';
}


// N0.25 Media Library, Newsletter and Distribution architecture gates.
foreach ([
    'app/Nexora/Modules/Core/MediaDistributionModule.php',
    'app/Nexora/Media/Contracts/MediaManagerContract.php',
    'app/Nexora/Media/Services/MediaManager.php',
    'app/Nexora/Media/Services/MediaUploadPolicy.php',
    'app/Nexora/Media/Services/ImageVariantGenerator.php',
    'app/Nexora/Media/Services/MediaUsageManager.php',
    'app/Nexora/Distribution/Services/DistributionAdapterRegistry.php',
    'app/Nexora/Distribution/Services/RssFeedService.php',
    'app/Nexora/Distribution/Services/NewsletterSubscriptionManager.php',
    'app/Nexora/Distribution/Services/NewsletterDispatchService.php',
    'app/Models/MediaAsset.php',
    'app/Models/MediaFolder.php',
    'app/Models/MediaCollection.php',
    'app/Models/NewsletterSubscriber.php',
    'app/Models/NewsletterCampaign.php',
    'app/Http/Controllers/Admin/Media/MediaController.php',
    'app/Http/Controllers/Admin/Distribution/DistributionController.php',
    'app/Http/Controllers/Public/RssFeedController.php',
    'resources/js/admin/pages/Admin/Media/Index.tsx',
    'resources/js/admin/pages/Admin/Distribution/Index.tsx',
    'database/migrations/2026_08_15_001200_add_nexora_media_distribution.php',
] as $artifact25) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact25);
    if (! is_file($path) || filesize($path) === 0) $errors[] = "Missing N0.25 Media/Distribution artifact: {$artifact25}";
}
$config25 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['MediaDistributionModule::class','media.assets.read','media.assets.write','media.assets.delete','distribution.newsletter.read','distribution.newsletter.write','distribution.newsletter.send','distribution.adapters.read'] as $marker25) {
    if (! str_contains($config25, $marker25)) $errors[] = "N0.25 runtime registration is missing: {$marker25}";
}
$routes25 = is_file($routesFile) ? (string) file_get_contents($routesFile) : '';
foreach (["name('media.index')", "name('distribution.index')", "'/feed.xml'", "'/media/{asset:uuid}/{variant?}'", "newsletter.unsubscribe"] as $route25) {
    if (! str_contains($routes25, $route25)) $errors[] = "N0.25 route is missing: {$route25}";
}
$mediaPage25 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Media'.DIRECTORY_SEPARATOR.'Index.tsx');
$distributionPage25 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Distribution'.DIRECTORY_SEPARATOR.'Index.tsx');
foreach ([$mediaPage25, $distributionPage25] as $page25) {
    if (preg_match('/<(button|input|select|textarea)\b/', $page25) === 1) $errors[] = 'N0.25 Admin feature UI must use @nexora/admin-ui rather than raw interactive controls.';
}
$mediaPolicy25 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Media'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'MediaUploadPolicy.php');
foreach (['image/svg+xml','application/x-php','text/html'] as $forbiddenActiveType25) {
    if (str_contains($mediaPolicy25, "'{$forbiddenActiveType25}' =>")) $errors[] = "N0.25 public media policy must not allow active-content MIME type: {$forbiddenActiveType25}";
}
$plan25Path = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md';
$plan25 = is_file($plan25Path) ? (string) file_get_contents($plan25Path) : '';
if (! str_contains($plan25, '| N0.25 | Media, newsletter, syndication and distribution adapters | DONE |')) $errors[] = 'Master plan must mark N0.25 as DONE.';
if (! str_contains($plan25, '| N0.26 | Search, content analytics, SEO crawler/content audit | DONE |')) $errors[] = 'Master plan must mark N0.26 Search/Analytics/SEO Crawler as DONE.';


// N0.26 Search, content analytics and SEO crawler architecture gates.
foreach ([
    'app/Nexora/Modules/Core/SearchAnalyticsModule.php',
    'app/Nexora/Discovery/Search/SearchIndexer.php',
    'app/Nexora/Discovery/Search/DocumentTextExtractor.php',
    'app/Nexora/Discovery/Analytics/AnalyticsRecorder.php',
    'app/Nexora/Discovery/Analytics/AnalyticsAggregator.php',
    'app/Nexora/Discovery/Crawler/PageInspector.php',
    'app/Nexora/Discovery/Crawler/SeoCrawler.php',
    'app/Http/Middleware/RecordPublicAnalytics.php',
    'app/Http/Controllers/Admin/Discovery/DiscoveryController.php',
    'app/Http/Controllers/Public/SiteSearchController.php',
    'resources/js/admin/pages/Admin/Discovery/Index.tsx',
    'resources/js/admin/pages/Admin/Discovery/Crawl.tsx',
    'database/migrations/2026_08_15_001300_add_nexora_search_analytics_crawler.php',
    'tests/Feature/DiscoveryFlowTest.php',
    'tests/Unit/Discovery/PageInspectorTest.php',
    'tests/Architecture/N026DiscoveryArchitectureTest.php',
] as $artifact26) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact26);
    if (! is_file($path) || filesize($path) === 0) $errors[] = "Missing N0.26 Search/Analytics/Crawler artifact: {$artifact26}";
}
$config26 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['SearchAnalyticsModule::class','search.index.read','search.index.write','search.public.query','analytics.events.write','analytics.metrics.read','analytics.metrics.aggregate','seo.crawler.read','seo.crawler.run'] as $marker26) {
    if (! str_contains($config26, $marker26)) $errors[] = "N0.26 runtime registration is missing: {$marker26}";
}
$routes26 = is_file($routesFile) ? (string) file_get_contents($routesFile) : '';
foreach (["name('site.search')","name('discovery.index')","name('discovery.reindex')","name('discovery.aggregate')","name('discovery.crawl')","RecordPublicAnalytics::class"] as $route26) {
    if (! str_contains($routes26, $route26)) $errors[] = "N0.26 route/middleware marker is missing: {$route26}";
}
$analytics26 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Discovery'.DIRECTORY_SEPARATOR.'Analytics'.DIRECTORY_SEPARATOR.'AnalyticsRecorder.php');
foreach (["Sec-GPC", "DNT", "visitor_hash", "session_hash"] as $privacyMarker26) {
    if (! str_contains($analytics26, $privacyMarker26)) $errors[] = "N0.26 analytics privacy control is missing: {$privacyMarker26}";
}
if (preg_match('/\bip_address\b/', $analytics26) === 1) $errors[] = 'N0.26 content analytics must not persist raw visitor IP addresses.';
$crawler26 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Discovery'.DIRECTORY_SEPARATOR.'Crawler'.DIRECTORY_SEPARATOR.'SeoCrawler.php');
foreach (['isAllowed(', "'/admin'", 'resolveRedirect('] as $crawlerMarker26) {
    if (! str_contains($crawler26, $crawlerMarker26)) $errors[] = "N0.26 crawler boundary is missing: {$crawlerMarker26}";
}
$approvedHttp26=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Foundation'.DIRECTORY_SEPARATOR.'Network'.DIRECTORY_SEPARATOR.'ApprovedHttpClient.php');
if(!str_contains($crawler26,'ApprovedHttpClient')||!str_contains($approvedHttp26,'withoutRedirecting()'))$errors[]='N0.26 crawler outbound redirects must be blocked by the approved Nexora network broker.';
foreach (['resources/js/admin/pages/Admin/Discovery/Index.tsx','resources/js/admin/pages/Admin/Discovery/Crawl.tsx'] as $ui26) {
    $source26 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $ui26));
    if (! str_contains($source26, '@nexora/admin-ui')) $errors[] = "N0.26 Admin UI must use @nexora/admin-ui: {$ui26}";
    if (preg_match('/<(button|input|select|textarea)\b/', $source26) === 1) $errors[] = "N0.26 Admin feature UI contains raw interactive controls: {$ui26}";
}
$plan26 = is_file($plan25Path) ? (string) file_get_contents($plan25Path) : '';
if (! str_contains($plan26, '| N0.26 | Search, content analytics, SEO crawler/content audit | DONE |')) $errors[] = 'Master plan must mark N0.26 as DONE.';
if (! str_contains($plan26, '| N0.27 | Automation/workflow engine, triggers/conditions/actions/webhooks | DONE |')) $errors[] = 'Master plan must mark N0.27 Automation/Workflow as DONE.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external26) {
    if (! str_contains($plan26, $external26)) $errors[] = "Externalized roadmap family disappeared in N0.26: {$external26}";
}


// N0.27 Automation, Workflow Engine and Webhook architecture gates.
foreach ([
    'app/Nexora/Modules/Core/AutomationModule.php',
    'app/Nexora/Automation/Contracts/AutomationEventBusContract.php',
    'app/Nexora/Automation/Services/AutomationEventBus.php',
    'app/Nexora/Automation/Services/AutomationDefinitionValidator.php',
    'app/Nexora/Automation/Services/ConditionEvaluator.php',
    'app/Nexora/Automation/Services/WorkflowActionExecutor.php',
    'app/Nexora/Automation/Services/WebhookDeliveryService.php',
    'app/Nexora/Automation/Services/WebhookSigner.php',
    'app/Nexora/Automation/Services/WebhookUrlPolicy.php',
    'app/Http/Controllers/Admin/Automation/AutomationController.php',
    'app/Http/Controllers/Admin/Automation/WebhookController.php',
    'app/Http/Controllers/Public/InboundWebhookController.php',
    'app/Jobs/ExecuteWorkflowRunJob.php',
    'app/Jobs/DeliverWebhookJob.php',
    'resources/js/admin/pages/Admin/Automation/Index.tsx',
    'resources/js/admin/pages/Admin/Automation/Form.tsx',
    'resources/js/admin/pages/Admin/Automation/Run.tsx',
    'database/migrations/2026_08_15_001400_add_nexora_automation_workflows.php',
    'tests/Feature/AutomationFlowTest.php',
    'tests/Feature/WebhookDeliveryTest.php',
    'tests/Unit/Nexora/Automation/ConditionEvaluatorTest.php',
    'tests/Unit/Nexora/Automation/WebhookSignerTest.php',
    'tests/Architecture/N027AutomationArchitectureTest.php',
] as $artifact27) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact27);
    if (! is_file($path) || filesize($path) === 0) $errors[] = "Missing N0.27 Automation artifact: {$artifact27}";
}
$config27 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['AutomationModule::class','automation.workflows.read','automation.workflows.write','automation.events.emit','automation.runs.execute','webhooks.inbound.receive','webhooks.outbound.send'] as $marker27) {
    if (! str_contains($config27, $marker27)) $errors[] = "N0.27 runtime registration is missing: {$marker27}";
}
$routes27 = is_file($routesFile) ? (string) file_get_contents($routesFile) : '';
foreach (["name('automation.index')","name('automation.run')","name('automation.webhooks.destinations.store')","name('automation.webhooks.endpoints.rotate')","name('webhooks.inbound')"] as $route27) {
    if (! str_contains($routes27, $route27)) $errors[] = "N0.27 automation/webhook route is missing: {$route27}";
}
$delivery27 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Automation'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'WebhookDeliveryService.php');
foreach (['X-Nexora-Signature','X-Nexora-Timestamp','Idempotency-Key'] as $security27) {
    if (! str_contains($delivery27, $security27)) $errors[] = "N0.27 outbound Webhook security control is missing: {$security27}";
}
$approvedHttp27=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Foundation'.DIRECTORY_SEPARATOR.'Network'.DIRECTORY_SEPARATOR.'ApprovedHttpClient.php');
if(!str_contains($delivery27,'ApprovedHttpClient')||!str_contains($approvedHttp27,'withoutRedirecting()'))$errors[]='N0.27 outbound Webhook redirects must be blocked by the approved Nexora network broker.';
$inbound27 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Public'.DIRECTORY_SEPARATOR.'InboundWebhookController.php');
$bootstrap27 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php');
if (! str_contains($bootstrap27, 'preventRequestForgery') || ! str_contains($bootstrap27, "'hooks/*'")) $errors[] = 'N0.27 inbound Webhook route must be explicitly excluded from browser CSRF protection and protected by its own HMAC verification.';
foreach (['1_048_576','previous_secret_valid_until','hash_hmac','Idempotency-Key','Webhook signature verification failed'] as $inboundMarker27) {
    if (! str_contains($inbound27, $inboundMarker27)) $errors[] = "N0.27 inbound Webhook security control is missing: {$inboundMarker27}";
}
if (preg_match('/\bip_address\b/', $inbound27) === 1) $errors[] = 'N0.27 inbound Webhook receipts must not persist raw source IP addresses.';
foreach (['resources/js/admin/pages/Admin/Automation/Index.tsx','resources/js/admin/pages/Admin/Automation/Form.tsx','resources/js/admin/pages/Admin/Automation/Run.tsx'] as $ui27) {
    $source27=(string)file_get_contents($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $ui27));
    if (! str_contains($source27,'@nexora/admin-ui')) $errors[] = "N0.27 Admin UI must use @nexora/admin-ui: {$ui27}";
    if (preg_match('/<(button|input|select|textarea)\b/', $source27) === 1) $errors[] = "N0.27 Admin feature UI contains raw interactive controls: {$ui27}";
}
$plan27 = is_file($plan25Path) ? (string) file_get_contents($plan25Path) : '';
if (! str_contains($plan27, '| N0.27 | Automation/workflow engine, triggers/conditions/actions/webhooks | DONE |')) $errors[] = 'Master plan must mark N0.27 Automation/Workflow as DONE.';
if (! str_contains($plan27, '| N0.28 | Sentinel advanced supply-chain controls: SBOM, signing, provenance, sandbox adapters | DONE |')) $errors[] = 'Master plan must mark N0.28 Sentinel supply-chain controls as DONE.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external27) {
    if (! str_contains($plan27, $external27)) $errors[] = "Externalized roadmap family disappeared in N0.27: {$external27}";
}



// N0.28 Supply-chain security and P0 stability gates.
foreach ([
    'app/Nexora/Modules/Core/SupplyChainSecurityModule.php',
    'app/Nexora/Security/SupplyChain/Services/PackageContentDigest.php',
    'app/Nexora/Security/SupplyChain/Services/PackageJsonReader.php',
    'app/Nexora/Security/SupplyChain/Services/SbomService.php',
    'app/Nexora/Security/SupplyChain/Services/SignatureVerifier.php',
    'app/Nexora/Security/SupplyChain/Services/ProvenanceService.php',
    'app/Nexora/Security/SupplyChain/Services/SupplyChainAnalyzer.php',
    'app/Nexora/Security/SupplyChain/Services/PolicySandboxAdapter.php',
    'app/Nexora/Security/SupplyChain/Contracts/SandboxAdapterContract.php',
    'app/Http/Controllers/Admin/Security/SupplyChainController.php',
    'resources/js/admin/pages/Admin/Security/SupplyChain/Index.tsx',
    'app/Console/Commands/Nexora/SupplyChainVerifyCommand.php',
    'app/Nexora/Http/ErrorPresenter.php',
    'resources/js/admin/pages/Errors/Show.tsx',
    'database/migrations/2026_08_15_001500_add_nexora_supply_chain_security.php',
    'tests/Unit/Nexora/SupplyChain/PackageContentDigestTest.php',
    'tests/Architecture/N028SupplyChainArchitectureTest.php',
    'docs/n0-28-supply-chain-security.md',
] as $artifact28) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact28);
    if (! is_file($path) || filesize($path) === 0) $errors[] = "Missing N0.28 supply-chain/stability artifact: {$artifact28}";
}
$config28 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['SupplyChainSecurityModule::class', 'security.supply-chain.read', 'security.artifacts.verify', 'security.publishers.manage', 'security.sandbox.evaluate'] as $marker28) {
    if (! str_contains($config28, $marker28)) $errors[] = "N0.28 runtime registration is missing: {$marker28}";
}
$migration28 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2026_08_15_001500_add_nexora_supply_chain_security.php');
foreach (['nx_trusted_publishers','nx_supply_chain_artifacts','nx_supply_chain_components','nx_supply_chain_attestations'] as $table28) {
    if (! str_contains($migration28, $table28)) $errors[] = "N0.28 migration is missing table: {$table28}";
}
if (preg_match('/private[_-]?key/i', $migration28) === 1) $errors[] = 'N0.28 must never create a publisher private-signing-key database field.';
$digest28 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Security'.DIRECTORY_SEPARATOR.'SupplyChain'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'PackageContentDigest.php');
if (! str_contains($digest28, "if (\$name === 'nexora.signature.json') continue;")) $errors[] = 'N0.28 deterministic content digest must exclude only the detached signature manifest to avoid circular signing.';
$signature28 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Security'.DIRECTORY_SEPARATOR.'SupplyChain'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'SignatureVerifier.php');
foreach (['ed25519','sodium_crypto_sign_verify_detached','content_sha256','key_id'] as $signatureMarker28) {
    if (! str_contains($signature28, $signatureMarker28)) $errors[] = "N0.28 signature verification control is missing: {$signatureMarker28}";
}
$schedule28 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'console.php');
foreach (["->name('nexora.automation.hourly')->hourly()->withoutOverlapping()", "->name('nexora.automation.daily')->dailyAt('00:05')->withoutOverlapping()"] as $scheduleMarker28) {
    if (! str_contains($schedule28, $scheduleMarker28)) $errors[] = "N0.28 scheduler regression fix is missing: {$scheduleMarker28}";
}
if (preg_match_all('/Schedule::call\(.*?withoutOverlapping\(\)/s', $schedule28, $callbackSchedules28) > 0) {
    foreach ($callbackSchedules28[0] as $callbackSchedule28) if (! str_contains($callbackSchedule28, '->name(')) $errors[] = 'Every Schedule::call callback using withoutOverlapping() must be explicitly named first.';
}
$mediaPolicy28 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Media'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'MediaUploadPolicy.php');
if (! str_contains($mediaPolicy28, 'getPathname()')) $errors[] = 'N0.28 Media upload policy must use the Windows-safe UploadedFile pathname rather than getRealPath().';
if (str_contains($mediaPolicy28, '->getRealPath(')) $errors[] = 'N0.28 Media upload policy regressed to getRealPath(), which is fragile for PHP temporary uploads on Windows.';
$errorPresenter28 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'ErrorPresenter.php');
foreach (['request_id','HTTP_','Too many requests','Something went wrong'] as $errorMarker28) if (! str_contains($errorPresenter28, $errorMarker28)) $errors[] = "N0.28 safe HTTP error presentation is missing: {$errorMarker28}";
foreach (['resources/js/admin/pages/Admin/Security/SupplyChain/Index.tsx','resources/js/admin/pages/Errors/Show.tsx'] as $ui28) {
    $source28 = (string) file_get_contents($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $ui28));
    if (! str_contains($source28, '@nexora/admin-ui')) $errors[] = "N0.28 Admin UI must use @nexora/admin-ui: {$ui28}";
    if (preg_match('/<(button|input|select|textarea)\b/', $source28) === 1) $errors[] = "N0.28 Admin feature UI contains raw interactive controls: {$ui28}";
}
$plan28 = is_file($plan25Path) ? (string) file_get_contents($plan25Path) : '';
if (! str_contains($plan28, '| N0.28 | Sentinel advanced supply-chain controls: SBOM, signing, provenance, sandbox adapters | DONE |')) $errors[] = 'Master plan must mark N0.28 as DONE.';
if (! str_contains($plan28, '| N0.29 | Extensions lifecycle, Forge developer SDK, Marketplace | DONE |')) $errors[] = 'Master plan must mark N0.29 Extensions/Forge/Marketplace as DONE.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external28) if (! str_contains($plan28, $external28)) $errors[] = "Externalized roadmap family disappeared in N0.28: {$external28}";


// N0.29 Extensions lifecycle, Forge SDK, Marketplace and shared UI behavior gates.
foreach ([
    'app/Nexora/Modules/Core/ExtensionsModule.php',
    'app/Nexora/Extensions/Data/ExtensionManifest.php',
    'app/Nexora/Extensions/Services/ExtensionManifestValidator.php',
    'app/Nexora/Extensions/Services/ExtensionPackageInstaller.php',
    'app/Nexora/Extensions/Services/ExtensionLifecycleManager.php',
    'app/Nexora/Extensions/Services/ExtensionMigrationRunner.php',
    'app/Nexora/Extensions/Services/MarketplaceCatalogService.php',
    'app/Nexora/Extensions/Services/MarketplacePackageStager.php',
    'app/Http/Controllers/Admin/Extensions/ExtensionController.php',
    'app/Console/Commands/Nexora/ExtensionListCommand.php',
    'app/Console/Commands/Nexora/MakeExtensionCommand.php',
    'resources/js/admin/pages/Admin/Extensions/Index.tsx',
    'resources/js/admin/pages/Admin/Extensions/Show.tsx',
    'resources/js/admin/ui/untitled/date-time-picker.tsx',
    'database/migrations/2026_08_15_001600_add_nexora_extensions_marketplace.php',
    'tests/Unit/Extensions/ExtensionManifestValidatorTest.php',
    'tests/Feature/Extensions/ExtensionsAdminFlowTest.php',
    'tests/Architecture/N029ExtensionsArchitectureTest.php',
    'docs/n0-29-extensions-marketplace.md',
] as $artifact29) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $artifact29);
    if (! is_file($path) || filesize($path) === 0) $errors[] = "Missing N0.29 extension/marketplace artifact: {$artifact29}";
}
$config29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['ExtensionsModule::class','extensions.registry.read','extensions.lifecycle.manage','extensions.capabilities.grant','marketplace.catalog.read','marketplace.catalog.sync'] as $marker29) {
    if (! str_contains($config29,$marker29)) $errors[]="N0.29 runtime registration is missing: {$marker29}";
}
$migration29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2026_08_15_001600_add_nexora_extensions_marketplace.php');
foreach (['nx_extensions','nx_extension_versions','nx_extension_dependencies','nx_extension_capability_grants','nx_extension_lifecycle_events','nx_marketplace_sources','nx_marketplace_catalog_items'] as $table29) {
    if (! str_contains($migration29,$table29)) $errors[]="N0.29 migration is missing table: {$table29}";
}
if (str_contains($migration29,'->after(')) $errors[]='N0.29 migration must remain portable and cannot use ->after().';
$installer29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Extensions'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'ExtensionPackageInstaller.php');
foreach (["decision !== 'allow'",'content_sha256','Version immutability blocked the install'] as $installMarker29) if (! str_contains($installer29,$installMarker29)) $errors[]="N0.29 extension install boundary is missing: {$installMarker29}";
$stager29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Extensions'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'MarketplacePackageStager.php');
foreach (['trusted_publishers_only',"where('status', 'active')",'storeLocalFile','hash_equals'] as $stageMarker29) if (! str_contains($stager29,$stageMarker29)) $errors[]="N0.29 Marketplace trust/quarantine boundary is missing: {$stageMarker29}";
$approvedHttp29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Foundation'.DIRECTORY_SEPARATOR.'Network'.DIRECTORY_SEPARATOR.'ApprovedHttpClient.php');if(!str_contains($stager29,'ApprovedHttpClient')||!str_contains($approvedHttp29,'withoutRedirecting()'))$errors[]='N0.29 Marketplace package redirects must be blocked by the approved Nexora network broker.';
foreach (['resources/js/admin/pages/Admin/Extensions/Index.tsx','resources/js/admin/pages/Admin/Extensions/Show.tsx'] as $ui29) {
    $source29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$ui29));
    if (! str_contains($source29,'@nexora/admin-ui')) $errors[]="N0.29 Admin UI must use @nexora/admin-ui: {$ui29}";
    if (preg_match('/<(button|input|select|textarea)\\b/',$source29)===1) $errors[]="N0.29 Admin feature UI contains raw interactive controls: {$ui29}";
}
$dataTable29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'DataTable.tsx');
foreach (['sticky top-0','sticky bottom-0'] as $tableMarker29) if (! str_contains($dataTable29,$tableMarker29)) $errors[]="Shared DataTable sticky behavior regressed: {$tableMarker29}";
$select29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'ui'.DIRECTORY_SEPARATOR.'untitled'.DIRECTORY_SEPARATOR.'select.tsx');
if (! str_contains($select29,'react-aria-components')) $errors[]='Shared Select must be implemented with the existing React Aria UI primitive.';
if (! str_contains($select29,'value={value === "" ? null : value}')) $errors[]='Shared Select must use the current React Aria value/onChange selection API.';
if (! str_contains($select29,'onChange={(key) =>')) $errors[]='Shared Select must forward current React Aria onChange selection events.';
if (str_contains($select29,'selectedKey=') || str_contains($select29,'onSelectionChange=')) $errors[]='Shared Select regressed to the legacy selectedKey/onSelectionChange API.';
if (str_contains($select29,'nx-pressable')) $errors[]='Shared Select trigger must not inherit generic action-button press/scale behavior.';
$dateTime29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'ui'.DIRECTORY_SEPARATOR.'untitled'.DIRECTORY_SEPARATOR.'date-time-picker.tsx');
foreach (['react-aria-components','@internationalized/date','AriaDatePicker','AriaTimeField'] as $dateMarker29) if (! str_contains($dateTime29,$dateMarker29)) $errors[]="Shared date/time UI is missing library primitive: {$dateMarker29}";
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages',['tsx','ts']) as $feature29) {
    $featureSource29=(string)file_get_contents($feature29);
    if (preg_match('/type=[\"\\\'](?:date|time|datetime-local|month|week)[\"\\\']/i',$featureSource29)===1) $errors[]="Admin feature pages must use Nexora Date/Time UI components instead of native browser date/time inputs: {$feature29}";
}
$plan29=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md');
if (! str_contains($plan29,'| N0.29 | Extensions lifecycle, Forge developer SDK, Marketplace | DONE |')) $errors[]='Master plan must mark N0.29 Extensions/Forge/Marketplace as DONE.';
if (! str_contains($plan29,'| N0.30 | Commerce + Billing foundation | DONE |')) $errors[]='Master plan must mark N0.30 Commerce/Billing as DONE.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external29) if (! str_contains($plan29,$external29)) $errors[]="Externalized roadmap family disappeared in N0.29: {$external29}";


// N0.30 Commerce + Billing provider-neutral foundation gates.
foreach ([
    'app/Nexora/Modules/Core/CommerceModule.php',
    'app/Nexora/Commerce/Contracts/PaymentProviderContract.php',
    'app/Nexora/Commerce/Services/PaymentProviderRegistry.php',
    'app/Nexora/Commerce/Services/CurrencyManager.php',
    'app/Nexora/Commerce/Services/TaxCalculator.php',
    'app/Nexora/Commerce/Services/CommerceOrderService.php',
    'app/Nexora/Commerce/Services/InvoiceService.php',
    'app/Nexora/Commerce/Services/PaymentService.php',
    'app/Nexora/Commerce/Services/RefundService.php',
    'app/Http/Controllers/Admin/Commerce/CommerceDashboardController.php',
    'resources/js/admin/pages/Admin/Commerce/Index.tsx',
    'resources/js/admin/pages/Admin/Commerce/Products.tsx',
    'resources/js/admin/pages/Admin/Commerce/Customers.tsx',
    'resources/js/admin/pages/Admin/Commerce/Orders.tsx',
    'resources/js/admin/pages/Admin/Commerce/Billing.tsx',
    'resources/js/admin/pages/Admin/Commerce/Settings.tsx',
    'database/migrations/2026_08_16_001700_add_nexora_commerce_billing.php',
    'tests/Unit/Commerce/PaymentProviderRegistryTest.php',
    'tests/Unit/Commerce/CurrencyAndTaxTest.php',
    'tests/Feature/Commerce/CommerceAdminFlowTest.php',
    'tests/Architecture/N030CommerceArchitectureTest.php',
    'docs/n0-30-commerce-billing.md',
] as $artifact30) {
    $path=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$artifact30);
    if (! is_file($path) || filesize($path)===0) $errors[]="Missing N0.30 Commerce artifact: {$artifact30}";
}
$config30=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['CommerceModule::class','commerce.catalog.read','commerce.catalog.write','commerce.customers.read','commerce.orders.write','commerce.billing.read','commerce.tax.manage','commerce.providers.register','commerce.payments.manage'] as $marker30) if (! str_contains($config30,$marker30)) $errors[]="N0.30 runtime registration is missing: {$marker30}";
$migration30=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2026_08_16_001700_add_nexora_commerce_billing.php');
foreach (['nx_commerce_currencies','nx_commerce_tax_rates','nx_commerce_products','nx_commerce_prices','nx_commerce_customers','nx_commerce_orders','nx_commerce_order_items','nx_commerce_invoices','nx_commerce_payment_provider_configs','nx_commerce_payment_transactions','nx_commerce_refunds','nx_commerce_subscriptions','nx_commerce_billing_events'] as $table30) if (! str_contains($migration30,$table30)) $errors[]="N0.30 migration is missing table: {$table30}";
if (str_contains($migration30,'->after(')) $errors[]='N0.30 migration must remain portable and cannot use ->after().';
$commerceCore30='';
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Commerce',['php']) as $commerceFile30) $commerceCore30.=(string)file_get_contents($commerceFile30)."\n";
if (preg_match('/\b(?:Stripe|PayPal|Braintree|Adyen)\b/i',$commerceCore30)===1) $errors[]='N0.30 Commerce Core must not hard-code a payment gateway SDK/provider implementation.';
foreach (['api_key','secret_key','client_secret','private_key'] as $secretColumn30) if (preg_match('/["\']'.$secretColumn30.'["\']/', $migration30)===1) $errors[]="N0.30 Commerce Core must not create gateway secret columns: {$secretColumn30}";
$providerContract30=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Commerce'.DIRECTORY_SEPARATOR.'Contracts'.DIRECTORY_SEPARATOR.'PaymentProviderContract.php');
foreach (['createPayment','refund','createSubscription','cancelSubscription'] as $providerMarker30) if (! str_contains($providerContract30,$providerMarker30)) $errors[]="N0.30 payment-provider contract is missing: {$providerMarker30}";
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Commerce',['tsx','ts']) as $feature30) {
    $source30=(string)file_get_contents($feature30);
    if (preg_match('/<(button|input|select|textarea)\\b/',$source30)===1) $errors[]="N0.30 Commerce feature UI contains raw interactive controls: {$feature30}";
}
$plan30=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md');
if (! str_contains($plan30,'| N0.30 | Commerce + Billing foundation | DONE |')) $errors[]='Master plan must mark N0.30 Commerce/Billing as DONE.';
if (! str_contains($plan30,'| N0.31 | CRM foundation | DONE |')) $errors[]='Master plan must mark N0.31 CRM as DONE.';
if (! str_contains($plan30,'| N0.32 | Membership + Helpdesk foundations; LMS/Booking/Projects remain external packages | DONE |')) $errors[]='Master plan must mark N0.32 Membership/Helpdesk as DONE.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external30) if (! str_contains($plan30,$external30)) $errors[]="Externalized roadmap family disappeared in N0.30: {$external30}";


// N0.31 CRM provider-neutral foundation gates.
foreach ([
    'app/Nexora/Modules/Core/CrmModule.php',
    'app/Nexora/Crm/Contracts/CrmActivityProviderContract.php',
    'app/Nexora/Crm/Contracts/CrmCommerceLinkContract.php',
    'app/Nexora/Crm/Contracts/CrmOpportunityManagerContract.php',
    'app/Nexora/Crm/Contracts/CrmTimelineContract.php',
    'app/Nexora/Crm/Services/CrmActivityProviderRegistry.php',
    'app/Nexora/Crm/Services/CrmActivityService.php',
    'app/Nexora/Crm/Services/CrmCommerceLinkService.php',
    'app/Nexora/Crm/Services/CrmCustomFieldService.php',
    'app/Nexora/Crm/Services/CrmLeadConversionService.php',
    'app/Nexora/Crm/Services/CrmOpportunityService.php',
    'app/Nexora/Crm/Services/CrmTimelineService.php',
    'app/Http/Controllers/Admin/Crm/CrmDashboardController.php',
    'app/Http/Controllers/Admin/Crm/OrganizationController.php',
    'app/Http/Controllers/Admin/Crm/ContactController.php',
    'app/Http/Controllers/Admin/Crm/LeadController.php',
    'app/Http/Controllers/Admin/Crm/OpportunityController.php',
    'app/Http/Controllers/Admin/Crm/ActivityController.php',
    'app/Http/Controllers/Admin/Crm/CrmSettingsController.php',
    'app/Http/Controllers/Admin/Crm/CommerceLinkController.php',
    'resources/js/admin/pages/Admin/Crm/Index.tsx',
    'resources/js/admin/pages/Admin/Crm/Organizations.tsx',
    'resources/js/admin/pages/Admin/Crm/Contacts.tsx',
    'resources/js/admin/pages/Admin/Crm/Leads.tsx',
    'resources/js/admin/pages/Admin/Crm/Opportunities.tsx',
    'resources/js/admin/pages/Admin/Crm/Settings.tsx',
    'resources/js/admin/pages/Admin/Crm/CommerceLinks.tsx',
    'database/migrations/2026_08_16_001800_add_nexora_crm.php',
    'tests/Unit/Crm/CrmActivityProviderRegistryTest.php',
    'tests/Feature/Crm/CrmAdminFlowTest.php',
    'tests/Architecture/N031CrmArchitectureTest.php',
    'docs/n0-31-crm.md',
] as $artifact31) {
    $path=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$artifact31);
    if (! is_file($path) || filesize($path)===0) $errors[]="Missing N0.31 CRM artifact: {$artifact31}";
}
$config31=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['CrmModule::class','crm.organizations.read','crm.organizations.write','crm.contacts.read','crm.contacts.write','crm.leads.read','crm.leads.write','crm.opportunities.read','crm.opportunities.write','crm.activities.read','crm.activities.write','crm.custom-fields.manage','crm.commerce.link','crm.providers.register'] as $marker31) if (! str_contains($config31,$marker31)) $errors[]="N0.31 runtime registration is missing: {$marker31}";
$migration31=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2026_08_16_001800_add_nexora_crm.php');
foreach (['nx_crm_organizations','nx_crm_contacts','nx_crm_pipelines','nx_crm_pipeline_stages','nx_crm_opportunities','nx_crm_leads','nx_crm_activities','nx_crm_notes','nx_crm_timeline_events','nx_crm_opportunity_stage_history','nx_crm_custom_field_definitions','nx_crm_custom_field_values','nx_crm_commerce_links'] as $table31) if (! str_contains($migration31,$table31)) $errors[]="N0.31 migration is missing table: {$table31}";
if (str_contains($migration31,'->after(')) $errors[]='N0.31 migration must remain portable and cannot use ->after().';
$crmCore31='';
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Crm',['php']) as $crmFile31) $crmCore31.=(string)file_get_contents($crmFile31)."\n";
if (preg_match('/\b(?:Google\\\\Client|GmailService|MicrosoftGraph|OutlookClient)\b/i',$crmCore31)===1) $errors[]='N0.31 CRM Core must not hard-code email/calendar provider SDK implementations.';
$crmCommerce31=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Crm'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'CrmCommerceLinkService.php');
if (! str_contains($crmCommerce31,'commerce_customer_id')) $errors[]='N0.31 CRM-Commerce integration must use the explicit link boundary.';
$crmOpportunity31=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Crm'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'CrmOpportunityService.php');
foreach (['DB::transaction','lockForUpdate','CrmOpportunityStageHistory'] as $crmTransition31) if (! str_contains($crmOpportunity31,$crmTransition31)) $errors[]="N0.31 opportunity transition safety is missing: {$crmTransition31}";
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Crm',['tsx','ts']) as $feature31) {
    $source31=(string)file_get_contents($feature31);
    if (preg_match('/<(button|input|select|textarea)\\b/',$source31)===1) $errors[]="N0.31 CRM feature UI contains raw interactive controls: {$feature31}";
    if (preg_match('/type=["\\\'](?:date|time|datetime-local|month|week)["\\\']/i',$source31)===1) $errors[]="N0.31 CRM feature UI must use shared Date/Time components: {$feature31}";
}
$crmOppPage31=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Crm'.DIRECTORY_SEPARATOR.'Opportunities.tsx');
if (! str_contains($crmOppPage31,'DateTimePicker')) $errors[]='N0.31 opportunity UI must use the shared DateTimePicker.';
$dataTable31=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'DataTable.tsx');
foreach (['sticky top-0','sticky bottom-0'] as $tableMarker31) if (! str_contains($dataTable31,$tableMarker31)) $errors[]="Shared DataTable sticky behavior regressed in N0.31: {$tableMarker31}";
$plan31=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md');
if (! str_contains($plan31,'| N0.31 | CRM foundation | DONE |')) $errors[]='Master plan must mark N0.31 CRM as DONE.';
if (! str_contains($plan31,'| N0.32 | Membership + Helpdesk foundations; LMS/Booking/Projects remain external packages | DONE |')) $errors[]='Master plan must mark N0.32 Membership/Helpdesk as DONE.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external31) if (! str_contains($plan31,$external31)) $errors[]="Externalized roadmap family disappeared in N0.31: {$external31}";



// N0.32 Membership + Helpdesk foundations and external-app boundary gates.
foreach ([
    'app/Nexora/Modules/Core/MembershipModule.php',
    'app/Nexora/Modules/Core/HelpdeskModule.php',
    'app/Nexora/Membership/Contracts/MembershipAccessContract.php',
    'app/Nexora/Membership/Contracts/MembershipManagerContract.php',
    'app/Nexora/Membership/Services/MembershipAccessManager.php',
    'app/Nexora/Membership/Services/MembershipManager.php',
    'app/Nexora/Membership/Services/MembershipCommerceSyncService.php',
    'app/Nexora/Helpdesk/Contracts/HelpdeskTicketManagerContract.php',
    'app/Nexora/Helpdesk/Services/HelpdeskTicketManager.php',
    'app/Nexora/Helpdesk/Services/HelpdeskSlaService.php',
    'app/Http/Controllers/Admin/Membership/MembershipDashboardController.php',
    'app/Http/Controllers/Admin/Membership/MembershipPlanController.php',
    'app/Http/Controllers/Admin/Membership/MembershipController.php',
    'app/Http/Controllers/Admin/Membership/MembershipAccessPolicyController.php',
    'app/Http/Controllers/Admin/Helpdesk/HelpdeskDashboardController.php',
    'app/Http/Controllers/Admin/Helpdesk/HelpdeskTicketController.php',
    'app/Http/Controllers/Admin/Helpdesk/HelpdeskSettingsController.php',
    'resources/js/admin/pages/Admin/Membership/Index.tsx',
    'resources/js/admin/pages/Admin/Membership/Plans.tsx',
    'resources/js/admin/pages/Admin/Membership/Members.tsx',
    'resources/js/admin/pages/Admin/Membership/AccessPolicies.tsx',
    'resources/js/admin/pages/Admin/Helpdesk/Index.tsx',
    'resources/js/admin/pages/Admin/Helpdesk/Tickets.tsx',
    'resources/js/admin/pages/Admin/Helpdesk/TicketShow.tsx',
    'resources/js/admin/pages/Admin/Helpdesk/Settings.tsx',
    'database/migrations/2026_08_16_001900_add_nexora_membership_helpdesk.php',
    'tests/Unit/Membership/MembershipEffectiveTest.php',
    'tests/Feature/MembershipHelpdesk/MembershipHelpdeskFlowTest.php',
    'tests/Architecture/N032MembershipHelpdeskArchitectureTest.php',
    'docs/n0-32-membership-helpdesk.md',
] as $artifact32) {
    $path=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$artifact32);
    if (! is_file($path) || filesize($path)===0) $errors[]="Missing N0.32 Membership/Helpdesk artifact: {$artifact32}";
}
$config32=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['MembershipModule::class','HelpdeskModule::class','membership.plans.read','membership.members.write','membership.access.evaluate','membership.access.manage','membership.commerce.sync','helpdesk.tickets.read','helpdesk.tickets.write','helpdesk.messages.write','helpdesk.sla.manage'] as $marker32) if (! str_contains($config32,$marker32)) $errors[]="N0.32 runtime registration is missing: {$marker32}";
$migration32=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2026_08_16_001900_add_nexora_membership_helpdesk.php');
foreach (['nx_membership_plans','nx_membership_entitlements','nx_memberships','nx_membership_access_policies','nx_membership_events','nx_helpdesk_sla_policies','nx_helpdesk_tickets','nx_helpdesk_messages','nx_helpdesk_ticket_events'] as $table32) if (! str_contains($migration32,$table32)) $errors[]="N0.32 migration is missing table: {$table32}";
if (str_contains($migration32,'->after(')) $errors[]='N0.32 migration must remain portable and cannot use ->after().';
$theme32=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Public'.DIRECTORY_SEPARATOR.'ThemePageController.php');
foreach (['MembershipAccessContract','assertCanAccess'] as $access32) if (! str_contains($theme32,$access32)) $errors[]="N0.32 protected-content access boundary is missing: {$access32}";
$membershipCore32=''; foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Membership',['php']) as $file32) $membershipCore32.=(string)file_get_contents($file32)."\n";
if (preg_match('/\b(?:Course|Lesson|Quiz|Appointment|ProjectBoard)\b/',$membershipCore32)===1) $errors[]='N0.32 Membership Core must not absorb LMS, Booking or Projects product-domain implementations.';
$helpdeskCore32=''; foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Helpdesk',['php']) as $file32) $helpdeskCore32.=(string)file_get_contents($file32)."\n";
if (preg_match('/\b(?:Zendesk|Freshdesk|Intercom|MicrosoftGraph|GmailService)\b/i',$helpdeskCore32)===1) $errors[]='N0.32 Helpdesk Core must remain support-provider neutral.';
$features32 = array_merge(
    iterator_to_array(nexoraFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Membership',['tsx','ts'])),
    iterator_to_array(nexoraFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Helpdesk',['tsx','ts']))
);
foreach ($features32 as $feature32) {
    $source32=(string)file_get_contents($feature32);
    if (preg_match('/<(button|input|select|textarea)\b/',$source32)===1) $errors[]="N0.32 feature UI contains raw interactive controls: {$feature32}";
    if (preg_match('/type=["\'](?:date|time|datetime-local|month|week)["\']/i',$source32)===1) $errors[]="N0.32 feature UI must use shared Date/Time components: {$feature32}";
}
$members32=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Membership'.DIRECTORY_SEPARATOR.'Members.tsx');
if (! str_contains($members32,'DateTimePicker')) $errors[]='N0.32 membership scheduling UI must use the shared DateTimePicker.';
$schedule32=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'console.php');
foreach (['nexora:membership:expire','nexora:helpdesk:sla-check'] as $command32) if (! str_contains($schedule32,$command32)) $errors[]="N0.32 scheduled maintenance is missing: {$command32}";
$plan32=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md');
if (! str_contains($plan32,'| N0.32 | Membership + Helpdesk foundations; LMS/Booking/Projects remain external packages | DONE |')) $errors[]='Master plan must mark N0.32 Membership/Helpdesk as DONE.';
if (! str_contains($plan32,'| N0.33 | Multisite, tenancy, organizations, SSO and enterprise controls | DONE |')) $errors[]='Master plan must mark N0.33 Enterprise/Multisite as DONE.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external32) if (! str_contains($plan32,$external32)) $errors[]="Externalized roadmap family disappeared in N0.32: {$external32}";


// N0.33 enterprise tenancy, identity and governance gates.
foreach ([
    'app/Nexora/Modules/Core/EnterpriseModule.php',
    'app/Nexora/Enterprise/Contracts/EnterpriseIdentityProviderContract.php',
    'app/Nexora/Enterprise/Services/TenantContext.php',
    'app/Nexora/Enterprise/Services/TenantAuthorizationService.php',
    'app/Nexora/Enterprise/Services/SsoProviderRegistry.php',
    'app/Nexora/Enterprise/Support/BelongsToTenant.php',
    'app/Http/Middleware/ResolveEnterpriseOrganization.php',
    'app/Http/Controllers/Admin/Enterprise/EnterpriseController.php',
    'app/Http/Controllers/Enterprise/ScimController.php',
    'app/Http/Controllers/Enterprise/SsoController.php',
    'resources/js/admin/pages/Admin/Enterprise/Index.tsx',
    'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx',
    'resources/js/admin/components/OrganizationSwitcher.tsx',
    'database/migrations/2026_08_16_002000_add_nexora_enterprise_tenancy.php',
    'tests/Unit/Enterprise/TenantAuthorizationTest.php',
    'tests/Feature/Enterprise/EnterpriseFlowTest.php',
    'tests/Architecture/N033EnterpriseArchitectureTest.php',
    'docs/n0-33-enterprise-tenancy.md',
] as $artifact33) {
    $path=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$artifact33);
    if (! is_file($path) || filesize($path)===0) $errors[]="Missing N0.33 Enterprise artifact: {$artifact33}";
}
$config33=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['EnterpriseModule::class','enterprise.organizations.read','enterprise.organizations.write','enterprise.members.manage','enterprise.domains.manage','enterprise.identity.manage','enterprise.scim.manage','enterprise.impersonation.manage','enterprise.audit.read','enterprise.tenant.resolve'] as $marker33) if (! str_contains($config33,$marker33)) $errors[]="N0.33 runtime registration is missing: {$marker33}";
$migration33=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2026_08_16_002000_add_nexora_enterprise_tenancy.php');
foreach (['nx_enterprise_organizations','nx_enterprise_roles','nx_enterprise_organization_members','nx_enterprise_domains','nx_enterprise_invitations','nx_enterprise_sso_providers','nx_enterprise_scim_tokens','nx_enterprise_impersonation_sessions','nx_enterprise_audit_events','nx_enterprise_settings'] as $table33) if (! str_contains($migration33,$table33)) $errors[]="N0.33 migration is missing table: {$table33}";
if (str_contains($migration33,'->after(')) $errors[]='N0.33 migration must remain portable and cannot use ->after().';
if (! str_contains($migration33,"'tenant_id'")) $errors[]='N0.33 migration must add an explicit tenant_id boundary to tenant-scoped roots.';
$tenantTrait33=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Enterprise'.DIRECTORY_SEPARATOR.'Support'.DIRECTORY_SEPARATOR.'BelongsToTenant.php');
foreach (["addGlobalScope('nexora_tenant'",'tenant_id','is_default'] as $tenantMarker33) if (! str_contains($tenantTrait33,$tenantMarker33)) $errors[]="N0.33 tenant scope is missing: {$tenantMarker33}";
$settings33=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Foundation'.DIRECTORY_SEPARATOR.'Settings'.DIRECTORY_SEPARATOR.'DatabaseSettingsRepository.php');
foreach (['TenantContext','nx_enterprise_settings','EnterpriseSetting'] as $settingsMarker33) if (! str_contains($settings33,$settingsMarker33)) $errors[]="N0.33 tenant settings overlay is missing: {$settingsMarker33}";
$permission33=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Middleware'.DIRECTORY_SEPARATOR.'RequirePermission.php');
if (! str_contains($permission33,'TenantAuthorizationService')) $errors[]='N0.33 RequirePermission must enforce the second organization-role restriction key.';
$ssoContract33=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Enterprise'.DIRECTORY_SEPARATOR.'Contracts'.DIRECTORY_SEPARATOR.'EnterpriseIdentityProviderContract.php');
foreach (['protocol','redirectUrl','resolveIdentity'] as $ssoMarker33) if (! str_contains($ssoContract33,$ssoMarker33)) $errors[]="N0.33 enterprise identity adapter contract is missing: {$ssoMarker33}";
$enterpriseCore33=''; foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Enterprise',['php']) as $file33) $enterpriseCore33.=(string)file_get_contents($file33)."\n";
if (preg_match('/\b(?:Okta|Auth0|AzureAD|GoogleWorkspace|OneLogin)\\\\/i',$enterpriseCore33)===1) $errors[]='N0.33 Enterprise Core must not hard-code a vendor identity SDK.';
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Enterprise',['tsx','ts']) as $feature33) {
    $source33=(string)file_get_contents($feature33);
    if (! str_contains($source33,'@nexora/admin-ui')) $errors[]="N0.33 Enterprise UI must consume @nexora/admin-ui: {$feature33}";
    if (preg_match('/<(button|input|select|textarea)\\b/',$source33)===1) $errors[]="N0.33 Enterprise feature UI contains raw interactive controls: {$feature33}";
    if (preg_match('/type=["\'](?:date|time|datetime-local|month|week)["\']/i',$source33)===1) $errors[]="N0.33 Enterprise UI must use shared date/time primitives: {$feature33}";
}
$csrf33=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php');
foreach (['scim/*','sso/*/*/callback'] as $csrfMarker33) if (! str_contains($csrf33,$csrfMarker33)) $errors[]="N0.33 external identity endpoint CSRF boundary is missing: {$csrfMarker33}";
$plan33=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md');
if (! str_contains($plan33,'| N0.33 | Multisite, tenancy, organizations, SSO and enterprise controls | DONE |')) $errors[]='Master plan must mark N0.33 Enterprise/Multisite as DONE.';
if (! str_contains($plan33,'| N0.34 | Cloud/HA/distributed runtime, queues, object storage, operational tooling | DONE |')) $errors[]='Master plan must keep N0.34 Cloud/HA runtime marked DONE.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external33) if (! str_contains($plan33,$external33)) $errors[]="Externalized roadmap family disappeared in N0.33: {$external33}";



// N0.34 cloud / HA / distributed-runtime gates.
foreach ([
    'app/Nexora/Modules/Core/CloudRuntimeModule.php',
    'app/Nexora/Cloud/Contracts/ObjectStorageContract.php',
    'app/Nexora/Cloud/Contracts/DistributedLockContract.php',
    'app/Nexora/Cloud/Services/NodeIdentity.php',
    'app/Nexora/Cloud/Services/NodeManager.php',
    'app/Nexora/Cloud/Services/RuntimeLeaseManager.php',
    'app/Nexora/Cloud/Services/ClusterLeadership.php',
    'app/Nexora/Cloud/Services/LaravelObjectStorage.php',
    'app/Nexora/Cloud/Services/LaravelDistributedLock.php',
    'app/Nexora/Cloud/Services/RuntimeTopology.php',
    'app/Nexora/Cloud/Services/RuntimeMetricsRecorder.php',
    'app/Nexora/Cloud/Services/HealthProbeService.php',
    'app/Nexora/Cloud/Services/BackupOrchestrator.php',
    'app/Nexora/Cloud/Services/RestorePlanner.php',
    'app/Http/Middleware/RuntimeNodeHeartbeat.php',
    'app/Http/Controllers/Admin/Cloud/CloudOperationsController.php',
    'app/Http/Controllers/Operations/RuntimeHealthController.php',
    'resources/js/admin/pages/Admin/Cloud/Index.tsx',
    'database/migrations/2026_08_16_002100_add_nexora_cloud_runtime.php',
    'tests/Unit/Cloud/RuntimeLeaseManagerTest.php',
    'tests/Unit/Cloud/ObjectStorageContractTest.php',
    'tests/Feature/Cloud/CloudOperationsFlowTest.php',
    'tests/Architecture/N034CloudRuntimeArchitectureTest.php',
    'docs/n0-34-cloud-runtime.md',
    'config/nexora_cloud.php',
] as $artifact34) {
    $path=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$artifact34);
    if (! is_file($path) || filesize($path)===0) $errors[]="Missing N0.34 Cloud Runtime artifact: {$artifact34}";
}
$config34=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
foreach (['CloudRuntimeModule::class','cloud.topology.read','cloud.nodes.manage','cloud.leases.manage','cloud.storage.read','cloud.storage.write','cloud.metrics.read','cloud.metrics.write','cloud.backups.manage','cloud.restore.plan'] as $marker34) if (! str_contains($config34,$marker34)) $errors[]="N0.34 runtime registration is missing: {$marker34}";
$migration34=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2026_08_16_002100_add_nexora_cloud_runtime.php');
foreach (['nx_runtime_nodes','nx_runtime_leases','nx_runtime_metrics','nx_runtime_backup_runs','nx_runtime_restore_plans'] as $table34) if (! str_contains($migration34,$table34)) $errors[]="N0.34 migration is missing table: {$table34}";
if (str_contains($migration34,'->after(')) $errors[]='N0.34 migration must remain portable and cannot use ->after().';
$leadership34=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Cloud'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'ClusterLeadership.php');
foreach (['scheduler-leader','RuntimeLeaseManager','NodeIdentity'] as $lead34) if (! str_contains($leadership34,$lead34)) $errors[]="N0.34 scheduler leadership boundary is missing: {$lead34}";
$lock34=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Cloud'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'LaravelDistributedLock.php');
if (! str_contains($lock34,'Cache::lock')) $errors[]='N0.34 distributed-lock implementation must use Laravel atomic cache locks behind the Nexora contract.';
$restore34=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Cloud'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'RestorePlanner.php');
if (! str_contains($restore34,"'automatic_destructive_restore' => false")) $errors[]='N0.34 restore planner must not silently enable unattended destructive restore.';
$console34=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'console.php');
foreach (['$leaderCheck','nexora:node:heartbeat','nexora:node:drain','nexora:node:activate','nexora:runtime:metrics','nexora:backup:create','nexora:backup:verify','nexora:restore:plan'] as $consoleMarker34) if (! str_contains($console34,$consoleMarker34)) $errors[]="N0.34 operational console capability is missing: {$consoleMarker34}";
if (! str_contains($console34,"Schedule::command('nexora:node:heartbeat')->everyMinute();")) $errors[]='Every runtime node must heartbeat independently; node heartbeat must not be scheduler-leader gated.';
foreach (["Schedule::command('nexora:publishing:run')->everyMinute()->withoutOverlapping()->when(\$leaderCheck);","name('nexora.automation.hourly')->hourly()->withoutOverlapping()->when(\$leaderCheck)"] as $leaderSchedule34) if (! str_contains($console34,$leaderSchedule34)) $errors[]="N0.34 existing scheduler work is not leader-gated: {$leaderSchedule34}";
$routes34=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php');
foreach (["'/health/live'","'/health/ready'","'/cloud'"] as $route34) if (! str_contains($routes34,$route34)) $errors[]="N0.34 health/operations route is missing: {$route34}";
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'Cloud',['tsx','ts']) as $feature34) {
    $source34=(string)file_get_contents($feature34);
    if (! str_contains($source34,'@nexora/admin-ui')) $errors[]="N0.34 Cloud UI must consume @nexora/admin-ui: {$feature34}";
    if (preg_match('/<(button|input|select|textarea)\\b/',$source34)===1) $errors[]="N0.34 Cloud feature UI contains raw interactive controls: {$feature34}";
    if (preg_match('/type=["\'](?:date|time|datetime-local|month|week)["\']/i',$source34)===1) $errors[]="N0.34 Cloud UI must use shared date/time primitives: {$feature34}";
}
$plan34=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md');
if (! str_contains($plan34,'| N0.34 | Cloud/HA/distributed runtime, queues, object storage, operational tooling | DONE |')) $errors[]='Master plan must mark N0.34 Cloud/HA runtime as DONE.';
if (! str_contains($plan34,'| N1.0 | Release Candidate certification')) $errors[]='Master plan must advance to N1.0 Release Candidate certification after N0.34.';
foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external34) if (! str_contains($plan34,$external34)) $errors[]="Externalized roadmap family disappeared in N0.34: {$external34}";


// N1.0 RC18 runtime limits/queue/cancellation safety + prior release-candidate certification gates.
foreach ([
    'scripts/certification-preflight.php',
    'scripts/lib/module-graph.php',
    'scripts/module-graph-verify.php',
    'scripts/frontend-contract-verify.php',
    'scripts/lib/laravel-runtime-contracts.php',
    'scripts/laravel-runtime-contract-verify.php',
    'scripts/lib/database-contracts.php',
    'app/Nexora/Foundation/Database/PortableNullableUnique.php',
    'scripts/database-contract-verify.php',
    'scripts/lib/security-contracts.php',
    'scripts/security-contract-verify.php',
    'scripts/lib/zero-install-contracts.php',
    'scripts/zero-install-contract-verify.php',
    'scripts/lib/browser-ux-contracts.php',
    'scripts/browser-ux-contract-verify.php',
    'scripts/browser-evidence-verify.php',
    'scripts/lib/performance-contracts.php',
    'scripts/performance-contract-verify.php',
    'scripts/performance-build-verify.php',
    'config/nexora-performance.php',
    'config/nexora-release.php',
    'app/Http/Middleware/ApplyPerformanceHeaders.php',
    'app/Nexora/Security/Session/SessionSecurityManager.php',
    'app/Nexora/Enterprise/Validation/TenantExists.php',
    'app/Nexora/Enterprise/Validation/TenantMemberExists.php',
    'app/Http/Middleware/EnsureTenantRouteBinding.php',
    'scripts/certify-release.php',
    'scripts/create-certification-database.php',
    'scripts/certify-database-matrix.php',
    'scripts/http-smoke.php',
    'scripts/zero-state-verify.php',
    'tests/Architecture/N100ReleaseCandidateArchitectureTest.php',
    'tests/Architecture/N100Rc4LaravelRuntimeArchitectureTest.php',
    'tests/Architecture/N100Rc5DatabaseArchitectureTest.php',
    'tests/Architecture/N100Rc6SecurityArchitectureTest.php',
    'tests/Architecture/N100Rc7ZeroInstallArchitectureTest.php',
    'tests/Architecture/N100Rc8BrowserUxArchitectureTest.php',
    'tests/Architecture/N100Rc9PerformanceArchitectureTest.php',
    'tests/Feature/Certification/PerformanceHeaderCertificationTest.php',
    'tests/Unit/InstallationRecoveryTest.php',
    'tests/Feature/Certification/InstallerRecoveryCertificationTest.php',
    'tests/Feature/Certification/SecurityBoundaryCertificationTest.php',
    'tests/Compatibility/DatabaseRoundTripCompatibilityTest.php',
    'docs/n1-0-rc5-database-stabilization.md',
    'docs/n1-0-rc6-security-stabilization.md',
    'docs/n1-0-rc7-zero-install-stabilization.md',
    'docs/n1-0-rc8-browser-ux-stabilization.md',
    'docs/n1-0-rc9-performance-packaging-stabilization.md',
    'docs/n1-0-rc9-verification.md',
    'docs/browser-certification-evidence.example.json',
    'scripts/lib/ha-final-contracts.php',
    'scripts/ha-final-contract-verify.php',
    'scripts/lib/final-evidence.php',
    'scripts/ha-evidence-verify.php',
    'scripts/backup-restore-evidence-verify.php',
    'scripts/final-evidence-verify.php',
    'scripts/lib/final-closure.php',
    'scripts/lib/final-closure-contracts.php',
    'scripts/final-closure-contract-verify.php',
    'scripts/final-closure-status.php',
    'scripts/final-target-run.php',
    'scripts/final-target-run.bat',
    'scripts/final-target-run.ps1',
    'scripts/final-target-run.sh',
    'scripts/lib/target-diagnostics-contracts.php',
    'scripts/target-diagnostics-contract-verify.php',
    'scripts/target-diagnostics.php',
    'scripts/target-diagnostics.bat',
    'scripts/target-diagnostics.ps1',
    'scripts/target-diagnostics.sh',
    'scripts/lib/upgrade-contracts.php',
    'scripts/upgrade-contract-verify.php',
    'scripts/lib/environment-contracts.php',
    'scripts/environment-contract-verify.php',
    'config/nexora-environment.php',
    '.env.production.example',
    'scripts/lib/dependency-contracts.php',
    'scripts/dependency-contract-verify.php',
    'scripts/dependency-audit.php',
    'scripts/dependency-runtime-verify.php',
    'scripts/dependency-provenance.php',
    'scripts/refresh-dependency-locks.bat',
    'scripts/refresh-dependency-locks.ps1',
    'scripts/refresh-dependency-locks.sh',
    'config/nexora-dependencies.php',
    'scripts/lib/filesystem-contracts.php',
    'scripts/filesystem-contract-verify.php',
    'config/nexora-filesystem.php',
    'app/Nexora/Foundation/Filesystem/AtomicFileWriter.php',
    'app/Nexora/Foundation/Filesystem/PortablePath.php',
    'app/Nexora/Foundation/Filesystem/FilesystemDoctor.php',
    'app/Console/Commands/Nexora/FilesystemDoctorCommand.php',
    'scripts/lib/transfer-contracts.php',
    'scripts/transfer-contract-verify.php',
    'config/nexora-transfers.php',
    'app/Nexora/Foundation/Transfers/TransferSafety.php',
    'app/Nexora/Foundation/Transfers/TransferDoctor.php',
    'app/Console/Commands/Nexora/TransferDoctorCommand.php',
    'app/Nexora/Foundation/Environment/EnvironmentDoctor.php',
    'app/Console/Commands/Nexora/EnvironmentDoctorCommand.php',
    'config/nexora-upgrade.php',
    'app/Nexora/Foundation/Upgrade/UpgradePlanStore.php',
    'app/Nexora/Foundation/Upgrade/UpgradeBackupVerifier.php',
    'app/Nexora/Foundation/Upgrade/UpgradeCompatibilityService.php',
    'app/Nexora/Foundation/Upgrade/UpgradeManager.php',
    'app/Console/Commands/Nexora/UpgradePreflightCommand.php',
    'app/Console/Commands/Nexora/UpgradePlanCommand.php',
    'app/Console/Commands/Nexora/UpgradeApplyCommand.php',
    'app/Console/Commands/Nexora/UpgradeStatusCommand.php',
    'tests/Architecture/N100Rc13UpgradeSafetyArchitectureTest.php',
    'tests/Architecture/N100Rc14EnvironmentArchitectureTest.php',
    'tests/Architecture/N100Rc15DependencyArchitectureTest.php',
    'tests/Architecture/N100Rc16FilesystemArchitectureTest.php',
    'tests/Architecture/N100Rc17TransferArchitectureTest.php',
    'tests/Architecture/N100Rc18RuntimeSafetyArchitectureTest.php',
    'tests/Architecture/N100Rc19ConcurrencyArchitectureTest.php',
    'tests/Architecture/N100Rc20FinalClosureIntegrityArchitectureTest.php',
    'tests/Unit/DatabaseVersionPolicyTest.php',
    'tests/Feature/Certification/CertificationDatabaseIsolationTest.php',
    'tests/Compatibility/CertificationDatabaseBindingCompatibilityTest.php',
    'tests/Support/AssertsCertificationDatabase.php',
    'scripts/lib/final-integrity-contracts.php',
    'scripts/final-integrity-contract-verify.php',
    'scripts/lib/source-attestation.php',
    'scripts/source-attestation.php',
    'scripts/source-attestation-contract-verify.php',
    'scripts/lib/release-artifact.php',
    'scripts/release-artifact-verify.php',
    'scripts/zero-install-evidence-verify.php',
    'scripts/upgrade-rehearsal-evidence-verify.php',
    'docs/zero-install-evidence.example.json',
    'docs/upgrade-rehearsal-evidence.example.json',
    'app/Nexora/Installation/Database/DatabaseVersionPolicy.php',
    'app/Nexora/Installation/Database/DatabaseRuntimeDoctor.php',
    '.github/workflows/release-certification.yml',
    'tests/Unit/ConcurrencyGuardTest.php',
    'tests/Feature/Certification/ConcurrencyCertificationTest.php',
    'scripts/lib/concurrency-contracts.php',
    'scripts/concurrency-contract-verify.php',
    'config/nexora-concurrency.php',
    'app/Nexora/Foundation/Database/ConcurrencyGuard.php',
    'app/Nexora/Foundation/Database/ConcurrencyDoctor.php',
    'app/Console/Commands/Nexora/ConcurrencyDoctorCommand.php',
    'database/migrations/2026_08_17_000100_add_nexora_concurrency_mutexes.php',
    'docs/n1-0-rc19-concurrency-safety.md',
    'docs/n1-0-rc19-verification.md',
    'scripts/lib/runtime-safety-contracts.php',
    'scripts/runtime-safety-contract-verify.php',
    'config/nexora-runtime.php',
    'app/Nexora/Foundation/Runtime/RuntimeLimitsDoctor.php',
    'app/Console/Commands/Nexora/RuntimeDoctorCommand.php',
    'app/Http/Middleware/ConfigureTrustedProxies.php',
    'app/Http/Middleware/EnforceRequestLimits.php',
    'tests/Unit/AtomicFileWriterTest.php',
    'tests/Unit/TransferSafetyTest.php',
    'tests/Unit/PortablePathTest.php',
    'docs/n1-0-rc13-upgrade-safety.md',
    'docs/n1-0-rc13-verification.md',
    'docs/n1-0-rc15-dependency-reproducibility.md',
    'docs/n1-0-rc15-verification.md',
    'docs/n1-0-rc17-transfer-safety.md',
    'docs/n1-0-rc17-verification.md',
    'docs/n1-0-rc18-runtime-safety.md',
    'docs/n1-0-rc18-verification.md',
    'docs/upgrade-backup-evidence.example.json',
    'config/nexora-ha.php',
    'app/Nexora/Cloud/Services/HaReadinessService.php',
    'app/Nexora/Cloud/Services/ClusterRehearsalService.php',
    'app/Nexora/Cloud/Services/BackupRestoreRehearsalService.php',
    'tests/Architecture/N100Rc10FinalCertificationArchitectureTest.php',
    'tests/Architecture/N100Rc11FinalTargetArchitectureTest.php',
    'tests/Architecture/N100Rc12TargetDiagnosticsArchitectureTest.php',
    'tests/Unit/Cloud/HaReadinessServiceTest.php',
    'tests/Unit/Cloud/ClusterRehearsalServiceTest.php',
    'docs/n1-0-rc10-final-certification.md',
    'docs/n1-0-rc10-verification.md',
    'docs/n1-0-rc11-final-target-run.md',
    'docs/n1-0-rc11-verification.md',
    'docs/n1-0-rc12-target-diagnostics.md',
    'docs/n1-0-rc12-verification.md',
    'docs/ha-certification-evidence.example.json',
    'docs/backup-restore-evidence.example.json',
    'scripts/target-runtime-run.php',
    'scripts/target-runtime-run.bat',
    'scripts/target-runtime-run.ps1',
    'scripts/target-runtime-run.sh',
    'scripts/lib/target-runtime-contracts.php',
    'scripts/target-runtime-contract-verify.php',
    'tests/Architecture/N100Rc22TargetRuntimeClosureArchitectureTest.php',
    'docs/n1-0-rc22-target-runtime-closure.md',
    'docs/n1-0-rc22-verification.md',
    'scripts/target-environment-bootstrap.php',
    'scripts/target-environment-bootstrap.bat',
    'scripts/target-environment-bootstrap.ps1',
    'scripts/target-environment-bootstrap.sh',
    'scripts/target-runtime-evidence-verify.php',
    'scripts/lib/target-resume-contracts.php',
    'scripts/target-resume-contract-verify.php',
    'tests/Architecture/N100Rc23TargetBootstrapResumeArchitectureTest.php',
    'docs/n1-0-rc23-target-bootstrap-resume.md',
    'scripts/target-prerequisite-intake.php',
    'scripts/target-prerequisite-intake.bat',
    'scripts/target-prerequisite-intake.ps1',
    'scripts/target-prerequisite-intake.sh',
    'scripts/dependency-lock-review.php',
    'scripts/dependency-lock-review.bat',
    'scripts/dependency-lock-review.ps1',
    'scripts/dependency-lock-review.sh',
    'scripts/lib/target-intake-contracts.php',
    'scripts/target-intake-contract-verify.php',
    'tests/Architecture/N100Rc24TargetPrerequisiteIntakeArchitectureTest.php',
    'docs/n1-0-rc24-target-prerequisite-lock-intake.md',
    'scripts/target-evidence-intake.php',
    'scripts/target-evidence-intake.bat',
    'scripts/target-evidence-intake.ps1',
    'scripts/target-evidence-intake.sh',
    'scripts/closure-dashboard.php',
    'scripts/lib/target-evidence-intake.php',
    'scripts/lib/target-evidence-contracts.php',
    'scripts/target-evidence-contract-verify.php',
    'tests/Architecture/N100Rc25UnifiedTargetEvidenceArchitectureTest.php',
    'docs/n1-0-rc25-unified-target-evidence.md',
    'scripts/target-prerequisite-remediate.php',
    'scripts/target-prerequisite-remediate.bat',
    'scripts/target-prerequisite-remediate.ps1',
    'scripts/target-prerequisite-remediate.sh',
    'scripts/lib/target-remediation-contracts.php',
    'scripts/target-remediation-contract-verify.php',
    'tests/Architecture/N100Rc27TargetPrerequisiteRemediationArchitectureTest.php',
    'docs/n1-0-rc27-target-prerequisite-remediation.md',
    'scripts/n1-c1-dependency-certify.php',
    'scripts/n1-c1-dependency-certify.bat',
    'scripts/n1-c1-dependency-certify.ps1',
    'scripts/n1-c1-dependency-certify.sh',
    'scripts/n1-c1-installed-dependency-verify.php',
    'scripts/lib/n1-c1-contracts.php',
    'scripts/n1-c1-contract-verify.php',
    'tests/Architecture/N100C1TargetEnvironmentDependenciesArchitectureTest.php',
    'docs/n1-0-c1-target-environment-dependencies.md',
    'scripts/n1-c1-evidence-verify.php',
    'scripts/n1-c2-laravel-runtime-certify.php',
    'scripts/n1-c2-laravel-runtime-certify.bat',
    'scripts/n1-c2-laravel-runtime-certify.ps1',
    'scripts/n1-c2-laravel-runtime-certify.sh',
    'scripts/n1-c2-evidence-verify.php',
    'scripts/lib/n1-c2-contracts.php',
    'scripts/n1-c2-contract-verify.php',
    'scripts/n1-c3-database-matrix-certify.php',
    'scripts/n1-c3-database-matrix-certify.bat',
    'scripts/n1-c3-database-matrix-certify.ps1',
    'scripts/n1-c3-database-matrix-certify.sh',
    'scripts/n1-c3-matrix-prerequisite.php',
    'scripts/n1-c3-database-matrix-evidence-verify.php',
    'scripts/lib/n1-c3-contracts.php',
    'scripts/n1-c3-contract-verify.php',
    'tests/Architecture/N100C3StrictFiveDatabaseMatrixArchitectureTest.php',
    'docs/n1-0-c3-five-database-matrix.md',
    'scripts/n1-c4-operations-certify.php',
    'scripts/n1-c4-operations-certify.bat',
    'scripts/n1-c4-operations-certify.ps1',
    'scripts/n1-c4-operations-certify.sh',
    'scripts/n1-c4-evidence-prepare.php',
    'scripts/n1-c4-evidence-verify.php',
    'scripts/lib/n1-c4-contracts.php',
    'scripts/n1-c4-contract-verify.php',
    'tests/Architecture/N100C4OperationalRecoveryArchitectureTest.php',
    'docs/n1-0-c4-install-upgrade-backup-recovery.md',
    'config/nexora-browser-certification.php',
    'scripts/n1-c5-browser-performance-certify.php',
    'scripts/n1-c5-browser-performance-certify.bat',
    'scripts/n1-c5-browser-performance-certify.ps1',
    'scripts/n1-c5-browser-performance-certify.sh',
    'scripts/n1-c5-evidence-prepare.php',
    'scripts/n1-c5-evidence-import.php',
    'scripts/n1-c5-browser-evidence-verify.php',
    'scripts/n1-c5-web-vitals-evidence-verify.php',
    'scripts/n1-c5-evidence-verify.php',
    'scripts/lib/n1-c5-browser-performance.php',
    'scripts/lib/n1-c5-contracts.php',
    'scripts/n1-c5-contract-verify.php',
    'tests/Architecture/N100C5BrowserAccessibilityPerformanceArchitectureTest.php',
    'docs/n1-0-c5-browser-accessibility-performance.md',
    'scripts/n1-c6-final-certify.php',
    'scripts/n1-c6-final-certify.bat',
    'scripts/n1-c6-final-certify.ps1',
    'scripts/n1-c6-final-certify.sh',
    'scripts/n1-c6-evidence-prepare.php',
    'scripts/n1-c6-ha-evidence-import.php',
    'scripts/n1-c6-evidence-verify.php',
    'scripts/lib/n1-c6-final.php',
    'scripts/lib/n1-c6-contracts.php',
    'scripts/n1-c6-contract-verify.php',
    'tests/Architecture/N100C6HaFinalReleaseArchitectureTest.php',
    'docs/n1-0-c6-ha-final-release.md',
    'scripts/n1-target-execution.php',
    'scripts/n1-target-execution.bat',
    'scripts/n1-target-execution.ps1',
    'scripts/n1-target-execution.sh',
    'scripts/lib/n1-target-execution-contracts.php',
    'scripts/lib/target-support-capsule.php',
    'scripts/n1-target-support-capsule.php',
    'scripts/n1-target-execution-contract-verify.php',
    'scripts/target-prerequisite-restart-verify.php',
    'scripts/n1-target-next-action.php',
    'scripts/n1-target-next-action.bat',
    'scripts/n1-target-next-action.ps1',
    'scripts/n1-target-next-action.sh',
    'scripts/n1-c6-target-url-verify.php',
    'scripts/lib/n1-target-plan.php',
    'scripts/lib/n1-target-maximum-closure-contracts.php',
    'scripts/n1-target-maximum-closure-contract-verify.php',
    'config/nexora-certification-evidence.php',
    'scripts/dependency-lock-refresh.php',
    'scripts/lib/target-composer.php',
    'scripts/refresh-dependency-locks.bat',
    'scripts/refresh-dependency-locks.ps1',
    'scripts/refresh-dependency-locks.sh',
    'tests/Architecture/N100TargetExecutionPackArchitectureTest.php',
    'docs/n1-0-target-execution-pack.md',
    'docs/n1-0-target-execution-v2-3-maximum-closure.md',
    'docs/n1-0-target-execution-v2-5-release-trust.md',
    'tests/Architecture/N100C2LaravelRuntimeCoreDatabaseArchitectureTest.php',
    'docs/n1-0-c2-laravel-runtime-core-db.md',
] as $artifact100) {
    $path=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$artifact100);
    if (! is_file($path) || filesize($path)===0) $errors[]="Missing N1.0 RC certification artifact: {$artifact100}";
}
$config100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'nexora.php');
if (preg_match("/'version' => '1\.0\.0-rc\.(\d+)'/",$config100,$currentRc100)!==1 || (int)($currentRc100[1]??0)<55) $errors[]='N1.0 target execution pack platform version must be rc.55 or newer while host/clock closure is active.';
require_once $root.'/scripts/lib/n1-c4-contracts.php';
$c4Guard=nexoraAnalyzeN10C4Contracts($root);
foreach($c4Guard['errors'] as $error)$errors[]='N1.0-C4 contract: '.$error;
require_once $root.'/scripts/lib/n1-c5-contracts.php';
$c5Guard=nexoraAnalyzeN10C5Contracts($root);
foreach($c5Guard['errors'] as $error)$errors[]='N1.0-C5 contract: '.$error;
if (($c5Guard['metrics']['matrix_rows'] ?? 0)!==36 || ($c5Guard['metrics']['browsers'] ?? 0)!==3) $errors[]='N1.0-C5 must retain 36 Chrome/Edge/Firefox matrix rows.';
require_once $root.'/scripts/lib/n1-c6-contracts.php';
$c6Guard=nexoraAnalyzeN10C6Contracts($root);
foreach($c6Guard['errors'] as $error)$errors[]='N1.0-C6 contract: '.$error;
if (($c6Guard['metrics']['prior_chunks'] ?? 0)!==5 || ($c6Guard['metrics']['ha_checks'] ?? 0)<34 || ($c6Guard['metrics']['ordered_gates'] ?? 0)<20) $errors[]='N1.0-C6 must retain five prior chunk prerequisites, at least thirty-four HA checks and twenty ordered final gates.';
require_once $root.'/scripts/lib/n1-target-execution-contracts.php';
$targetExecutionGuard=nexoraAnalyzeN10TargetExecutionContracts($root);
foreach($targetExecutionGuard['errors'] as $error)$errors[]='N1.0 target execution contract: '.$error;
if (($targetExecutionGuard['metrics']['automated_chunks'] ?? 0)!==3 || ($targetExecutionGuard['metrics']['operator_chunks'] ?? 0)!==3) $errors[]='N1.0 target execution pack must retain three automated and three operator chunks.';
if (($targetExecutionGuard['metrics']['lock_refresh_wrappers'] ?? 0)!==3 || ($targetExecutionGuard['metrics']['trusted_composer_discovery'] ?? 0)!==1) $errors[]='N1.0 target execution pack v2 must retain cross-platform lock refresh and trusted Composer discovery.';
if (($targetExecutionGuard['metrics']['support_capsule'] ?? 0)!==1) $errors[]='N1.0 target execution pack must generate a ZIP-independent redacted support capsule.';
if (($targetExecutionGuard['metrics']['automatic_lock_acceptance'] ?? 1)!==0) $errors[]='N1.0 target execution pack must never auto-accept dependency locks.';
require_once $root.'/scripts/lib/n1-target-maximum-closure-contracts.php';
$maximumClosureGuard=nexoraAnalyzeN10TargetMaximumClosureContracts($root);
foreach($maximumClosureGuard['errors'] as $error)$errors[]='N1.0 maximum closure contract: '.$error;
if (($maximumClosureGuard['metrics']['freshness_domains']??0)!==7 || ($maximumClosureGuard['metrics']['target_url_gate']??0)!==1 || ($maximumClosureGuard['metrics']['release_input_freeze']??0)!==1) $errors[]='N1.0 maximum closure batch must retain seven evidence freshness domains, target URL binding and release input freeze.';
$release100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'build-production-release.php');
foreach (['config/nexora.php','config/nexora-release.php','certification-pass','build-assets.json','final-evidence.json','dependency-audit.json','performance_report_sha256','final_evidence_report_sha256','dependency_audit_report_sha256','dependency_provenance_report_sha256','source_tree_sha256','forbidden_archive_prefixes','config/nexora-filesystem.php','atomic_state_publication','case_portability_certified','config/nexora-transfers.php','bounded_streaming','archive_expansion_budgets','config/nexora-runtime.php','runtime_safety','queue_timeout_retry_alignment','config/nexora-concurrency.php','config/nexora-certification-evidence.php','concurrency','portable_transaction_mutex','external_effect_semantics','nexora-release.json'] as $releaseMarker100) if (! str_contains(str_replace('\\','/',$release100),$releaseMarker100)) $errors[]="N1.0 certified production release boundary is missing: {$releaseMarker100}";
if (str_contains($release100,"\$version = '0.26.0'")) $errors[]='Production release builder must never hard-code the obsolete N0.26 version.';
$certRunner100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'certify-release.php');
foreach (['certification-preflight.php','module-graph-verify.php','frontend-contract-verify.php','laravel-runtime-contract-verify.php','database-contract-verify.php','zero-install-contract-verify.php','browser-ux-contract-verify.php','inertia-frontend-contract-verify.php','browser-evidence-verify.php','performance-contract-verify.php','performance-build-verify.php','ha-final-contract-verify.php','final-closure-contract-verify.php','target-diagnostics-contract-verify.php','target-runtime-contract-verify.php','target-resume-contract-verify.php','target-intake-contract-verify.php','target-remediation-contract-verify.php','n1-c1-contract-verify.php','n1-c2-contract-verify.php','n1-c3-contract-verify.php','n1-c4-contract-verify.php','n1-c5-contract-verify.php','n1-c6-contract-verify.php','n1-target-execution-contract-verify.php','n1-target-maximum-closure-contract-verify.php','n1-target-release-trust-contract-verify.php','n1-target-supply-chain-contract-verify.php','n1-target-update-trust-contract-verify.php','target-evidence-contract-verify.php','upgrade-contract-verify.php','n1-target-distributed-upgrade-contract-verify.php','environment-contract-verify.php','dependency-contract-verify.php','filesystem-contract-verify.php','transfer-contract-verify.php','runtime-safety-contract-verify.php','concurrency-contract-verify.php','final-integrity-contract-verify.php','source-attestation-contract-verify.php','source-attestation.php','release-artifact-verify.php','zero-install-evidence-verify.php','upgrade-rehearsal-evidence-verify.php','nexora:database:doctor','nexora:concurrency:doctor','dependency-runtime-verify.php','dependency-provenance.php','dependency-audit.php','composer.lock','package-lock.json','nexora:environment:doctor','nexora:filesystem:doctor','nexora:transfer:doctor','nexora:runtime:doctor','ha-evidence-verify.php','backup-restore-evidence-verify.php','final-evidence-verify.php','security-contract-verify.php','certify-database-matrix.php','package:discover','schedule:list','migrate:fresh','db:seed','migrate:reset','nexora:runtime:sync','artisan','typecheck','npm','build-production-release.php'] as $certMarker100) if (! str_contains($certRunner100,$certMarker100)) $errors[]="N1.0 certification runner is missing required gate marker: {$certMarker100}";
foreach (['quality-check.bat','quality-check.ps1','quality-check.sh'] as $quality100) {
    $source100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.$quality100);
    if (! str_contains($source100,'certify-release.php')) $errors[]="N1.0 quality wrapper must delegate to the single certification engine: {$quality100}";
}
foreach (['setup-zero.bat','setup-zero.ps1','setup-zero.sh'] as $zero100) {
    $source100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.$zero100);
    if (preg_match('/(?:copy|Copy-Item|cp)\s+[^\r\n]*\.env\.example[^\r\n]*\.env/i',$source100)===1) $errors[]="N1.0 true zero-install test must not pre-create .env: {$zero100}";
    if (! str_contains($source100,'zero-state-verify.php')) $errors[]="N1.0 zero-state verification is missing from: {$zero100}";
}
$baseTheme100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'nexora-base'.DIRECTORY_SEPARATOR.'nexora.json');
if (! str_contains($baseTheme100,'>=0.34 <2.0')) $errors[]='N1.0 RC built-in theme compatibility window must include N0.34 through the 1.x line.';
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Architecture',['php']) as $architecture100) {
    $source100=(string)file_get_contents($architecture100);
    $basename100=basename($architecture100);
    if (! str_starts_with($basename100,'N100') && preg_match('/assertStringContainsString\([^\r\n]*[\"\']version[\"\']/', $source100)===1) {
        $errors[]="Historical architecture test must not freeze the mutable top-level platform version: {$architecture100}";
    }
}
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Architecture',['php']) as $currentRcArchitecture100) {
    $currentRcSource100=(string)file_get_contents($currentRcArchitecture100);
    $currentRcBase100=basename($currentRcArchitecture100);
    if ($currentRcBase100!=='N100C2LaravelRuntimeCoreDatabaseArchitectureTest.php' && preg_match('/1\.0\.0-rc\.\d+/', $currentRcSource100)===1) {
        $errors[]="Only the current N1.0-C2 architecture test may freeze a mutable 1.0.0-rc.* platform version: {$currentRcArchitecture100}";
    }
}
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Architecture',['php']) as $currentRcPlanArchitecture100) {
    $currentRcPlanSource100=(string)file_get_contents($currentRcPlanArchitecture100);
    $currentRcPlanBase100=basename($currentRcPlanArchitecture100);
    if ($currentRcPlanBase100!=='N100C2LaravelRuntimeCoreDatabaseArchitectureTest.php' && preg_match('/CERTIFYING — (?:RC\d+|N1\.0-C[1-6])/', $currentRcPlanSource100)===1) {
        $errors[]="Historical architecture tests must not freeze mutable RC plan status: {$currentRcPlanArchitecture100}";
    }
}
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'module-graph.php';
$moduleGraph100=nexoraAnalyzeModuleGraph($root);
foreach ($moduleGraph100['errors'] as $moduleError100) $errors[]='N1.0 module graph: '.$moduleError100;
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'laravel-runtime-contracts.php';
$laravelRuntime100=nexoraAnalyzeLaravelRuntimeContracts($root);
foreach ($laravelRuntime100['errors'] as $runtimeError100) $errors[]='N1.0 Laravel runtime contract: '.$runtimeError100;
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'database-contracts.php';
$databaseContracts100=nexoraAnalyzeDatabaseContracts($root);
foreach ($databaseContracts100['errors'] as $databaseError100) $errors[]='N1.0 database contract: '.$databaseError100;
if (($databaseContracts100['metrics']['tenant_tables'] ?? 0)!==51 || ($databaseContracts100['metrics']['tenant_models'] ?? 0)!==51) $errors[]='N1.0 tenant model/table manifest must remain exactly aligned at 51 roots.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'security-contracts.php';
$securityContracts100=nexoraAnalyzeSecurityContracts($root);
foreach ($securityContracts100['errors'] as $securityError100) $errors[]='N1.0 security contract: '.$securityError100;
if (($securityContracts100['metrics']['raw_tenant_exists'] ?? -1)!==0) $errors[]='N1.0 must not use raw exists rules for tenant-owned request references.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'zero-install-contracts.php';
$zeroInstallContracts100=nexoraAnalyzeZeroInstallContracts($root);
foreach ($zeroInstallContracts100['errors'] as $zeroError100) $errors[]='N1.0 zero-install contract: '.$zeroError100;
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'browser-ux-contracts.php';
$browserUxContracts100=nexoraAnalyzeBrowserUxContracts($root);
foreach ($browserUxContracts100['errors'] as $browserError100) $errors[]='N1.0 browser/UX contract: '.$browserError100;
if (($browserUxContracts100['metrics']['physical_text_alignment'] ?? -1)!==0) $errors[]='N1.0 RC8 Admin must use logical text alignment for RTL.';
if (($browserUxContracts100['metrics']['physical_positioning'] ?? -1)!==0) $errors[]='N1.0 RC8 shared Admin surfaces must use logical start/end positioning.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'inertia-frontend-contracts.php';
$inertiaFrontendContracts100=nexoraAnalyzeInertiaFrontendContracts($root);
foreach ($inertiaFrontendContracts100['errors'] as $frontendTypeError100) $errors[]='N1.0 RC21 Inertia frontend type contract: '.$frontendTypeError100;
if (($inertiaFrontendContracts100['metrics']['transform_chains'] ?? -1)!==0) $errors[]='N1.0 RC21 must not chain useForm.transform() into submit methods.';
if (($inertiaFrontendContracts100['metrics']['unsafe_router_payloads'] ?? -1)!==0) $errors[]='N1.0 RC21 router payloads must use Inertia RequestPayload-compatible values.';
if (($inertiaFrontendContracts100['metrics']['navlink_children'] ?? -1)!==0) $errors[]='N1.0 RC21 NavLink usage must match the label+icon API.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'performance-contracts.php';
$performanceContracts100=nexoraAnalyzePerformanceContracts($root);
foreach ($performanceContracts100['errors'] as $performanceError100) $errors[]='N1.0 performance/packaging contract: '.$performanceError100;
if (($performanceContracts100['metrics']['release_required_entries'] ?? 0)<9) $errors[]='N1.0 RC9 production release policy must retain the required production entries.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'ha-final-contracts.php';
$haFinalContracts100=nexoraAnalyzeHaFinalContracts($root);
foreach ($haFinalContracts100['errors'] as $haFinalError100) $errors[]='N1.0 RC10 HA/final evidence contract: '.$haFinalError100;
if (($haFinalContracts100['metrics']['ha_checks'] ?? 0)!==7) $errors[]='N1.0 RC10 must retain seven strict HA readiness checks.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'final-closure-contracts.php';
$finalClosureContracts100=nexoraAnalyzeFinalClosureContracts($root);
foreach ($finalClosureContracts100['errors'] as $finalClosureError100) $errors[]='N1.0 RC11 final closure contract: '.$finalClosureError100;
if (($finalClosureContracts100['metrics']['closure_domains'] ?? 0)!==11) $errors[]='N1.0 final closure must retain eleven fail-closed status domains after RC20.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'target-diagnostics-contracts.php';
$targetDiagnosticsContracts100=nexoraAnalyzeTargetDiagnosticsContracts($root);
foreach ($targetDiagnosticsContracts100['errors'] as $targetDiagnosticsError100) $errors[]='N1.0 RC12 target diagnostics contract: '.$targetDiagnosticsError100;
if (($targetDiagnosticsContracts100['metrics']['diagnostic_groups'] ?? 0)!==6) $errors[]='N1.0 RC12 must retain six target diagnostic groups.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'target-runtime-contracts.php';
$targetRuntimeContracts100=nexoraAnalyzeTargetRuntimeContracts($root);
foreach ($targetRuntimeContracts100['errors'] as $targetRuntimeError100) $errors[]='N1.0 RC22 target runtime contract: '.$targetRuntimeError100;
if (($targetRuntimeContracts100['metrics']['wrappers'] ?? 0)!==3) $errors[]='N1.0 RC22 must retain BAT/PowerShell/shell target runtime wrappers.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'target-resume-contracts.php';
$targetResumeContracts100=nexoraAnalyzeTargetResumeContracts($root);
foreach ($targetResumeContracts100['errors'] as $targetResumeError100) $errors[]='N1.0 RC23 target bootstrap/resume contract: '.$targetResumeError100;
if (($targetResumeContracts100['metrics']['bootstrap_wrappers'] ?? 0)!==3) $errors[]='N1.0 RC23 must retain BAT/PowerShell/shell target bootstrap wrappers.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'target-intake-contracts.php';
$targetIntakeContracts100=nexoraAnalyzeTargetIntakeContracts($root);
foreach ($targetIntakeContracts100['errors'] as $targetIntakeError100) $errors[]='N1.0 RC24 target prerequisite/lock intake contract: '.$targetIntakeError100;
if (($targetIntakeContracts100['metrics']['intake_wrappers'] ?? 0)!==3 || ($targetIntakeContracts100['metrics']['lock_review_wrappers'] ?? 0)!==3) $errors[]='N1.0 RC24 must retain three target-intake and three lock-review wrappers.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'target-evidence-contracts.php';
$targetEvidenceContracts100=nexoraAnalyzeTargetEvidenceContracts($root);
foreach ($targetEvidenceContracts100['errors'] as $targetEvidenceError100) $errors[]='N1.0 RC25 target evidence intake contract: '.$targetEvidenceError100;
if (($targetEvidenceContracts100['metrics']['wrappers'] ?? 0)!==3 || ($targetEvidenceContracts100['metrics']['operator_evidence'] ?? 0)!==5) $errors[]='N1.0 RC25 must retain three intake wrappers and five operator evidence domains.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'target-orchestrator-contracts.php';
$targetOrchestratorContracts100=nexoraAnalyzeTargetOrchestratorContracts($root);
foreach ($targetOrchestratorContracts100['errors'] as $targetOrchestratorError100) $errors[]='N1.0 RC26 target certification orchestrator contract: '.$targetOrchestratorError100;
if (($targetOrchestratorContracts100['metrics']['wrappers'] ?? 0)!==3 || ($targetOrchestratorContracts100['metrics']['automatic_lock_acceptance'] ?? 1)!==0 || ($targetOrchestratorContracts100['metrics']['direct_destructive_db_commands'] ?? 1)!==0) $errors[]='N1.0 RC26 must retain three orchestrator wrappers with no automatic lock acceptance or direct destructive DB commands.';

require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'target-remediation-contracts.php';
$targetRemediationContracts100=nexoraAnalyzeTargetRemediationContracts($root);
foreach ($targetRemediationContracts100['errors'] as $targetRemediationError100) $errors[]='N1.0 RC27 target prerequisite remediation contract: '.$targetRemediationError100;
if (($targetRemediationContracts100['metrics']['wrappers'] ?? 0)!==3 || ($targetRemediationContracts100['metrics']['automatic_downloads'] ?? 1)!==0 || ($targetRemediationContracts100['metrics']['automatic_lock_acceptance'] ?? 1)!==0) $errors[]='N1.0 RC27 must retain three remediation wrappers with zero automatic downloads and zero automatic lock acceptance.';
require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'upgrade-contracts.php';
$upgradeContracts100=nexoraAnalyzeUpgradeContracts($root);
foreach ($upgradeContracts100['errors'] as $upgradeError100) $errors[]='N1.0 RC13 upgrade safety contract: '.$upgradeError100;
if (($upgradeContracts100['metrics']['commands'] ?? 0)<4) $errors[]='N1.0 RC13+ must retain at least the four original upgrade operator commands.';
if (($upgradeContracts100['metrics']['automatic_database_rollback'] ?? 1)!==0) $errors[]='N1.0 RC13 must keep automatic database rollback disabled.';

require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'environment-contracts.php';
$environmentContracts100=nexoraAnalyzeEnvironmentContracts($root);
foreach ($environmentContracts100['errors'] as $environmentError100) $errors[]='N1.0 RC14 environment/config contract: '.$environmentError100;
if (($environmentContracts100['metrics']['runtime_env_calls'] ?? -1)!==0) $errors[]='N1.0 RC14 must keep runtime env() calls outside config at zero.';
if (($environmentContracts100['metrics']['production_template_keys'] ?? 0)<40) $errors[]='N1.0 RC14 production environment template is incomplete.';


require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'dependency-contracts.php';
$dependencyContracts100=nexoraAnalyzeDependencyContracts($root,false);
foreach ($dependencyContracts100['errors'] as $dependencyError100) $errors[]='N1.0 RC15 dependency contract: '.$dependencyError100;
if (($dependencyContracts100['metrics']['direct_prod_dependencies'] ?? 0)<10) $errors[]='N1.0 RC15 dependency manifest audit unexpectedly found too few direct production dependencies.';

require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'filesystem-contracts.php';
$filesystemContracts100=nexoraAnalyzeFilesystemContracts($root);
foreach ($filesystemContracts100['errors'] as $filesystemError100) $errors[]='N1.0 RC16 filesystem contract: '.$filesystemError100;
if (($filesystemContracts100['metrics']['case_collisions'] ?? -1)!==0) $errors[]='N1.0 RC16 repository must have zero case-insensitive path collisions.';
if (($filesystemContracts100['metrics']['windows_invalid_paths'] ?? -1)!==0) $errors[]='N1.0 RC16 repository must have zero Windows-invalid path components.';

require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'transfer-contracts.php';
$transferContracts100=nexoraAnalyzeTransferContracts($root);
foreach ($transferContracts100['errors'] as $transferError100) $errors[]='N1.0 RC17 transfer contract: '.$transferError100;
if (($transferContracts100['metrics']['unsafe_backup_full_loads'] ?? -1)!==0) $errors[]='N1.0 RC17 backups must have zero complete-artifact PHP memory loads.';
if (($transferContracts100['metrics']['unbounded_archive_extracts'] ?? -1)!==0) $errors[]='N1.0 RC17 package installers must have zero unbounded archive extraction paths.';

require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'runtime-safety-contracts.php';
$runtimeSafetyContracts100=nexoraAnalyzeRuntimeSafetyContracts($root);
foreach ($runtimeSafetyContracts100['errors'] as $runtimeSafetyError100) $errors[]='N1.0 RC18 runtime safety contract: '.$runtimeSafetyError100;
if (($runtimeSafetyContracts100['metrics']['queue_jobs'] ?? 0)!==4) $errors[]='N1.0 RC18 must retain four bounded first-party queue jobs.';
if (($runtimeSafetyContracts100['metrics']['jobs_without_fail_on_timeout'] ?? -1)!==0) $errors[]='N1.0 RC18 queue jobs must fail closed on timeout.';

require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'concurrency-contracts.php';
$concurrencyContracts100=nexoraAnalyzeConcurrencyContracts($root);
foreach ($concurrencyContracts100['errors'] as $concurrencyError100) $errors[]='N1.0 RC19 concurrency contract: '.$concurrencyError100;
if (($concurrencyContracts100['metrics']['critical_direct_transactions'] ?? -1)!==0) $errors[]='N1.0 RC19 critical mutation services must have zero direct unbounded DB::transaction calls.';
if (($concurrencyContracts100['metrics']['external_effect_exactly_once_claims'] ?? -1)!==0) $errors[]='N1.0 RC19 must not claim exactly-once semantics for external SMTP/HTTP side effects.';

require_once $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'final-integrity-contracts.php';
$finalIntegrityContracts100=nexoraAnalyzeFinalIntegrityContracts($root);
foreach ($finalIntegrityContracts100['errors'] as $finalIntegrityError100) $errors[]='N1.0 RC20 final closure integrity contract: '.$finalIntegrityError100;
if (($finalIntegrityContracts100['metrics']['closure_domains'] ?? 0)!==11) $errors[]='N1.0 RC20 must retain eleven closure domains.';
if (($finalIntegrityContracts100['metrics']['primary_db_families'] ?? 0)!==5) $errors[]='N1.0 RC20 final certification must cover all five primary DB families.';

$enterprise100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Nexora'.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'EnterpriseModule.php');
if (! str_contains($enterprise100,"new ModuleDependency('nexora.identity-access','^0.5')")) $errors[]='Enterprise module must depend on the registered nexora.identity-access ^0.5 module.';
if (str_contains($enterprise100,"new ModuleDependency('nexora.identity','^0.3')")) $errors[]='Obsolete missing nexora.identity module dependency must not return.';
$runtimeHeartbeat100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Middleware'.DIRECTORY_SEPARATOR.'RuntimeNodeHeartbeat.php');
if (preg_match('/function\\s+handle\\s*\\(\\s*Request\\s+\\$request\\s*,\\s*Closure\\s+\\$next\\s*\\)\\s*:\\s*Response/',$runtimeHeartbeat100)!==1) $errors[]='RuntimeNodeHeartbeat must preserve Laravel standard two-argument middleware handle signature.';
foreach (['private readonly NodeIdentity $identity','private readonly NodeManager $nodes','private readonly InstallationState $installation'] as $dependency100) if (! str_contains($runtimeHeartbeat100,$dependency100)) $errors[]="RuntimeNodeHeartbeat must constructor-inject runtime dependency: {$dependency100}";
if (! str_contains($runtimeHeartbeat100,'if (! $this->installation->isInstalled())')) $errors[]='RuntimeNodeHeartbeat must bypass runtime readiness fencing before the installation lock exists.';
foreach (nexoraFiles($root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin',['tsx','ts']) as $frontend100) {
    $frontendSource100=(string)file_get_contents($frontend100);
    if (preg_match('/\\.transform\\s*\\([\\s\\S]*?\\)\\s*\\.\\s*(?:get|post|put|patch|delete)\\s*\\(/m',$frontendSource100)===1) $errors[]="Inertia v3 form transform() must not be chained to a submit method: {$frontend100}";
}
foreach (['resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx','resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx'] as $sectionNav100) {
    $sectionNavSource100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$sectionNav100));
    if (str_contains($sectionNavSource100,'NavLink')) $errors[]="Horizontal section navigation must use ButtonLink, not sidebar NavLink: {$sectionNav100}";
}
$plan100=(string)file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'NEXORA_PLAN_STATUS.md');
if (! str_contains($plan100,'| N1.0 | Release Candidate certification: zero-install, migrations, build, tests, security, accessibility, performance, backup/restore, HA and packaging | CERTIFYING — TARGET EXECUTION / C1-C6 REAL EVIDENCE |')) $errors[]='Master plan must mark N1.0 as CERTIFYING — TARGET EXECUTION while C1-C6 code blocks are applied and real evidence remains pending.';
if (! str_contains($plan100,'| N1.1 | Admin UX / Design System certification and whole-system consistency audit | NEXT AFTER N1.0 PASS |')) $errors[]='N1.1 must remain blocked behind N1.0 certification.';


foreach(['scripts/lib/n1-certification-session.php','scripts/n1-certification-session.php','scripts/n1-target-session-release-contract-verify.php','scripts/lib/final-release-seal.php','scripts/final-release-seal-verify.php'] as $requiredV24){if(!is_file($root.'/'.$requiredV24))$errors[]='N1.0 v2.4 required source missing ['.$requiredV24.']';}
$sessionSealOut=[];$sessionSealCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-session-release-contract-verify.php').' 2>&1',$sessionSealOut,$sessionSealCode);if($sessionSealCode!==0)$errors[]='N1.0 session/release-seal contract verifier failed: '.implode(' | ',$sessionSealOut);

foreach(['config/nexora-release-trust.php','scripts/lib/n1-certified-toolchain.php','scripts/n1-certified-toolchain.php','scripts/lib/release-signature.php','scripts/release-signing-key.php','scripts/lib/release-archive-hygiene.php','scripts/release-offline-verify.php','scripts/lib/n1-target-release-trust-contracts.php','scripts/n1-target-release-trust-contract-verify.php'] as $requiredV25){if(!is_file($root.'/'.$requiredV25))$errors[]='N1.0 v2.5 release-trust source missing ['.$requiredV25.']';}
$releaseTrustOut=[];$releaseTrustCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-release-trust-contract-verify.php').' 2>&1',$releaseTrustOut,$releaseTrustCode);if($releaseTrustCode!==0)$errors[]='N1.0 release-trust contract verifier failed: '.implode(' | ',$releaseTrustOut);
foreach(['config/nexora-supply-chain.php','scripts/lib/release-trust-anchor.php','scripts/release-trust-anchor.php','scripts/dependency-sbom.php','scripts/lib/production-dependency-stage.php','scripts/production-dependency-stage.php','scripts/release-provenance.php','scripts/lib/release-content-manifest.php','scripts/lib/n1-target-supply-chain-contracts.php','scripts/n1-target-supply-chain-contract-verify.php'] as $requiredV26){if(!is_file($root.'/'.$requiredV26))$errors[]='N1.0 v2.6 supply-chain source missing ['.$requiredV26.']';}
$supplyChainOut=[];$supplyChainCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-supply-chain-contract-verify.php').' 2>&1',$supplyChainOut,$supplyChainCode);if($supplyChainCode!==0)$errors[]='N1.0 supply-chain contract verifier failed: '.implode(' | ',$supplyChainOut);

foreach(['config/nexora-update-trust.php','scripts/lib/trusted-update.php','scripts/trusted-update-trust-anchor.php','scripts/trusted-update-admit.php','scripts/trusted-update-stage.php','scripts/trusted-update-candidate.php','scripts/trusted-update-admit-candidate.php','app/Nexora/Foundation/Upgrade/TrustedUpdateAdmission.php','scripts/lib/n1-target-update-trust-contracts.php','scripts/n1-target-update-trust-contract-verify.php'] as $requiredV27){if(!is_file($root.'/'.$requiredV27))$errors[]='N1.0 v2.7 trusted-update source missing ['.$requiredV27.']';}
$updateTrustOut=[];$updateTrustCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-update-trust-contract-verify.php').' 2>&1',$updateTrustOut,$updateTrustCode);if($updateTrustCode!==0)$errors[]='N1.0 trusted-update contract verifier failed: '.implode(' | ',$updateTrustOut);

foreach(['app/Nexora/Foundation/Upgrade/UpgradeMaintenanceLease.php','app/Nexora/Foundation/Upgrade/UpgradePostHealthCheck.php','app/Nexora/Foundation/Upgrade/UpgradeRecoveryDecisionStore.php','app/Console/Commands/Nexora/UpgradeRecoveryRecordCommand.php','app/Console/Commands/Nexora/UpgradeMaintenanceLeaseCommand.php'] as $requiredV29){if(!is_file($root.'/'.$requiredV29))$errors[]='N1.0 v2.9 operational upgrade safety source missing ['.$requiredV29.']';}
$upgradeV29Out=[];$upgradeV29Code=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/upgrade-contract-verify.php').' 2>&1',$upgradeV29Out,$upgradeV29Code);if($upgradeV29Code!==0)$errors[]='N1.0 v2.9 operational upgrade contract verifier failed: '.implode(' | ',$upgradeV29Out);

foreach(['app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php','app/Nexora/Foundation/Upgrade/UpgradeMigrationLedger.php','app/Console/Commands/Nexora/UpgradeClusterStatusCommand.php','app/Console/Commands/Nexora/UpgradeNodeStatusCommand.php','app/Console/Commands/Nexora/UpgradeClusterLockCommand.php','app/Console/Commands/Nexora/UpgradeSchedulerLeaseCommand.php','scripts/lib/n1-target-distributed-upgrade-contracts.php','scripts/n1-target-distributed-upgrade-contract-verify.php'] as $requiredV30){if(!is_file($root.'/'.$requiredV30))$errors[]='N1.0 v3.0 distributed upgrade source missing ['.$requiredV30.']';}
$distributedUpgradeOut=[];$distributedUpgradeCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-distributed-upgrade-contract-verify.php').' 2>&1',$distributedUpgradeOut,$distributedUpgradeCode);if($distributedUpgradeCode!==0)$errors[]='N1.0 v3.0 distributed upgrade contract verifier failed: '.implode(' | ',$distributedUpgradeOut);
if (($upgradeContracts100['metrics']['automatic_peer_drain'] ?? 1)!==0) $errors[]='N1.0 v3.0 must keep peer draining explicit/operator-controlled.';

foreach(['app/Nexora/Cloud/Services/RuntimeActivityTracker.php','app/Nexora/Cloud/Services/RuntimeVersionGuard.php','app/Nexora/Foundation/Upgrade/UpgradeMigrationSafety.php','app/Console/Commands/Nexora/UpgradeQuiescenceCommand.php','scripts/lib/n1-target-runtime-quiescence-contracts.php','scripts/n1-target-runtime-quiescence-contract-verify.php'] as $requiredV31){if(!is_file($root.'/'.$requiredV31))$errors[]='N1.0 v3.1 runtime quiescence source missing ['.$requiredV31.']';}
$runtimeQuiescenceOut=[];$runtimeQuiescenceCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-quiescence-contract-verify.php').' 2>&1',$runtimeQuiescenceOut,$runtimeQuiescenceCode);if($runtimeQuiescenceCode!==0)$errors[]='N1.0 v3.1 runtime quiescence contract verifier failed: '.implode(' | ',$runtimeQuiescenceOut);
foreach(['scripts/lib/n1-target-cutover-barrier-contracts.php','scripts/n1-target-cutover-barrier-contract-verify.php','tests/Architecture/N100V32CutoverBarrierArchitectureTest.php','tests/Unit/RuntimeVersionGuardQueuePayloadTest.php'] as $requiredV32){if(!is_file($root.'/'.$requiredV32))$errors[]='N1.0 v3.2 cutover barrier source missing ['.$requiredV32.']';}
$cutoverBarrierOut=[];$cutoverBarrierCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-cutover-barrier-contract-verify.php').' 2>&1',$cutoverBarrierOut,$cutoverBarrierCode);if($cutoverBarrierCode!==0)$errors[]='N1.0 v3.2 cutover barrier contract verifier failed: '.implode(' | ',$cutoverBarrierOut);
foreach(['scripts/lib/deployment-generation.php','app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php','resources/js/admin/runtime/deployment-fence.ts','app/Console/Commands/Nexora/RuntimeDeploymentStatusCommand.php','scripts/lib/n1-target-deployment-generation-contracts.php','scripts/n1-target-deployment-generation-contract-verify.php','tests/Architecture/N100V33DeploymentGenerationArchitectureTest.php'] as $requiredV33){if(!is_file($root.'/'.$requiredV33))$errors[]='N1.0 v3.3 deployment-generation source missing ['.$requiredV33.']';}
$deploymentGenerationOut=[];$deploymentGenerationCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-deployment-generation-contract-verify.php').' 2>&1',$deploymentGenerationOut,$deploymentGenerationCode);if($deploymentGenerationCode!==0)$errors[]='N1.0 v3.3 deployment-generation contract verifier failed: '.implode(' | ',$deploymentGenerationOut);
foreach(['app/Nexora/Cloud/Services/RuntimeEnvironmentIdentity.php','app/Nexora/Cloud/Services/RuntimeKeyRotationService.php','app/Console/Commands/Nexora/RuntimeEnvironmentStatusCommand.php','app/Console/Commands/Nexora/RuntimeKeyRotationCommand.php','scripts/lib/n1-target-runtime-environment-contracts.php','scripts/n1-target-runtime-environment-contract-verify.php','tests/Architecture/N100V34RuntimeEnvironmentArchitectureTest.php'] as $requiredV34){if(!is_file($root.'/'.$requiredV34))$errors[]='N1.0 v3.4 runtime-environment source missing ['.$requiredV34.']';}
$runtimeEnvironmentOut=[];$runtimeEnvironmentCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-environment-contract-verify.php').' 2>&1',$runtimeEnvironmentOut,$runtimeEnvironmentCode);if($runtimeEnvironmentCode!==0)$errors[]='N1.0 v3.4 runtime-environment contract verifier failed: '.implode(' | ',$runtimeEnvironmentOut);
foreach(['config/nexora-activation.php','app/Nexora/Cloud/Services/RuntimeActivationIdentity.php','app/Console/Commands/Nexora/RuntimeActivationStatusCommand.php','app/Console/Commands/Nexora/RuntimeActivationRotateCommand.php','scripts/lib/n1-target-runtime-activation-contracts.php','scripts/n1-target-runtime-activation-contract-verify.php','tests/Architecture/N100V35RuntimeActivationArchitectureTest.php'] as $requiredV35){if(!is_file($root.'/'.$requiredV35))$errors[]='N1.0 v3.5 runtime-activation source missing ['.$requiredV35.']';}
$runtimeActivationOut=[];$runtimeActivationCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-activation-contract-verify.php').' 2>&1',$runtimeActivationOut,$runtimeActivationCode);if($runtimeActivationCode!==0)$errors[]='N1.0 v3.5 runtime-activation contract verifier failed: '.implode(' | ',$runtimeActivationOut);
foreach(['config/nexora-engine.php','app/Nexora/Cloud/Services/RuntimeEngineIdentity.php','app/Console/Commands/Nexora/RuntimeEngineStatusCommand.php','scripts/lib/n1-target-runtime-engine-contracts.php','scripts/n1-target-runtime-engine-contract-verify.php','tests/Architecture/N100V36RuntimeEngineArchitectureTest.php'] as $requiredV36){if(!is_file($root.'/'.$requiredV36))$errors[]='N1.0 v3.6 runtime-engine source missing ['.$requiredV36.']';}
$runtimeEngineOut=[];$runtimeEngineCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-engine-contract-verify.php').' 2>&1',$runtimeEngineOut,$runtimeEngineCode);if($runtimeEngineCode!==0)$errors[]='N1.0 v3.6 runtime-engine contract verifier failed: '.implode(' | ',$runtimeEngineOut);
foreach(['config/nexora-database-runtime.php','app/Nexora/Installation/Database/DatabaseDataPlaneIdentity.php','app/Console/Commands/Nexora/DatabaseDataPlaneStatusCommand.php','scripts/database-data-plane-certify.php','scripts/lib/n1-target-database-data-plane-contracts.php','scripts/n1-target-database-data-plane-contract-verify.php','tests/Architecture/N100V37DatabaseDataPlaneArchitectureTest.php'] as $requiredV37){if(!is_file($root.'/'.$requiredV37))$errors[]='N1.0 v3.7 database data-plane source missing ['.$requiredV37.']';}
$databaseDataPlaneOut=[];$databaseDataPlaneCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-database-data-plane-contract-verify.php').' 2>&1',$databaseDataPlaneOut,$databaseDataPlaneCode);if($databaseDataPlaneCode!==0)$errors[]='N1.0 v3.7 database data-plane contract verifier failed: '.implode(' | ',$databaseDataPlaneOut);


foreach(['config/nexora-storage-runtime.php','app/Nexora/Cloud/Services/RuntimeStorageDataPlaneIdentity.php','app/Console/Commands/Nexora/RuntimeStorageStatusCommand.php','scripts/lib/n1-target-storage-data-plane-contracts.php','scripts/n1-target-storage-data-plane-contract-verify.php','tests/Architecture/N100V38StorageDataPlaneArchitectureTest.php'] as $requiredV38){if(!is_file($root.'/'.$requiredV38))$errors[]='N1.0 v3.8 storage data-plane source missing ['.$requiredV38.']';}
$storageDataPlaneOut=[];$storageDataPlaneCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-storage-data-plane-contract-verify.php').' 2>&1',$storageDataPlaneOut,$storageDataPlaneCode);if($storageDataPlaneCode!==0)$errors[]='N1.0 v3.8 storage data-plane contract verifier failed: '.implode(' | ',$storageDataPlaneOut);


foreach(['config/nexora-network-runtime.php','app/Nexora/Cloud/Services/RuntimeServiceDataPlaneIdentity.php','app/Nexora/Foundation/Network/NetworkDestinationPolicy.php','app/Nexora/Foundation/Network/ApprovedHttpClient.php','app/Console/Commands/Nexora/RuntimeServiceStatusCommand.php','scripts/lib/n1-target-service-data-plane-contracts.php','scripts/n1-target-service-data-plane-contract-verify.php','tests/Architecture/N100V39ServiceDataPlaneArchitectureTest.php'] as $requiredV39){if(!is_file($root.'/'.$requiredV39))$errors[]='N1.0 v3.9 service/network data-plane source missing ['.$requiredV39.']';}
$serviceDataPlaneOut=[];$serviceDataPlaneCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-service-data-plane-contract-verify.php').' 2>&1',$serviceDataPlaneOut,$serviceDataPlaneCode);if($serviceDataPlaneCode!==0)$errors[]='N1.0 v3.9 service/network data-plane contract verifier failed: '.implode(' | ',$serviceDataPlaneOut);

foreach(['config/nexora-host-runtime.php','app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php','app/Console/Commands/Nexora/RuntimeHostStatusCommand.php','scripts/lib/n1-target-host-clock-contracts.php','scripts/n1-target-host-clock-contract-verify.php','tests/Architecture/N100V40HostClockArchitectureTest.php'] as $requiredV40){if(!is_file($root.'/'.$requiredV40))$errors[]='N1.0 v4.0 host/clock source missing ['.$requiredV40.']';}
$hostClockOut=[];$hostClockCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-host-clock-contract-verify.php').' 2>&1',$hostClockOut,$hostClockCode);if($hostClockCode!==0)$errors[]='N1.0 v4.0 host/clock contract verifier failed: '.implode(' | ',$hostClockOut);
foreach(['config/nexora-resource-runtime.php','app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php','app/Console/Commands/Nexora/RuntimeResourceStatusCommand.php','scripts/lib/n1-target-resource-envelope-contracts.php','scripts/n1-target-resource-envelope-contract-verify.php','tests/Architecture/N100V41ResourceEnvelopeArchitectureTest.php'] as $requiredV41){if(!is_file($root.'/'.$requiredV41))$errors[]='N1.0 v4.1 resource-envelope source missing ['.$requiredV41.']';}
$resourceEnvelopeOut=[];$resourceEnvelopeCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-resource-envelope-contract-verify.php').' 2>&1',$resourceEnvelopeOut,$resourceEnvelopeCode);if($resourceEnvelopeCode!==0)$errors[]='N1.0 v4.1 resource-envelope contract verifier failed: '.implode(' | ',$resourceEnvelopeOut);
foreach(['config/nexora-policy-runtime.php','app/Nexora/Cloud/Services/RuntimePolicyPlaneIdentity.php','app/Console/Commands/Nexora/RuntimePolicyStatusCommand.php','scripts/lib/n1-target-policy-plane-contracts.php','scripts/n1-target-policy-plane-contract-verify.php','tests/Architecture/N100V42PolicyPlaneArchitectureTest.php'] as $requiredV42){if(!is_file($root.'/'.$requiredV42))$errors[]='N1.0 v4.2 policy-plane source missing ['.$requiredV42.']';}
$policyPlaneOut=[];$policyPlaneCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-policy-plane-contract-verify.php').' 2>&1',$policyPlaneOut,$policyPlaneCode);if($policyPlaneCode!==0)$errors[]='N1.0 v4.2 policy-plane contract verifier failed: '.implode(' | ',$policyPlaneOut);
foreach(['config/nexora-process-runtime.php','app/Nexora/Cloud/Services/RuntimeProcessPlane.php','app/Console/Commands/Nexora/RuntimeProcessHeartbeatCommand.php','app/Console/Commands/Nexora/RuntimeProcessStatusCommand.php','scripts/lib/n1-target-process-plane-contracts.php','scripts/n1-target-process-plane-contract-verify.php','tests/Architecture/N100V43ProcessPlaneArchitectureTest.php'] as $requiredV43){if(!is_file($root.'/'.$requiredV43))$errors[]='N1.0 v4.3 process-plane source missing ['.$requiredV43.']';}
$processPlaneOut=[];$processPlaneCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-process-plane-contract-verify.php').' 2>&1',$processPlaneOut,$processPlaneCode);if($processPlaneCode!==0)$errors[]='N1.0 v4.3 process-plane contract verifier failed: '.implode(' | ',$processPlaneOut);
foreach(['config/nexora-framework.php','app/Nexora/Foundation/Runtime/FrameworkCompatibility.php','app/Nexora/Foundation/Runtime/ReviewedDependencyState.php','app/Nexora/Foundation/Runtime/DependencyDeploymentReconciler.php','app/Console/Commands/Nexora/RuntimeCompatibilityStatusCommand.php','app/Console/Commands/Nexora/RuntimeDependencyStatusCommand.php','app/Console/Commands/Nexora/RuntimeDependencyReconcileCommand.php','scripts/lib/n1-target-framework-dependency-contracts.php','scripts/n1-target-framework-dependency-contract-verify.php','tests/Architecture/N100V44FrameworkDependencyArchitectureTest.php'] as $requiredV44){if(!is_file($root.'/'.$requiredV44))$errors[]='N1.0 v4.4 framework/dependency source missing ['.$requiredV44.']';}
$frameworkDependencyOut=[];$frameworkDependencyCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-framework-dependency-contract-verify.php').' 2>&1',$frameworkDependencyOut,$frameworkDependencyCode);if($frameworkDependencyCode!==0)$errors[]='N1.0 v4.4 framework/dependency contract verifier failed: '.implode(' | ',$frameworkDependencyOut);

foreach ([
    'scripts/lib/n1-target-tenant-seed-typescript-contracts.php',
    'scripts/n1-target-tenant-seed-typescript-contract-verify.php',
    'tests/Architecture/N100V45TenantSeedTypeScriptArchitectureTest.php',
] as $requiredV45) {
    if (! is_file($root.'/'.$requiredV45)) {
        $errors[] = 'N1.0 v4.5 tenant-seed / TypeScript source missing ['.$requiredV45.']';
    }
}

$tenantSeedTypeScriptOut = [];
$tenantSeedTypeScriptCode = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-tenant-seed-typescript-contract-verify.php').' 2>&1',
    $tenantSeedTypeScriptOut,
    $tenantSeedTypeScriptCode,
);
if ($tenantSeedTypeScriptCode !== 0) {
    $errors[] = 'N1.0 v4.5 tenant-seed / TypeScript contract verifier failed: '.implode(' | ', $tenantSeedTypeScriptOut);
}


foreach ([
    'scripts/lib/n1-target-tenant-execution-contracts.php',
    'scripts/n1-target-tenant-execution-contract-verify.php',
    'tests/Architecture/N100V46TenantExecutionBoundaryArchitectureTest.php',
    'app/Nexora/Enterprise/Services/TenantExecutionScope.php',
] as $requiredV46) {
    if (! is_file($root.'/'.$requiredV46)) {
        $errors[] = 'N1.0 v4.6 tenant execution source missing ['.$requiredV46.']';
    }
}
$tenantExecutionOut = [];
$tenantExecutionCode = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-tenant-execution-contract-verify.php').' 2>&1',
    $tenantExecutionOut,
    $tenantExecutionCode,
);
if ($tenantExecutionCode !== 0) {
    $errors[] = 'N1.0 v4.6 tenant execution contract verifier failed: '.implode(' | ', $tenantExecutionOut);
}

foreach ([
    'scripts/lib/n1-target-fresh-install-dependency-trust-contracts.php',
    'scripts/n1-target-fresh-install-dependency-trust-contract-verify.php',
    'tests/Architecture/N100V47FreshInstallDependencyTrustArchitectureTest.php',
    'app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php',
    'app/Nexora/Foundation/Runtime/DependencyReviewSynchronizer.php',
    'app/Console/Commands/Nexora/RuntimeDependencyReviewSyncCommand.php',
] as $requiredV47) {
    if (! is_file($root.'/'.$requiredV47)) {
        $errors[] = 'N1.0 v4.7 fresh-install dependency trust source missing ['.$requiredV47.']';
    }
}
$freshInstallDependencyTrustOut = [];
$freshInstallDependencyTrustCode = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-fresh-install-dependency-trust-contract-verify.php').' 2>&1',
    $freshInstallDependencyTrustOut,
    $freshInstallDependencyTrustCode,
);
if ($freshInstallDependencyTrustCode !== 0) {
    $errors[] = 'N1.0 v4.7 fresh-install dependency trust contract verifier failed: '.implode(' | ', $freshInstallDependencyTrustOut);
}

foreach ([
    'scripts/lib/n1-target-installation-commit-contracts.php',
    'scripts/n1-target-installation-commit-contract-verify.php',
    'tests/Architecture/N100V48InstallationCommitBoundaryArchitectureTest.php',
    'app/Nexora/Installation/InstallationState.php',
    'app/Console/Commands/Nexora/InstallationLockStatusCommand.php',
] as $requiredV48) {
    if (! is_file($root.'/'.$requiredV48)) {
        $errors[] = 'N1.0 v4.8 installation commit source missing ['.$requiredV48.']';
    }
}
$installationCommitOut = [];
$installationCommitCode = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-installation-commit-contract-verify.php').' 2>&1',
    $installationCommitOut,
    $installationCommitCode,
);
if ($installationCommitCode !== 0) {
    $errors[] = 'N1.0 v4.8 installation commit contract verifier failed: '.implode(' | ', $installationCommitOut);
}


foreach ([
    'scripts/lib/n1-target-installer-consent-flow-contracts.php',
    'scripts/n1-target-installer-consent-flow-contract-verify.php',
    'tests/Architecture/N100V49InstallerConsentFlowArchitectureTest.php',
    'app/Nexora/Security/Password/PasswordStrengthEvaluator.php',
] as $requiredV49) {
    if (! is_file($root.'/'.$requiredV49)) {
        $errors[] = 'N1.0 v4.9 installer consent flow source missing ['.$requiredV49.']';
    }
}
$installerConsentFlowOut = [];
$installerConsentFlowCode = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-installer-consent-flow-contract-verify.php').' 2>&1',
    $installerConsentFlowOut,
    $installerConsentFlowCode,
);
if ($installerConsentFlowCode !== 0) {
    $errors[] = 'N1.0 v4.9 installer consent flow verifier failed: '.implode(' | ', $installerConsentFlowOut);
}

foreach ([
    'app/Nexora/Installation/InstallationResumeIdentity.php',
    'scripts/lib/n1-target-installation-resume-fast-track-contracts.php',
    'scripts/n1-target-installation-resume-fast-track-contract-verify.php',
    'scripts/n1-target-fast-track.php',
    'tests/Architecture/N100V50InstallationResumeFastTrackArchitectureTest.php',
] as $requiredV50) {
    if (! is_file($root.'/'.$requiredV50)) {
        $errors[] = 'N1.0 v5.0 installation resume/fast-track source missing ['.$requiredV50.']';
    }
}
$v50Out = [];
$v50Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-installation-resume-fast-track-contract-verify.php').' 2>&1',
    $v50Out,
    $v50Code,
);
if ($v50Code !== 0) {
    $errors[] = 'N1.0 v5.0 installation resume/fast-track verifier failed: '.implode(' | ', $v50Out);
}

foreach ([
    'scripts/lib/n1-target-progress.php',
    'scripts/n1-target-progress.php',
    'scripts/lib/n1-historical-typescript-remediation.php',
    'scripts/n1-historical-typescript-remediation.php',
    'scripts/lib/n1-target-progress-visibility-contracts.php',
    'scripts/n1-target-progress-visibility-contract-verify.php',
    'tests/Architecture/N100V51TargetProgressVisibilityArchitectureTest.php',
] as $requiredV51) {
    if (! is_file($root.'/'.$requiredV51)) {
        $errors[] = 'N1.0 v5.1 target progress visibility source missing ['.$requiredV51.']';
    }
}
$v51Out = [];
$v51Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-progress-visibility-contract-verify.php').' 2>&1',
    $v51Out,
    $v51Code,
);
if ($v51Code !== 0) {
    $errors[] = 'N1.0 v5.1 target progress visibility verifier failed: '.implode(' | ', $v51Out);
}

foreach ([
    'app/Nexora/Installation/SourceActivationIdentity.php',
    'app/Console/Commands/Nexora/SourceStatusCommand.php',
    'app/Console/Commands/Nexora/SourceActivateCommand.php',
    'scripts/n1-source-activate.bat',
    'scripts/n1-source-activate.sh',
    'scripts/lib/n1-target-source-activation-contracts.php',
    'scripts/n1-target-source-activation-contract-verify.php',
    'tests/Architecture/N100V52SourceActivationArchitectureTest.php',
] as $requiredV52) {
    if (! is_file($root.'/'.$requiredV52)) {
        $errors[] = 'N1.0 v5.2 source activation source missing ['.$requiredV52.']';
    }
}
$v52Out = [];
$v52Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-source-activation-contract-verify.php').' 2>&1',
    $v52Out,
    $v52Code,
);
if ($v52Code !== 0) {
    $errors[] = 'N1.0 v5.2 source activation verifier failed: '.implode(' | ', $v52Out);
}

foreach ([
    'app/Nexora/Installation/SourceSetIntegrity.php',
    'app/Nexora/Installation/SourceActivationHandshake.php',
    'bootstrap/nexora-source-manifest.json',
    'scripts/n1-source-manifest-seal.php',
    'scripts/n1-source-web-ack.bat',
    'scripts/n1-source-web-ack.sh',
    'scripts/lib/n1-installation-progress.php',
    'scripts/n1-installation-progress.php',
    'scripts/lib/n1-target-source-set-handshake-contracts.php',
    'scripts/n1-target-source-set-handshake-contract-verify.php',
    'tests/Unit/Installation/SourceActivationHandshakeTest.php',
    'tests/Architecture/N100V53SourceSetHandshakeArchitectureTest.php',
] as $requiredV53) {
    if (! is_file($root.'/'.$requiredV53)) {
        $errors[] = 'N1.0 v5.3 source-set/web-ack source missing ['.$requiredV53.']';
    }
}
$v53Out = [];
$v53Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-source-set-handshake-contract-verify.php').' 2>&1',
    $v53Out,
    $v53Code,
);
if ($v53Code !== 0) {
    $errors[] = 'N1.0 v5.3 source-set/web-ack verifier failed: '.implode(' | ', $v53Out);
}

foreach ([
    'scripts/lib/n1-target-runtime-source-convergence-contracts.php',
    'scripts/n1-target-runtime-source-convergence-contract-verify.php',
    'tests/Architecture/N100V54RuntimeSourceConvergenceArchitectureTest.php',
    'tests/Feature/Certification/SourceStatusRedactionCertificationTest.php',
] as $requiredV54) {
    if (! is_file($root.'/'.$requiredV54)) {
        $errors[] = 'N1.0 v5.4 runtime-source/secure-web-ack source missing ['.$requiredV54.']';
    }
}
$v54Out = [];
$v54Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-source-convergence-contract-verify.php').' 2>&1',
    $v54Out,
    $v54Code,
);
if ($v54Code !== 0) {
    $errors[] = 'N1.0 v5.4 runtime-source/secure-web-ack verifier failed: '.implode(' | ', $v54Out);
}

foreach ([
    'scripts/lib/n1-target-installer-host-clock-contracts.php',
    'scripts/n1-target-installer-host-clock-contract-verify.php',
    'tests/Architecture/N100V55InstallerHostClockArchitectureTest.php',
] as $requiredV55) {
    if (! is_file($root.'/'.$requiredV55)) {
        $errors[] = 'N1.0 v5.5 installer host/clock source missing ['.$requiredV55.']';
    }
}
$v55Out = [];
$v55Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-installer-host-clock-contract-verify.php').' 2>&1',
    $v55Out,
    $v55Code,
);
if ($v55Code !== 0) {
    $errors[] = 'N1.0 v5.5 installer host/clock verifier failed: '.implode(' | ', $v55Out);
}

foreach ([
    'scripts/lib/n1-target-installer-runtime-readiness-contracts.php',
    'scripts/n1-target-installer-runtime-readiness-contract-verify.php',
    'tests/Architecture/N100V56InstallerRuntimeReadinessArchitectureTest.php',
] as $requiredV56) {
    if (! is_file($root.'/'.$requiredV56)) {
        $errors[] = 'N1.0 v5.6 installer runtime-readiness source missing ['.$requiredV56.']';
    }
}
$v56Out = [];
$v56Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-installer-runtime-readiness-contract-verify.php').' 2>&1',
    $v56Out,
    $v56Code,
);
if ($v56Code !== 0) {
    $errors[] = 'N1.0 v5.6 installer runtime-readiness verifier failed: '.implode(' | ', $v56Out);
}

foreach ([
    'scripts/lib/n1-target-install-runtime-handoff-contracts.php',
    'scripts/n1-target-install-runtime-handoff-contract-verify.php',
    'tests/Architecture/N100V57InstallRuntimeHandoffArchitectureTest.php',
    'app/Nexora/Installation/RuntimePostInstallHandoff.php',
    'app/Console/Commands/Nexora/RuntimePostInstallStatusCommand.php',
] as $requiredV57) {
    if (! is_file($root.'/'.$requiredV57)) {
        $errors[] = 'N1.0 v5.7 install/runtime handoff source missing ['.$requiredV57.']';
    }
}
$v57Out = [];
$v57Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-install-runtime-handoff-contract-verify.php').' 2>&1',
    $v57Out,
    $v57Code,
);
if ($v57Code !== 0) {
    $errors[] = 'N1.0 v5.7 install/runtime handoff verifier failed: '.implode(' | ', $v57Out);
}

foreach ([
    'scripts/lib/n1-target-clock-temp-portability-contracts.php',
    'scripts/n1-target-clock-temp-portability-contract-verify.php',
    'tests/Architecture/N100V58ClockTempPortabilityArchitectureTest.php',
    'app/Nexora/Foundation/Runtime/RuntimeWritableTempDirectory.php',
] as $requiredV58) {
    if (! is_file($root.'/'.$requiredV58)) {
        $errors[] = 'N1.0 v5.8 clock/temp portability source missing ['.$requiredV58.']';
    }
}
$v58Out = [];
$v58Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-clock-temp-portability-contract-verify.php').' 2>&1',
    $v58Out,
    $v58Code,
);
if ($v58Code !== 0) {
    $errors[] = 'N1.0 v5.8 clock/temp portability verifier failed: '.implode(' | ', $v58Out);
}

foreach ([
    'scripts/lib/n1-target-exact-resume-commit-contracts.php',
    'scripts/n1-target-exact-resume-commit-contract-verify.php',
    'tests/Architecture/N100V59ExactResumeCommitArchitectureTest.php',
    'app/Console/Commands/Nexora/RuntimePostInstallReconcileCommand.php',
    'resources/views/install/runtime-handoff.blade.php',
] as $requiredV59) {
    if (! is_file($root.'/'.$requiredV59)) {
        $errors[] = 'N1.0 v5.9 exact-resume/commit source missing ['.$requiredV59.']';
    }
}
$v59Out = [];
$v59Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-exact-resume-commit-contract-verify.php').' 2>&1',
    $v59Out,
    $v59Code,
);
if ($v59Code !== 0) {
    $errors[] = 'N1.0 v5.9 exact-resume/commit verifier failed: '.implode(' | ', $v59Out);
}

foreach ([
    'scripts/lib/n1-frontend-build-diagnostics.php',
    'scripts/n1-c1-frontend-build-doctor.php',
    'scripts/n1-c1-frontend-build-doctor.bat',
    'scripts/n1-c1-frontend-build-doctor.ps1',
    'scripts/n1-c1-frontend-build-doctor.sh',
    'scripts/lib/n1-target-frontend-build-closure-contracts.php',
    'scripts/n1-target-frontend-build-closure-contract-verify.php',
    'tests/Architecture/N100V510FrontendBuildClosureArchitectureTest.php',
    'tests/Unit/Certification/FrontendBuildDiagnosticsTest.php',
] as $requiredV510) {
    if (! is_file($root.'/'.$requiredV510)) {
        $errors[] = 'N1.0 v5.10 frontend build closure source missing ['.$requiredV510.']';
    }
}
$v510Out = [];
$v510Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-frontend-build-closure-contract-verify.php').' 2>&1',
    $v510Out,
    $v510Code,
);
if ($v510Code !== 0) {
    $errors[] = 'N1.0 v5.10 frontend build closure verifier failed: '.implode(' | ', $v510Out);
}

foreach ([
    'scripts/lib/dependency-lock-intake.php',
    'scripts/dependency-lock-refresh.php',
    'scripts/dependency-lock-promote.php',
    'scripts/promote-reviewed-dependency-locks.bat',
    'scripts/promote-reviewed-dependency-locks.ps1',
    'scripts/promote-reviewed-dependency-locks.sh',
    'scripts/dependency-lock-promotion-recover.php',
    'scripts/recover-dependency-lock-promotion.bat',
    'scripts/recover-dependency-lock-promotion.ps1',
    'scripts/recover-dependency-lock-promotion.sh',
    'scripts/lib/n1-target-transactional-lock-intake-contracts.php',
    'scripts/n1-target-transactional-lock-intake-contract-verify.php',
    'tests/Architecture/N100V511TransactionalLockIntakeArchitectureTest.php',
] as $requiredV511) {

    if (! is_file($root.'/'.$requiredV511)) {
        $errors[] = 'N1.0 v5.11 transactional lock intake source missing ['.$requiredV511.']';
    }
}
$v511Out = [];
$v511Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-transactional-lock-intake-contract-verify.php').' 2>&1',
    $v511Out,
    $v511Code,
);
if ($v511Code !== 0) {
    $errors[] = 'N1.0 v5.11 transactional lock intake verifier failed: '.implode(' | ', $v511Out);
}

foreach ([
    'scripts/lib/dependency-toolchain.php',
    'scripts/lib/n1-target-reproducible-dependency-toolchain-contracts.php',
    'scripts/n1-target-reproducible-dependency-toolchain-contract-verify.php',
    'tests/Architecture/N100V512ReproducibleDependencyToolchainArchitectureTest.php',
] as $requiredV512) {
    if (! is_file($root.'/'.$requiredV512)) {
        $errors[] = 'N1.0 v5.12 reproducible dependency toolchain source missing ['.$requiredV512.']';
    }
}
$v512Out = [];
$v512Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-reproducible-dependency-toolchain-contract-verify.php').' 2>&1',
    $v512Out,
    $v512Code,
);
if ($v512Code !== 0) {
    $errors[] = 'N1.0 v5.12 reproducible dependency toolchain verifier failed: '.implode(' | ', $v512Out);
}

foreach ([
    'scripts/lib/dependency-candidate-supply-chain.php',
    'scripts/lib/n1-target-dependency-candidate-supply-chain-contracts.php',
    'scripts/n1-target-dependency-candidate-supply-chain-contract-verify.php',
    'tests/Architecture/N100V515DependencyCandidateSupplyChainArchitectureTest.php',
] as $requiredV515) {
    if (! is_file($root.'/'.$requiredV515)) {
        $errors[] = 'N1.0 v5.15 dependency candidate supply-chain source missing ['.$requiredV515.']';
    }
}
$v515Out = [];
$v515Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-dependency-candidate-supply-chain-contract-verify.php').' 2>&1',
    $v515Out,
    $v515Code,
);
if ($v515Code !== 0) {
    $errors[] = 'N1.0 v5.15 dependency candidate supply-chain verifier failed: '.implode(' | ', $v515Out);
}

foreach ([
    'scripts/lib/n1-target-windows-npm-bridge-contracts.php',
    'scripts/n1-target-windows-npm-bridge-contract-verify.php',
    'tests/Architecture/N100V520WindowsNpmBridgeArchitectureTest.php',
] as $requiredV520) {
    if (! is_file($root.'/'.$requiredV520)) {
        $errors[] = 'N1.0 v5.20 Windows npm bridge source missing ['.$requiredV520.']';
    }
}
$v520Out = [];
$v520Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-windows-npm-bridge-contract-verify.php').' 2>&1',
    $v520Out,
    $v520Code,
);
if ($v520Code !== 0) {
    $errors[] = 'N1.0 v5.20 Windows npm bridge verifier failed: '.implode(' | ', $v520Out);
}

foreach ([
    'scripts/lib/n1-target-npm-bundled-integrity-contracts.php',
    'scripts/n1-target-npm-bundled-integrity-contract-verify.php',
    'tests/Architecture/N100V521NpmBundledIntegrityArchitectureTest.php',
] as $requiredV521) {
    if (! is_file($root.'/'.$requiredV521)) {
        $errors[] = 'N1.0 v5.21 npm bundled-integrity source missing ['.$requiredV521.']';
    }
}
$v521Out = [];
$v521Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-npm-bundled-integrity-contract-verify.php').' 2>&1',
    $v521Out,
    $v521Code,
);
if ($v521Code !== 0) {
    $errors[] = 'N1.0 v5.21 npm bundled-integrity verifier failed: '.implode(' | ', $v521Out);
}

foreach ([
    'scripts/lib/n1-target-semantic-lock-reproducibility-contracts.php',
    'scripts/n1-target-semantic-lock-reproducibility-contract-verify.php',
    'tests/Architecture/N100V522SemanticLockReproducibilityArchitectureTest.php',
    'scripts/lib/n1-target-typescript-depth-contracts.php',
    'scripts/n1-target-typescript-depth-contract-verify.php',
    'tests/Architecture/N100V522TypeScriptDepthArchitectureTest.php',
] as $requiredV522) {
    if (! is_file($root.'/'.$requiredV522)) {
        $errors[] = 'N1.0 v5.22 semantic-lock/TS-depth source missing ['.$requiredV522.']';
    }
}
$v522Out = [];
$v522Code = 0;
exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-semantic-lock-reproducibility-contract-verify.php').' 2>&1', $v522Out, $v522Code);
if ($v522Code !== 0) {
    $errors[] = 'N1.0 v5.22 semantic lock reproducibility verifier failed: '.implode(' | ', $v522Out);
}
$v522TsOut = [];
$v522TsCode = 0;
exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-typescript-depth-contract-verify.php').' 2>&1', $v522TsOut, $v522TsCode);
if ($v522TsCode !== 0) {
    $errors[] = 'N1.0 v5.22 TypeScript depth verifier failed: '.implode(' | ', $v522TsOut);
}

foreach ([
    'scripts/lib/pkg1-closure.php',
    'scripts/lib/pkg1-closure-contracts.php',
    'scripts/pkg1-closure-contract-verify.php',
    'scripts/pkg1-usable-closure.php',
    'scripts/pkg1-usable-smoke.php',
    'scripts/pkg1-closure-evidence-verify.php',
    'scripts/pkg1-status.php',
    'scripts/pkg1-run.bat',
    'scripts/pkg1-run.php',
    'scripts/pkg1-launcher-contract-verify.php',
    'scripts/pkg1-status.bat',
    'scripts/pkg1-status.ps1',
    'scripts/pkg1-status.sh',
    'scripts/pkg1-build.php',
    'scripts/lib/pkg1-build-identity.php',
    'scripts/pkg1-finalize-login-smoke.bat',
    'scripts/pkg1-finalize-login-smoke.ps1',
    'tests/Architecture/N100Pkg1UsableClosureArchitectureTest.php',
] as $requiredPkg1) {
    if (! is_file($root.'/'.$requiredPkg1)) {
        $errors[] = 'PKG-1 usable closure source missing ['.$requiredPkg1.']';
    }
}
$pkg1Out = [];
$pkg1Code = 0;
exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/pkg1-closure-contract-verify.php').' 2>&1',
    $pkg1Out,
    $pkg1Code,
);
if ($pkg1Code !== 0) {
    $errors[] = 'PKG-1 usable closure verifier failed: '.implode(' | ', $pkg1Out);
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Source Guard] FAILED\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Source Guard] PASS\n");
