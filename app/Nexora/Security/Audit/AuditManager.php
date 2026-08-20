<?php

declare(strict_types=1);

namespace App\Nexora\Security\Audit;

use App\Http\Middleware\AssignRequestId;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditManager
{
    public function record(
        string $event,
        ?object $subject = null,
        array $metadata = [],
        ?Request $request = null,
        ?int $userId = null,
    ): AuditLog {
        $request ??= request();

        $requestId = (string) ($request->attributes->get(AssignRequestId::ATTRIBUTE)
            ?: $request->headers->get('X-Request-Id')
            ?: Str::uuid());

        return AuditLog::query()->create([
            'user_id' => $userId ?? $request->user()?->id,
            'event' => $event,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject && isset($subject->id) ? (string) $subject->id : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
            'request_id' => $requestId,
            'created_at' => now(),
        ]);
    }
}
