<?php

declare(strict_types=1);

namespace App\Nexora\Http;

use App\Http\Middleware\AssignRequestId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ErrorPresenter
{
    /** @return array{status:int,code:string,title:string,message:string,request_id:string} */
    public static function payload(Throwable $exception, Request $request): array
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        if ($status < 400 || $status > 599) $status = 500;

        $title = match ($status) {
            400 => 'Invalid request',
            401 => 'Authentication required',
            403 => 'Access denied',
            404 => 'Page not found',
            405 => 'Action not allowed',
            408 => 'Request timed out',
            409 => 'Conflict detected',
            413 => 'Upload is too large',
            419 => 'Session expired',
            422 => 'Validation failed',
            429 => 'Too many requests',
            503 => 'Service temporarily unavailable',
            default => 'Something went wrong',
        };

        $message = match ($status) {
            400 => 'Nexora could not understand this request. Check the submitted data and try again.',
            401 => 'Your session is not authorized for this request. Sign in again and retry.',
            403 => 'Your account does not have permission to perform this action.',
            404 => 'The requested Nexora resource could not be found.',
            405 => 'This action is not available for the requested route.',
            408 => 'The request took too long to complete. Retry when the connection is stable.',
            409 => 'The resource changed while you were working. Refresh before retrying.',
            413 => 'The uploaded payload exceeds the server limit. Choose a smaller file or increase the server upload limit.',
            419 => 'Your secure session expired. Refresh the page and try again.',
            422 => 'Some submitted fields need attention before Nexora can continue.',
            429 => 'Too many requests were received in a short time. Wait briefly and retry.',
            503 => 'Nexora is temporarily unavailable while a required service recovers.',
            default => 'Nexora could not complete this request. The incident reference below can be used to find the server log entry.',
        };

        $requestId = (string) ($request->attributes->get(AssignRequestId::ATTRIBUTE)
            ?: $request->headers->get('X-Request-Id')
            ?: Str::uuid());

        return [
            'status' => $status,
            'code' => 'HTTP_'.$status,
            'title' => $title,
            'message' => $message,
            'request_id' => $requestId,
        ];
    }

    public static function json(Throwable $exception, Request $request): JsonResponse
    {
        $payload = self::payload($exception, $request);
        return response()->json(['error' => $payload], $payload['status'], ['X-Request-Id' => $payload['request_id']]);
    }

    public static function inertia(Throwable $exception, Request $request): Response
    {
        $payload = self::payload($exception, $request);
        $response = Inertia::render('Errors/Show', ['error' => $payload])->toResponse($request);
        $response->setStatusCode($payload['status']);
        $response->headers->set('X-Request-Id', $payload['request_id']);
        return $response;
    }
}
