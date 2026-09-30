<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Nexora\Observability\Services\ObservabilityRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final readonly class ObserveRequestOutcome
{
    public function __construct(private ObservabilityRecorder $recorder) {}

    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);

        try {
            $response = $next($request);
            $this->recorder->captureHttp(
                $request,
                $response->getStatusCode(),
                (microtime(true) - $started) * 1000,
            );

            return $response;
        } catch (Throwable $exception) {
            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $this->recorder->captureHttp(
                $request,
                $status,
                (microtime(true) - $started) * 1000,
                $exception,
            );

            throw $exception;
        }
    }
}
