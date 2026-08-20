<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookReceipt;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Automation\Services\WebhookSigner;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class InboundWebhookController extends Controller
{
    public function __invoke(Request $request, WebhookEndpoint $endpoint, WebhookSigner $signer, AutomationEventBusContract $events, ConcurrencyGuard $concurrency): JsonResponse
    {
        if (! $endpoint->enabled) return response()->json(['message' => 'Webhook endpoint is disabled.'], 404);
        $body = (string) $request->getContent();
        if (strlen($body) > 1_048_576) return response()->json(['message' => 'Webhook payload exceeds the 1 MB limit.'], 413);

        $timestamp = trim((string) $request->header('X-Nexora-Timestamp'));
        $signature = trim((string) $request->header('X-Nexora-Signature'));
        if (! ctype_digit($timestamp) || abs(now()->timestamp - (int) $timestamp) > 300) {
            return response()->json(['message' => 'Webhook timestamp is invalid or expired.'], 401);
        }

        $valid = $signer->verify((string) $endpoint->secret, $timestamp, $body, $signature);
        if (! $valid && $endpoint->previous_secret && $endpoint->previous_secret_valid_until?->isFuture()) {
            $valid = $signer->verify((string) $endpoint->previous_secret, $timestamp, $body, $signature);
        }
        if (! $valid) return response()->json(['message' => 'Webhook signature verification failed.'], 401);

        $allowed = array_values(array_filter((array) $endpoint->allowed_ips));
        if ($allowed !== [] && ! in_array((string) $request->ip(), $allowed, true)) {
            return response()->json(['message' => 'Webhook source is not allowed.'], 403);
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)) return response()->json(['message' => 'Webhook body must be a JSON object or array.'], 422);

        $headerKey = trim((string) $request->header('Idempotency-Key'));
        $idempotency = $headerKey !== ''
            ? mb_substr($headerKey, 0, 190)
            : hash('sha256', $endpoint->uuid.'|'.$body);

        try {
            $result = $concurrency->transaction(function () use ($request, $endpoint, $events, $payload, $body, $idempotency): array {
                $existing = WebhookReceipt::query()
                    ->where('webhook_endpoint_id', $endpoint->id)
                    ->where('idempotency_key', $idempotency)
                    ->first();
                if ($existing) return ['receipt' => $existing, 'duplicate' => true];

                $receipt = WebhookReceipt::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'webhook_endpoint_id' => $endpoint->id,
                    'idempotency_key' => $idempotency,
                    'payload_hash' => hash('sha256', $body),
                    'source_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                    'headers' => $this->safeHeaders($request),
                    'payload' => $payload,
                    'status' => 'accepted',
                    'received_at' => now(),
                ]);

                $endpoint->forceFill(['last_received_at' => now()])->save();
                $events->emit(
                    'webhook.inbound',
                    ['webhook' => [
                        'endpoint_id' => $endpoint->id,
                        'endpoint_uuid' => $endpoint->uuid,
                        'endpoint_slug' => $endpoint->slug,
                        'receipt_uuid' => $receipt->uuid,
                    ], 'payload' => $payload],
                    'webhook_endpoint',
                    $endpoint->id,
                    'webhook-receipt:'.$receipt->uuid,
                );

                return ['receipt' => $receipt, 'duplicate' => false];
            });
        } catch (QueryException $exception) {
            if (! $concurrency->isUniqueViolation($exception)) throw $exception;
            $receipt = WebhookReceipt::query()
                ->where('webhook_endpoint_id', $endpoint->id)
                ->where('idempotency_key', $idempotency)
                ->first();
            if (! $receipt) throw $exception;
            return response()->json(['accepted' => true, 'duplicate' => true, 'receipt' => $receipt->uuid]);
        }

        /** @var WebhookReceipt $receipt */
        $receipt = $result['receipt'];
        $duplicate = (bool) $result['duplicate'];
        return response()->json(
            ['accepted' => true, 'duplicate' => $duplicate, 'receipt' => $receipt->uuid],
            $duplicate ? 200 : 202,
        );
    }

    private function safeHeaders(Request $request): array
    {
        return collect(['content-type', 'user-agent', 'x-request-id', 'idempotency-key'])
            ->mapWithKeys(fn (string $name): array => [$name => $request->header($name)])
            ->filter()
            ->all();
    }
}
