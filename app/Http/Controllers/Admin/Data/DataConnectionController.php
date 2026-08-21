<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Data;

use App\Http\Controllers\Controller;
use App\Models\DataConnection;
use App\Nexora\Data\ConnectionCatalog;
use App\Nexora\Data\ConnectionTester;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class DataConnectionController extends Controller
{
    public function __construct(
        private ConnectionCatalog $catalog,
        private ConnectionTester $tester,
        private AuditManager $audit,
    ) {
    }

    public function index(): Response
    {
        $catalog = $this->catalog->all();

        return Inertia::render('Admin/Data/Connections', [
            'connections' => DataConnection::query()->latest()->get()->map(function (DataConnection $connection) use ($catalog): array {
                $definition = $catalog[$connection->driver] ?? null;
                $rawName = trim((string) $connection->name);
                $displayName = $rawName === '' || $rawName === $connection->driver || str_replace('-', '_', strtolower($rawName)) === $connection->driver
                    ? (string) ($definition['label'] ?? $connection->driver)
                    : $rawName;
                $secret = (array) ($connection->secret_payload ?? []);
                $options = (array) ($connection->options ?? []);

                return [
                    'id' => $connection->id,
                    'name' => $displayName,
                    'driver' => $connection->driver,
                    'provider' => $connection->provider,
                    'purpose' => $connection->purpose,
                    'status' => $connection->status,
                    'enabled' => $connection->is_enabled,
                    'endpoint' => $this->safeEndpoint((string) ($connection->endpoint ?? '')),
                    'database' => $connection->database,
                    'username' => $connection->username,
                    'region' => (string) ($options['region'] ?? ''),
                    'hasPassword' => (string) ($secret['password'] ?? '') !== '',
                    'hasAccessKey' => (string) ($secret['key'] ?? '') !== '',
                    'hasSecretKey' => (string) ($secret['secret'] ?? '') !== '',
                    'lastTestedAt' => $connection->last_tested_at?->toIso8601String(),
                    'lastError' => $connection->last_error,
                ];
            }),
            'catalog' => array_values($catalog),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateConnectionPayload($request, true);
        $this->assertEndpointDoesNotContainCredentials((string) ($validated['endpoint'] ?? ''));
        $definition = $this->catalog->get((string) $validated['driver']);
        $this->assertUniqueName((string) $definition['provider'], (string) $validated['name']);

        $connection = DataConnection::query()->create([
            'name' => $validated['name'],
            'provider' => $definition['provider'],
            'driver' => $definition['key'],
            'purpose' => 'auxiliary',
            'status' => ($definition['available'] ?? false) ? 'untested' : 'adapter-missing',
            'is_enabled' => false,
            'endpoint' => $validated['endpoint'] ?? null,
            'database' => $validated['database'] ?? null,
            'username' => $validated['username'] ?? null,
            'secret_payload' => array_filter([
                'password' => $validated['password'] ?? null,
                'key' => $validated['access_key'] ?? null,
                'secret' => $validated['secret_key'] ?? null,
            ], static fn ($value): bool => $value !== null && $value !== ''),
            'options' => array_filter(['region' => $validated['region'] ?? null]),
        ]);

        $this->audit->record('data.connection.created', $connection, ['driver' => $connection->driver]);

        return back()->with('success', 'Data connection added. Test it before using it in a module.');
    }

    public function test(DataConnection $connection): JsonResponse
    {
        $result = $this->tester->test($connection);
        $connection->forceFill([
            'status' => $result['ok'] ? 'healthy' : 'failed',
            'last_tested_at' => now(),
            'last_error' => $result['ok'] ? null : $result['message'],
        ])->save();
        $this->audit->record('data.connection.tested', $connection, ['ok' => $result['ok'], 'driver' => $connection->driver]);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function toggle(Request $request, DataConnection $connection): RedirectResponse
    {
        if ($request->has('enabled')) {
            return $this->toggleEnabled($request, $connection);
        }

        return $this->updateConfiguration($request, $connection);
    }

    public function destroy(DataConnection $connection): RedirectResponse
    {
        if ($connection->is_enabled) {
            return back()->with('error', 'Disable this data connection before removing it.');
        }

        $this->audit->record('data.connection.deleted', $connection, [
            'driver' => $connection->driver,
            'name' => $connection->name,
        ]);
        $connection->delete();

        return back()->with('success', 'Data connection removed.');
    }

    private function toggleEnabled(Request $request, DataConnection $connection): RedirectResponse
    {
        $validated = $request->validate(['enabled' => ['required', 'boolean']]);
        $enabled = (bool) $validated['enabled'];

        if ($enabled && $connection->status !== 'healthy') {
            return back()->with('error', 'Test this data connection successfully before enabling it for module use.');
        }

        $connection->forceFill(['is_enabled' => $enabled])->save();
        $this->audit->record('data.connection.toggled', $connection, [
            'enabled' => $enabled,
            'driver' => $connection->driver,
        ]);

        return back()->with('success', $enabled ? 'Data connection enabled.' : 'Data connection disabled.');
    }

    private function updateConfiguration(Request $request, DataConnection $connection): RedirectResponse
    {
        $validated = $this->validateConnectionPayload($request, false, (string) $connection->driver);
        $this->assertEndpointDoesNotContainCredentials((string) ($validated['endpoint'] ?? ''));
        $this->assertUniqueName((string) $connection->provider, (string) $validated['name'], $connection->id);

        $secret = (array) ($connection->secret_payload ?? []);
        $options = (array) ($connection->options ?? []);
        $nextSecret = $secret;
        foreach (['password' => 'password', 'access_key' => 'key', 'secret_key' => 'secret'] as $input => $key) {
            $value = (string) ($validated[$input] ?? '');
            if ($value !== '') $nextSecret[$key] = $value;
        }
        $nextOptions = $options;
        $nextOptions['region'] = trim((string) ($validated['region'] ?? ''));
        $nextOptions = array_filter($nextOptions, static fn ($value): bool => $value !== null && $value !== '');

        $connectivityChanged =
            (string) ($connection->endpoint ?? '') !== (string) ($validated['endpoint'] ?? '')
            || (string) ($connection->database ?? '') !== (string) ($validated['database'] ?? '')
            || (string) ($connection->username ?? '') !== (string) ($validated['username'] ?? '')
            || $nextOptions !== $options
            || $nextSecret !== $secret;

        $attributes = [
            'name' => $validated['name'],
            'endpoint' => $validated['endpoint'] ?? null,
            'database' => $validated['database'] ?? null,
            'username' => $validated['username'] ?? null,
            'secret_payload' => $nextSecret,
            'options' => $nextOptions,
        ];

        if ($connectivityChanged) {
            $definition = $this->catalog->get((string) $connection->driver);
            $attributes += [
                'status' => ($definition['available'] ?? false) ? 'untested' : 'adapter-missing',
                'is_enabled' => false,
                'last_tested_at' => null,
                'last_error' => null,
            ];
        }

        $connection->forceFill($attributes)->save();
        $this->audit->record('data.connection.updated', $connection, [
            'driver' => $connection->driver,
            'connectivity_changed' => $connectivityChanged,
        ]);

        return back()->with(
            'success',
            $connectivityChanged
                ? 'Connection updated and disabled. Run a successful test before enabling it again.'
                : 'Connection details updated.',
        );
    }

    /** @return array<string,mixed> */
    private function validateConnectionPayload(Request $request, bool $includeDriver, ?string $existingDriver = null): array
    {
        $driverKey = trim((string) ($includeDriver ? $request->input('driver', '') : $existingDriver));
        $definition = null;
        if ($driverKey !== '') {
            try {
                $definition = $this->catalog->get($driverKey);
            } catch (\InvalidArgumentException) {
                $definition = null;
            }
        }

        $endpointRequired = (bool) ($definition['endpoint_required'] ?? true);
        $databaseSupported = (bool) ($definition['database_supported'] ?? true);
        $usernamePasswordSupported = (bool) ($definition['username_password_supported'] ?? true);
        $regionRequired = (bool) ($definition['region_required'] ?? false);
        $awsKeyPairSupported = (bool) ($definition['aws_key_pair_supported'] ?? false);

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'endpoint' => [$endpointRequired ? 'required' : 'nullable', 'string', 'max:500'],
            'database' => [$databaseSupported ? 'nullable' : 'prohibited', 'string', 'max:180'],
            'username' => [$usernamePasswordSupported ? 'nullable' : 'prohibited', 'string', 'max:180'],
            'password' => [$usernamePasswordSupported ? 'nullable' : 'prohibited', 'string', 'max:1000'],
            'region' => [$regionRequired ? 'required' : 'nullable', 'string', 'max:80'],
            'access_key' => [$awsKeyPairSupported ? 'nullable' : 'prohibited', 'string', 'max:500'],
            'secret_key' => [$awsKeyPairSupported ? 'nullable' : 'prohibited', 'string', 'max:1000'],
        ];
        if ($includeDriver) {
            $rules['driver'] = ['required', 'string', Rule::in($this->catalog->keys())];
        }

        $validated = $request->validate($rules);
        if ($awsKeyPairSupported) {
            $accessKey = trim((string) ($validated['access_key'] ?? ''));
            $secretKey = (string) ($validated['secret_key'] ?? '');
            if (($accessKey === '') !== ($secretKey === '')) {
                throw ValidationException::withMessages([
                    'access_key' => 'AWS access key and secret key must be entered together, or both left blank to use the runtime IAM credential chain.',
                ]);
            }
        }

        return $validated;
    }

    private function assertUniqueName(string $provider, string $name, ?int $ignoreId = null): void
    {
        $query = DataConnection::query()
            ->where('provider', $provider)
            ->where('name', trim($name));
        if ($ignoreId !== null) $query->whereKeyNot($ignoreId);
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A connection with this name already exists for this provider in the current organization.',
            ]);
        }
    }

    private function assertEndpointDoesNotContainCredentials(string $endpoint): void
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://[^/@\s]+@#i', trim($endpoint)) !== 1) return;

        throw ValidationException::withMessages([
            'endpoint' => 'Do not place usernames, passwords or tokens inside the endpoint. Use the encrypted credential fields instead.',
        ]);
    }

    private function safeEndpoint(string $endpoint): string
    {
        return preg_replace(
            '#^([a-z][a-z0-9+.-]*://)([^/@\s]+)@#i',
            '$1[redacted]@',
            trim($endpoint),
        ) ?? '';
    }
}
