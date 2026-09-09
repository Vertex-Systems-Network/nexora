<?php

declare(strict_types=1);

namespace App\Nexora\Security\Audit;

use App\Http\Middleware\AssignRequestId;
use App\Models\AuditLog;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Observability\Services\TelemetrySanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AuditManager
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly TelemetrySanitizer $sanitizer,
    ) {}

    public function record(
        string $event,
        ?object $subject = null,
        array $metadata = [],
        ?Request $request = null,
        ?int $userId = null,
    ): AuditLog {
        $request ??= request();
        $event = trim($event);
        if ($event === '') {
            throw new InvalidArgumentException('Audit event name cannot be empty.');
        }

        $incomingRequestId = (string) ($request->attributes->get(AssignRequestId::ATTRIBUTE)
            ?: $request->headers->get('X-Request-Id')
            ?: '');
        $requestId = preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $incomingRequestId) === 1
            ? $incomingRequestId
            : (string) Str::uuid();

        $ip = trim((string) $request->ip());
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $ip = null;
        }

        return AuditLog::query()->create([
            'tenant_id' => $this->tenant->id(),
            'user_id' => $userId ?? $request->user()?->id,
            'event' => mb_substr($event, 0, 160),
            'subject_type' => $subject ? mb_substr($subject::class, 0, 180) : null,
            'subject_id' => $subject && isset($subject->id) ? mb_substr((string) $subject->id, 0, 100) : null,
            'ip_address' => $ip,
            'user_agent' => $this->sanitizer->text($request->userAgent(), 300),
            'metadata' => $this->sanitizer->metadata($metadata),
            'request_id' => $requestId,
            'created_at' => now(),
        ]);
    }
}
