<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiConnection;
use App\Models\AiGenerationRun;
use App\Nexora\Ai\Services\AiGenerationService;
use App\Nexora\Ai\Services\AiProviderRegistry;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class AiPlatformController extends Controller
{
    public function __construct(
        private AiProviderRegistry $providers,
        private AiGenerationService $generation,
        private AuditManager $audit,
    ) {}

    public function index(): Response
    {
        $providers = collect($this->providers->all())
            ->map(fn ($provider, string $key): array => ['key' => $key, 'label' => $provider->label()])
            ->values();

        return Inertia::render('Admin/Ai/Index', [
            'providers' => $providers,
            'connections' => AiConnection::query()->latest('id')->get()->map(fn (AiConnection $connection): array => [
                'id' => $connection->id,
                'uuid' => $connection->uuid,
                'name' => $connection->name,
                'providerKey' => $connection->provider_key,
                'model' => $connection->model,
                'enabled' => $connection->enabled,
                'hasCredentials' => (array) $connection->credentials !== [],
                'settings' => (array) $connection->settings,
                'maxInputChars' => $connection->max_input_chars,
                'maxOutputTokens' => $connection->max_output_tokens,
                'dailyRequestLimit' => $connection->daily_request_limit,
                'lastHealthStatus' => $connection->last_health_status,
                'lastHealthMessage' => $connection->last_health_message,
                'lastHealthCheckedAt' => $connection->last_health_checked_at?->toIso8601String(),
            ])->values(),
            'recentRuns' => AiGenerationRun::query()->with('connection:id,name')->latest('id')->limit(20)->get()->map(fn (AiGenerationRun $run): array => [
                'id' => $run->id,
                'uuid' => $run->uuid,
                'connectionName' => $run->connection?->name,
                'providerKey' => $run->provider_key,
                'model' => $run->model,
                'status' => $run->status,
                'promptChars' => $run->prompt_chars,
                'requestedOutputTokens' => $run->requested_output_tokens,
                'inputTokens' => $run->input_tokens,
                'outputTokens' => $run->output_tokens,
                'outputChars' => $run->output_chars,
                'errorCode' => $run->error_code,
                'startedAt' => $run->started_at?->toIso8601String(),
                'completedAt' => $run->completed_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedConnection($request, true);
        $this->assertUniqueName((string) $data['name']);
        $credentials = $this->decodeObject((string) ($data['credentials_json'] ?? ''), 'credentials_json');
        $settings = $this->decodeObject((string) ($data['settings_json'] ?? ''), 'settings_json');
        $this->assertSettingsContainNoSecrets($settings);

        $connection = AiConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => trim((string) $data['name']),
            'provider_key' => $data['provider_key'],
            'model' => trim((string) $data['model']),
            'enabled' => false,
            'credentials' => $credentials,
            'settings' => $settings,
            'max_input_chars' => $data['max_input_chars'],
            'max_output_tokens' => $data['max_output_tokens'],
            'daily_request_limit' => $data['daily_request_limit'],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);
        $this->audit->record('ai.connection.created', $connection, ['provider_key' => $connection->provider_key, 'model' => $connection->model], $request);
        return back()->with('success', 'AI connection created. Test it before enabling generation.');
    }

    public function update(Request $request, AiConnection $connection): RedirectResponse
    {
        $data = $this->validatedConnection($request, false, (string) $connection->provider_key);
        $this->assertUniqueName((string) $data['name'], $connection->id);
        $providerKey = (string) ($data['provider_key'] ?? $connection->provider_key);
        $providerChanged = $providerKey !== $connection->provider_key;
        $credentialJson = trim((string) ($data['credentials_json'] ?? ''));
        if ($providerChanged && $credentialJson === '') {
            throw ValidationException::withMessages([
                'credentials_json' => 'Changing AI provider requires an explicit Credentials JSON value, including {} when the new provider uses no secret credentials.',
            ]);
        }
        $nextCredentials = $credentialJson === ''
            ? (array) $connection->credentials
            : $this->decodeObject($credentialJson, 'credentials_json');
        $nextSettings = $this->decodeObject((string) ($data['settings_json'] ?? ''), 'settings_json');
        $this->assertSettingsContainNoSecrets($nextSettings);
        $connectivityChanged = $providerChanged
            || trim((string) $data['model']) !== $connection->model
            || $nextCredentials !== (array) $connection->credentials
            || $nextSettings !== (array) $connection->settings;

        $attributes = [
            'name' => trim((string) $data['name']),
            'provider_key' => $providerKey,
            'model' => trim((string) $data['model']),
            'credentials' => $nextCredentials,
            'settings' => $nextSettings,
            'max_input_chars' => $data['max_input_chars'],
            'max_output_tokens' => $data['max_output_tokens'],
            'daily_request_limit' => $data['daily_request_limit'],
            'updated_by' => $request->user()?->id,
        ];
        if ($connectivityChanged) {
            $attributes += ['enabled' => false, 'last_health_status' => null, 'last_health_message' => null, 'last_health_checked_at' => null];
        }
        $connection->forceFill($attributes)->save();
        $this->audit->record('ai.connection.updated', $connection, ['connectivity_changed' => $connectivityChanged], $request);
        return back()->with('success', $connectivityChanged ? 'AI connection updated and disabled. Test it again before enabling.' : 'AI connection updated.');
    }

    public function test(Request $request, AiConnection $connection): RedirectResponse
    {
        $provider = $this->providers->get((string) $connection->provider_key);
        if ($provider === null) return back()->with('error', 'The selected AI provider adapter is not registered.');

        try {
            $health = $provider->health((array) $connection->credentials, (array) $connection->settings);
            $ok = ($health['ok'] ?? false) === true;
        } catch (Throwable) {
            $ok = false;
        }
        $message = $ok ? 'Healthy.' : 'AI provider health check failed.';

        $connection->forceFill([
            'last_health_status' => $ok ? 'healthy' : 'unhealthy',
            'last_health_message' => $message,
            'last_health_checked_at' => now(),
            'enabled' => $ok ? $connection->enabled : false,
        ])->save();
        $this->audit->record('ai.connection.tested', $connection, ['ok' => $ok, 'provider_key' => $connection->provider_key], $request);
        return back()->with($ok ? 'success' : 'error', $ok ? 'AI connection is healthy.' : 'AI connection health check failed.');
    }

    public function toggle(Request $request, AiConnection $connection): RedirectResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $enabled = (bool) $data['enabled'];
        if ($enabled && ($connection->last_health_status !== 'healthy' || ! $this->providers->has((string) $connection->provider_key))) {
            return back()->with('error', 'A healthy registered provider is required before enabling this AI connection.');
        }
        $connection->forceFill(['enabled' => $enabled, 'updated_by' => $request->user()?->id])->save();
        $this->audit->record('ai.connection.toggled', $connection, ['enabled' => $enabled], $request);
        return back()->with('success', $enabled ? 'AI connection enabled.' : 'AI connection disabled.');
    }

    public function destroy(Request $request, AiConnection $connection): RedirectResponse
    {
        if ($connection->enabled) return back()->with('error', 'Disable this AI connection before deleting it.');
        $this->audit->record('ai.connection.deleted', $connection, ['provider_key' => $connection->provider_key, 'name' => $connection->name], $request);
        $connection->delete();
        return back()->with('success', 'AI connection deleted.');
    }

    public function generate(Request $request, AiConnection $connection): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:200000'],
            'max_output_tokens' => ['required', 'integer', 'min:1', 'max:32768'],
        ]);
        try {
            $result = $this->generation->generate($connection, (string) $data['prompt'], (int) $data['max_output_tokens'], $request->user()?->id);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['prompt' => $exception->getMessage()]);
        }
        $this->audit->record('ai.generation.completed', $connection, [
            'provider_key' => $connection->provider_key,
            'model' => $connection->model,
            'prompt_chars' => mb_strlen((string) $data['prompt']),
            'output_chars' => mb_strlen($result->text),
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
        ], $request);

        return response()->json([
            'connectionName' => $connection->name,
            'model' => $connection->model,
            'text' => $result->text,
            'inputTokens' => $result->inputTokens,
            'outputTokens' => $result->outputTokens,
        ]);
    }

    /** @return array<string,mixed> */
    private function validatedConnection(Request $request, bool $includeProvider, ?string $existingProvider = null): array
    {
        $providerKeys = array_keys($this->providers->all());
        $rules = [
            'name' => ['required', 'string', 'max:180'],
            'model' => ['required', 'string', 'max:190'],
            'credentials_json' => ['nullable', 'string', 'max:20000'],
            'settings_json' => ['nullable', 'string', 'max:20000'],
            'max_input_chars' => ['required', 'integer', 'min:1', 'max:200000'],
            'max_output_tokens' => ['required', 'integer', 'min:1', 'max:32768'],
            'daily_request_limit' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
        if ($includeProvider) $rules['provider_key'] = ['required', 'string', Rule::in($providerKeys)];
        else $rules['provider_key'] = ['nullable', 'string', Rule::in($providerKeys === [] ? [$existingProvider] : $providerKeys)];
        return $request->validate($rules);
    }

    /** @return array<string,mixed> */
    private function decodeObject(string $json, string $field): array
    {
        $json = trim($json);
        if ($json === '') return [];
        $decoded = json_decode($json, true);
        if (! is_array($decoded) || array_is_list($decoded)) {
            throw ValidationException::withMessages([$field => 'Enter a JSON object with named keys.']);
        }
        return $decoded;
    }

    /** @param array<string,mixed> $settings */
    private function assertSettingsContainNoSecrets(array $settings): void
    {
        $walk = function (array $values, string $path = 'settings') use (&$walk): void {
            foreach ($values as $key => $value) {
                $normalized = strtolower(str_replace(['-', ' '], '_', (string) $key));
                if (preg_match('/(?:^|_)(?:password|passwd|secret|token|api_?key|access_?key|private_?key|credential)(?:_|$)/', $normalized) === 1) {
                    throw ValidationException::withMessages([
                        'settings_json' => "Secret-like setting [{$path}.{$key}] must be stored in Credentials JSON instead.",
                    ]);
                }
                if (is_array($value) && ! array_is_list($value)) $walk($value, $path.'.'.$key);
            }
        };
        $walk($settings);
    }

    private function assertUniqueName(string $name, ?int $ignoreId = null): void
    {
        $query = AiConnection::query()->where('name', trim($name));
        if ($ignoreId !== null) $query->whereKeyNot($ignoreId);
        if ($query->exists()) throw ValidationException::withMessages(['name' => 'An AI connection with this name already exists in the current organization.']);
    }
}
