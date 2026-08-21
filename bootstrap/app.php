<?php

declare(strict_types=1);

use App\Http\Middleware\ApplyPerformanceHeaders;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\ConfigureTrustedProxies;
use App\Http\Middleware\EnforceRequestLimits;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfNotInstalled;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\RequireApiAbility;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\ResolveEnterpriseOrganization;
use App\Http\Middleware\RuntimeNodeHeartbeat;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Nexora\Http\ErrorPresenter;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->prepend([ConfigureTrustedProxies::class, EnforceRequestLimits::class]);
        $middleware->preventRequestForgery(except: ['hooks/*', 'scim/*', 'sso/*/*/callback']);
        $middleware->web(append: [AssignRequestId::class, ApplyPerformanceHeaders::class, RedirectIfNotInstalled::class, RuntimeNodeHeartbeat::class, ResolveEnterpriseOrganization::class, SetLocale::class, HandleInertiaRequests::class]);
        $middleware->api(append: [AssignRequestId::class, ApplyPerformanceHeaders::class, RedirectIfNotInstalled::class, RuntimeNodeHeartbeat::class]);
        $middleware->alias([
            'admin' => EnsureAdminAccess::class,
            'permission' => RequirePermission::class,
            'api.token' => AuthenticateApiToken::class,
            'api.ability' => RequireApiAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || ($request->expectsJson() && ! $request->header('X-Inertia')),
        );

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($exception instanceof ValidationException || $exception instanceof AuthenticationException) {
                return null;
            }

            if ($request->header('X-Inertia')) {
                return ErrorPresenter::inertia($exception, $request);
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return ErrorPresenter::json($exception, $request);
            }

            if ($request->is('install', 'install/*')) {
                $payload = ErrorPresenter::payload($exception, $request);
                report($exception);

                return response()->view('install.error', [
                    'error' => $payload,
                    'detail' => app()->environment('local') ? $exception->getMessage() : null,
                ], $payload['status'])->header('X-Request-Id', $payload['request_id']);
            }

            return null;
        });
    })->create();
