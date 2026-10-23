<?php

declare(strict_types=1);

namespace App\Nexora\Http;

/**
 * Domain-side names for per-request values set by HTTP middleware (ARCH-BOUNDARY-100).
 * Domain code reads these instead of importing App\Http, keeping the dependency direction
 * Http → Nexora and never the reverse.
 */
final class RequestContext
{
    /** Request attribute holding the correlation id assigned by App\Http\Middleware\AssignRequestId. */
    public const REQUEST_ID = 'nexora_request_id';
}
