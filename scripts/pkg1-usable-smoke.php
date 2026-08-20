<?php

declare(strict_types=1);

use App\Models\User;
use App\Nexora\Installation\InstallationState;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

$baseUrl = rtrim((string) (getenv('NEXORA_PKG1_BASE_URL') ?: config('app.url')), '/');
$password = (string) (getenv('NEXORA_PKG1_SMOKE_PASSWORD') ?: '');
$errors = [];
$warnings = [];
$checks = [];

$check = static function (string $id, bool $passed, string $detail = '') use (&$checks, &$errors): void {
    $checks[$id] = [
        'status' => $passed ? 'pass' : 'fail',
        'detail' => $detail,
    ];
    if (! $passed) {
        $errors[] = $id.($detail !== '' ? ': '.$detail : '');
    }
};

/** @var InstallationState $installation */
$installation = app(InstallationState::class);
$inspection = $installation->inspect();
$check('installed-lock', ($inspection['valid'] ?? false) === true, implode('; ', (array) ($inspection['errors'] ?? [])));
$metadata = $installation->metadata() ?? [];
$adminId = (int) ($metadata['admin_user_id'] ?? 0);
$admin = $adminId > 0 ? User::query()->find($adminId) : null;
$check('installer-admin-present', $admin !== null, "admin_user_id={$adminId}");
$check('installer-admin-active', $admin?->status === 'active', (string) ($admin?->status ?? 'missing'));
$check('installer-admin-verified', $admin?->hasVerifiedEmail() === true, $admin?->email ?? 'missing');
$check('installer-admin-super-admin', $admin?->hasRole('super-admin') === true, $admin?->email ?? 'missing');

try {
    DB::connection()->getPdo();
    $check('database-readable', true, (string) DB::connection()->getDriverName());
} catch (Throwable $exception) {
    $check('database-readable', false, mb_substr($exception->getMessage(), 0, 300));
}

foreach (['login', 'admin.dashboard', 'runtime.health.ready'] as $routeName) {
    $check('route-'.$routeName, Route::has($routeName), $routeName);
}

$run = static function (array $parts) use ($root): array {
    $command = implode(' ', array_map(static fn ($part): string => escapeshellarg((string) $part), $parts));
    $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, $root);
    if (! is_resource($process)) {
        return ['exit_code'=>127,'stdout'=>'','stderr'=>'unable to start process'];
    }
    fclose($pipes[0]);
    $stdout=(string)stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr=(string)stream_get_contents($pipes[2]); fclose($pipes[2]);
    return ['exit_code'=>proc_close($process),'stdout'=>$stdout,'stderr'=>$stderr];
};

$post = $run([PHP_BINARY, 'artisan', 'nexora:runtime:post-install-status', '--assert-ready']);
$check('post-install-runtime-handoff', $post['exit_code'] === 0, mb_substr(trim($post['stderr'] ?: $post['stdout']), 0, 600));

$http = $run([PHP_BINARY, 'scripts/http-smoke.php']);
$check('public-http-smoke', $http['exit_code'] === 0, mb_substr(trim($http['stderr'] ?: $http['stdout']), 0, 600));

$authSmoke = [
    'status' => 'not-run',
    'email' => $admin?->email,
    'reason' => null,
];
if ($password === '') {
    $warnings[] = 'Live login/admin smoke not run because NEXORA_PKG1_SMOKE_PASSWORD is not set.';
    $authSmoke['reason'] = 'password-environment-missing';
} elseif ($admin === null) {
    $authSmoke['status'] = 'fail';
    $authSmoke['reason'] = 'installer-admin-missing';
    $errors[] = 'live-auth-smoke: installer admin missing';
} elseif (! function_exists('curl_init')) {
    $authSmoke['status'] = 'fail';
    $authSmoke['reason'] = 'curl-extension-missing';
    $errors[] = 'live-auth-smoke: PHP curl extension is required';
} else {
    $cookieDirectory = storage_path('framework/nexora-temp');
    if (! is_dir($cookieDirectory)) { @mkdir($cookieDirectory, 0700, true); }
    $cookie = tempnam($cookieDirectory, 'nx-pkg1-');
    $loginUrl = $baseUrl.'/login';
    $ch = curl_init($loginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: text/html'],
    ]);
    $response = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    preg_match('/<meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']/i', $response, $csrfMatch);
    $csrf = html_entity_decode((string) ($csrfMatch[1] ?? ''), ENT_QUOTES | ENT_HTML5);

    if ($status !== 200 || $csrf === '') {
        $authSmoke['status'] = 'fail';
        $authSmoke['reason'] = "login-page-http-{$status}-or-csrf-missing";
        $errors[] = 'live-auth-smoke: login page/CSRF bootstrap failed';
    } else {
        $ch = curl_init($loginUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $cookie,
            CURLOPT_COOKIEFILE => $cookie,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                '_token' => $csrf,
                'email' => $admin->email,
                'password' => $password,
                'remember' => '0',
            ]),
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml',
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);
        curl_exec($ch);
        $loginStatus = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $location = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        $ch = curl_init($baseUrl.'/admin');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE => $cookie,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Accept: text/html'],
        ]);
        curl_exec($ch);
        $adminStatus = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        @unlink($cookie);

        $passed = in_array($loginStatus, [302, 303], true) && $adminStatus === 200;
        $authSmoke = [
            'status' => $passed ? 'pass' : 'fail',
            'email' => $admin->email,
            'login_http_status' => $loginStatus,
            'login_redirect' => $location,
            'admin_http_status' => $adminStatus,
            'reason' => $passed ? null : 'login-or-admin-http-flow-failed',
        ];
        if (! $passed) {
            $errors[] = "live-auth-smoke: login={$loginStatus}, admin={$adminStatus}";
        }
    }
}

$report = [
    'schema' => 1,
    'package' => 'PKG-1',
    'status' => $errors === [] && ($authSmoke['status'] ?? null) === 'pass' ? 'pass' : ($errors === [] ? 'waiting-auth-smoke' : 'fail'),
    'base_url' => $baseUrl,
    'checks' => $checks,
    'live_auth_smoke' => $authSmoke,
    'warnings' => $warnings,
    'errors' => array_values(array_unique($errors)),
    'checked_at' => gmdate(DATE_ATOM),
];
$directory = $root.'/storage/app/nexora/pkg1';
if (! is_dir($directory)) {
    @mkdir($directory, 0775, true);
}
file_put_contents(
    $directory.'/usable-smoke.json',
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    LOCK_EX,
);

fwrite(STDOUT, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
exit($report['status'] === 'pass' ? 0 : 2);
