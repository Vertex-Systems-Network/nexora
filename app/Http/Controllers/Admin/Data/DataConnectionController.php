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

                return [
                'id' => $connection->id,
                'name' => $displayName,
                'driver' => $connection->driver,
                'provider' => $connection->provider,
                'purpose' => $connection->purpose,
                'status' => $connection->status,
                'enabled' => $connection->is_enabled,
                'endpoint' => $connection->endpoint,
                'database' => $connection->database,
                'username' => $connection->username,
                'lastTestedAt' => $connection->last_tested_at?->toIso8601String(),
                'lastError' => $connection->last_error,
                ];
            }),
            'catalog' => array_values($catalog),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'driver' => ['required', 'string', Rule::in($this->catalog->keys())],
            'endpoint' => ['required', 'string', 'max:500'],
            'database' => ['nullable', 'string', 'max:180'],
            'username' => ['nullable', 'string', 'max:180'],
            'password' => ['nullable', 'string', 'max:1000'],
            'region' => ['nullable', 'string', 'max:80'],
            'access_key' => ['nullable', 'string', 'max:500'],
            'secret_key' => ['nullable', 'string', 'max:1000'],
        ]);

        $definition = $this->catalog->get((string) $validated['driver']);
        $connection = DataConnection::query()->create([
            'name' => $validated['name'],
            'provider' => $definition['provider'],
            'driver' => $definition['key'],
            'purpose' => 'auxiliary',
            'status' => ($definition['available'] ?? false) ? 'untested' : 'adapter-missing',
            'is_enabled' => false,
            'endpoint' => $validated['endpoint'],
            'database' => $validated['database'] ?? null,
            'username' => $validated['username'] ?? null,
            'secret_payload' => array_filter([
                'password' => $validated['password'] ?? null,
                'username' => $validated['username'] ?? null,
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
        $validated = $request->validate(['enabled' => ['required', 'boolean']]);
        $enabled = (bool) $validated['enabled'];

        if ($enabled && $connection->status !== 'healthy') {
            return back()->with('error', 'Test this data connection successfully before enabling it for module use.');
        }

        $connection->forceFill(['is_enabled' => $enabled])->save();
        $this->audit->record('data.connection.toggled', $connection, ['enabled' => $enabled, 'driver' => $connection->driver]);

        return back()->with('success', $enabled ? 'Data connection enabled.' : 'Data connection disabled.');
    }

    public function destroy(DataConnection $connection): RedirectResponse
    {
        $this->audit->record('data.connection.deleted', $connection, ['driver' => $connection->driver, 'name' => $connection->name]);
        $connection->delete();
        return back()->with('success', 'Data connection removed.');
    }
}
