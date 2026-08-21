<?php

declare(strict_types=1);

namespace App\Nexora\Ai\Services;

use App\Models\AiConnection;
use App\Models\AiGenerationRun;
use App\Nexora\Ai\Data\AiTextGenerationRequest;
use App\Nexora\Ai\Data\AiTextGenerationResult;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final readonly class AiGenerationService
{
    public function __construct(
        private AiProviderRegistry $providers,
        private ConcurrencyGuard $concurrency,
    ) {}

    public function generate(AiConnection $connection, string $prompt, int $maxOutputTokens, ?int $userId = null): AiTextGenerationResult
    {
        $promptChars = mb_strlen($prompt);
        if (trim($prompt) === '') throw new InvalidArgumentException('AI prompt cannot be empty.');

        $scoped = AiConnection::query()->whereKey($connection->id)->first();
        if ($scoped === null || ! $scoped->enabled) {
            throw new InvalidArgumentException('The selected AI connection is unavailable or disabled.');
        }
        if ($promptChars > max(1, min(200000, (int) $scoped->max_input_chars))) {
            throw new InvalidArgumentException('AI prompt exceeds this connection input limit.');
        }
        $maxOutputTokens = max(1, $maxOutputTokens);
        $connectionOutputLimit = max(1, min(32768, (int) $scoped->max_output_tokens));
        if ($maxOutputTokens > $connectionOutputLimit) {
            throw new InvalidArgumentException('Requested AI output exceeds this connection token limit.');
        }

        $provider = $this->providers->get((string) $scoped->provider_key);
        if ($provider === null) {
            throw new InvalidArgumentException('The selected AI provider adapter is not registered.');
        }
        if ($scoped->last_health_status !== 'healthy') {
            throw new InvalidArgumentException('Test this AI connection successfully before generation.');
        }

        $run = $this->reserveRun($scoped, $prompt, $promptChars, $maxOutputTokens, $userId);

        try {
            $result = $provider->generate(new AiTextGenerationRequest(
                model: (string) $scoped->model,
                prompt: $prompt,
                maxOutputTokens: $maxOutputTokens,
                credentials: (array) $scoped->credentials,
                settings: (array) $scoped->settings,
            ));
            $this->assertValidResult($result, $maxOutputTokens);

            $run->forceFill([
                'status' => 'succeeded',
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
                'output_sha256' => hash('sha256', $result->text),
                'output_chars' => mb_strlen($result->text),
                'provider_request_id' => $this->boundedRequestId($result->providerRequestId),
                'completed_at' => now(),
                'error_code' => null,
                'error_message' => null,
            ])->save();

            return $result;
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => 'failed',
                'completed_at' => now(),
                'error_code' => 'provider_generation_failed',
                'error_message' => 'AI provider generation failed.',
            ])->save();

            if ($exception instanceof InvalidArgumentException) throw $exception;
            throw new InvalidArgumentException('AI provider generation failed.');
        }
    }

    private function reserveRun(AiConnection $connection, string $prompt, int $promptChars, int $maxOutputTokens, ?int $userId): AiGenerationRun
    {
        $tenantId = trim((string) $connection->tenant_id);
        if ($tenantId === '') throw new InvalidArgumentException('AI generation requires an active tenant identity.');
        $day = now()->format('Y-m-d');

        return $this->concurrency->mutex(
            'ai.generate.'.hash('sha256', $tenantId.'|'.$connection->id.'|'.$day),
            function () use ($connection, $prompt, $promptChars, $maxOutputTokens, $userId): AiGenerationRun {
                $locked = AiConnection::query()->whereKey($connection->id)->lockForUpdate()->first();
                if ($locked === null || ! $locked->enabled) {
                    throw new InvalidArgumentException('The selected AI connection is unavailable or disabled.');
                }

                $limit = max(1, min(100000, (int) $locked->daily_request_limit));
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                $used = AiGenerationRun::query()
                    ->where('ai_connection_id', $locked->id)
                    ->whereBetween('started_at', [$start, $end])
                    ->count();
                if ($used >= $limit) {
                    throw new InvalidArgumentException('This AI connection reached its daily request limit.');
                }

                return AiGenerationRun::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'ai_connection_id' => $locked->id,
                    'user_id' => $userId,
                    'provider_key' => $locked->provider_key,
                    'model' => $locked->model,
                    'status' => 'running',
                    'prompt_sha256' => hash('sha256', $prompt),
                    'prompt_chars' => $promptChars,
                    'requested_output_tokens' => $maxOutputTokens,
                    'started_at' => now(),
                ]);
            },
        );
    }

    private function assertValidResult(AiTextGenerationResult $result, int $maxOutputTokens): void
    {
        $chars = mb_strlen($result->text);
        $maxChars = min(500000, max(4096, $maxOutputTokens * 16));
        if ($chars > $maxChars) throw new InvalidArgumentException('AI provider returned output beyond the admitted response limit.');
        foreach ([$result->inputTokens, $result->outputTokens] as $tokens) {
            if ($tokens !== null && ($tokens < 0 || $tokens > 100000000)) {
                throw new InvalidArgumentException('AI provider returned invalid token accounting.');
            }
        }
        if ($result->outputTokens !== null && $result->outputTokens > $maxOutputTokens) {
            throw new InvalidArgumentException('AI provider exceeded the requested output token limit.');
        }
    }

    private function boundedRequestId(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, 255);
    }
}
