<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [
    'install routes exclude runtime/tenant/Inertia middleware' => [
        'file' => 'routes/web.php',
        'needle' => 'withoutMiddleware([RuntimeNodeHeartbeat::class, ResolveEnterpriseOrganization::class, HandleInertiaRequests::class])',
    ],
    'enterprise middleware bypasses DB before installation' => [
        'file' => 'app/Http/Middleware/ResolveEnterpriseOrganization.php',
        'needle' => "if (! \$this->installation->isInstalled() || \$request->routeIs('install.*'))",
    ],
    'locale middleware does not resolve auth before installation' => [
        'file' => 'app/Http/Middleware/SetLocale.php',
        'needle' => '$userLocale = $this->installation->isInstalled() ? $request->user()?->locale : null',
    ],
    'performance middleware does not resolve auth before installation' => [
        'file' => 'app/Http/Middleware/ApplyPerformanceHeaders.php',
        'needle' => 'if (! $this->installation->isInstalled())',
    ],
    'Inertia bootstrap sharing avoids runtime deployment calculation' => [
        'file' => 'app/Http/Middleware/HandleInertiaRequests.php',
        'needle' => "'mode' => 'bootstrap'",
    ],
    'installer exceptions render a diagnostic page' => [
        'file' => 'bootstrap/app.php',
        'needle' => "response()->view('install.error'",
    ],
    'installer diagnostic view exists' => [
        'file' => 'resources/views/install/error.blade.php',
        'needle' => 'Request ID:',
    ],
];

$errors = [];
foreach ($checks as $label => $check) {
    $path = $root.'/'.$check['file'];
    if (! is_file($path)) {
        $errors[] = $label.': missing file '.$check['file'];
        continue;
    }
    $source = (string) file_get_contents($path);
    if (! str_contains($source, $check['needle'])) {
        $errors[] = $label.': source guard missing';
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Installer Bootstrap Isolation] FAIL\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Installer Bootstrap Isolation] PASS\n");
fwrite(STDOUT, "Installer GET/POST bootstrap path remains DB/auth/runtime independent until installation is sealed.\n");
