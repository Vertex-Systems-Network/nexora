<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Prepare writable Laravel/Nexora runtime paths before Composer or Laravel boots.
require_once __DIR__.'/../bootstrap/nexora-runtime-bootstrap.php';

if (defined('NEXORA_RUNTIME_BOOTSTRAP_ERROR')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo NEXORA_RUNTIME_BOOTSTRAP_ERROR;
    exit;
}

// Prepare the safe browser installer before Laravel boots.
require_once __DIR__.'/../bootstrap/nexora-installer-bootstrap.php';

if (defined('NEXORA_INSTALL_BOOTSTRAP_ERROR')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Nexora installer bootstrap error: '.NEXORA_INSTALL_BOOTSTRAP_ERROR;
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader. If dependencies/build assets are missing,
// hand off to the standalone, framework-independent deployment bootstrap.
$autoload = __DIR__.'/../vendor/autoload.php';
$frontendManifest = __DIR__.'/build/manifest.json';
$installedLock = __DIR__.'/../storage/app/nexora/installed.lock';
if (! file_exists($installedLock) && (! file_exists($autoload) || ! file_exists($frontendManifest))) {
    // Render the framework-independent deployment bootstrap inside the canonical
    // domain URL. The standalone filename remains an implementation detail.
    define('NEXORA_BOOTSTRAP_INTERNAL', true);
    require __DIR__.'/nexora-bootstrap.php';
    exit;
}
if (! file_exists($autoload)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Nexora Composer dependencies are missing from an installed deployment.';
    exit;
}
require $autoload;

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
