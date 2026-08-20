<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Nexora\Data\ConnectionCatalog;
use App\Nexora\Data\ConnectionTester;
use App\Nexora\Installation\Database\DatabaseDriverRegistry;
use App\Nexora\Installation\DatabaseBackupManager;
use App\Nexora\Installation\DatabaseProvisioner;
use App\Nexora\Installation\Exceptions\InstallationCancelledException;
use App\Nexora\Installation\InstallationRunControl;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Installation\Installer;
use App\Nexora\Installation\SourceActivationIdentity;
use App\Nexora\Installation\SourceActivationHandshake;
use App\Nexora\Installation\RuntimePostInstallHandoff;
use App\Nexora\Installation\SystemRequirementChecker;
use App\Nexora\Security\Password\PasswordStrengthEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class InstallerController extends Controller
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(
        private InstallationState $state,
        private SystemRequirementChecker $requirements,
        private DatabaseProvisioner $database,
        private DatabaseDriverRegistry $databaseDrivers,
        private ConnectionCatalog $dataConnections,
        private ConnectionTester $dataConnectionTester,
        private DatabaseBackupManager $backups,
        private InstallationRunControl $runControl,
        private PasswordStrengthEvaluator $passwordStrength,
        private Installer $installer,
        private SourceActivationIdentity $sourceActivation,
        private SourceActivationHandshake $sourceHandshake,
        private RuntimePostInstallHandoff $postInstallHandoff,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->state->isInstalled()) {
            $handoff = $this->postInstallHandoff->inspect();

            return ($handoff['ready'] ?? false) === true
                ? redirect()->route('login')
                : redirect()->route('install.runtime.handoff');
        }

        return view('install.index', [
            'requirements' => $this->requirements->check(),
            'sourceIdentity' => $this->publicSourceIdentity($this->sourceActivation->inspect()),
            'defaults' => [
                'app_name' => (string) config('app.name', 'Nexora'),
                'app_url' => $request->getSchemeAndHttpHost(),
                'language' => app()->getLocale(),
                'db_driver' => 'mysql',
                'db_host' => (string) config('database.connections.mysql.host', '127.0.0.1'),
                'db_port' => (int) config('database.connections.mysql.port', 3306),
                'db_database' => (string) config('database.connections.mysql.database', 'nexora'),
                'db_username' => (string) config('database.connections.mysql.username', 'root'),
            ],
            'databaseDrivers' => $this->databaseDrivers->all(),
            'databaseDriverOptions' => array_values(array_map(static fn (array $driver): array => [
                'value' => (string) $driver['key'],
                'label' => (string) $driver['label'],
                'description' => (string) ($driver['description'] ?? $driver['minimum'] ?? ''),
                'provider' => (string) ($driver['provider'] ?? 'native'),
                'group' => (string) ($driver['group'] ?? 'Databases'),
                'disabled' => ! (bool) ($driver['available'] ?? false),
            ], $this->databaseDrivers->all())),
            'dataServices' => $this->dataConnections->all(),
            'languageOptions' => array_map(static fn (array $meta, string $code): array => [
                'value' => $code,
                'label' => (string) ($meta['name'] ?? $code),
                'description' => trim((string) ($meta['native'] ?? '').' · '.(string) ($meta['country'] ?? ''), ' ·'),
                'flag' => (string) ($meta['flag'] ?? ''),
                'flag_url' => (string) ($meta['flag_asset'] ?? ''),
                'group' => '',
                'disabled' => false,
            ], (array) config('localization.supported', []), array_keys((array) config('localization.supported', []))),
            'recovery' => $this->runControl->recoverySummary($request->session()->getId()),
            'localization' => [
                'current' => app()->getLocale(),
                'direction' => (string) config('localization.supported.'.app()->getLocale().'.dir', 'ltr'),
                'supported' => (array) config('localization.supported', []),
            ],
        ]);
    }


    public function testDataService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver' => ['required', 'string', Rule::in($this->dataConnections->keys())],
            'endpoint' => ['nullable', 'string', 'max:500'],
            'database' => ['nullable', 'string', 'max:180'],
            'username' => ['nullable', 'string', 'max:180'],
            'password' => ['nullable', 'string', 'max:1000'],
            'region' => ['nullable', 'string', 'max:80'],
            'access_key' => ['nullable', 'string', 'max:500'],
            'secret_key' => ['nullable', 'string', 'max:1000'],
        ]);

        $definition = $this->dataConnections->get((string) $validated['driver']);
        if (! ($definition['available'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($definition['availability_message'] ?? 'Connector runtime is unavailable.'),
            ], 422);
        }

        $result = $this->dataConnectionTester->testPayload($validated);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function runtimeHandoff(): View|RedirectResponse
    {
        if (! $this->state->isInstalled()) {
            return redirect()->route('install.index');
        }

        $handoff = $this->postInstallHandoff->inspect();
        if (($handoff['ready'] ?? false) !== true) {
            try {
                // This route is intentionally a fresh HTTP request after the
                // installer committed installed.lock. It is therefore the first
                // safe place to finalize install-sensitive environment,
                // activation, service and process fingerprints.
                $this->postInstallHandoff->verifyAndRecord();
                $handoff = $this->postInstallHandoff->inspect();
            } catch (Throwable $exception) {
                report($exception);
                $handoff = $this->postInstallHandoff->inspect();
                $handoff['errors'] = array_values(array_unique([
                    ...(array) ($handoff['errors'] ?? []),
                    $exception->getMessage(),
                ]));
            }
        }

        if (($handoff['ready'] ?? false) === true) {
            return redirect()->route('login')
                ->with('success', 'Nexora installation completed and the committed runtime identity is ready.');
        }

        return view('install.runtime-handoff', [
            'handoff' => $handoff,
            'sourceIdentity' => $this->publicSourceIdentity($this->sourceActivation->inspect()),
        ]);
    }

    public function sourceStatus(Request $request): JsonResponse
    {
        $state = $this->sourceActivation->inspect();
        $token = trim((string) $request->header('X-Nexora-Activation-Token', ''));
        $authorized = $token !== '';
        $handshake = $authorized
            ? $this->sourceHandshake->acknowledgeWeb($state, $token)
            : $this->sourceHandshake->inspect($state);
        $acknowledged = ($handshake['acknowledgement_authorized'] ?? false) === true;

        if ($authorized && ! $acknowledged) {
            return response()
                ->json($this->redactedSourceStatus($state, $handshake), 403)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('X-Nexora-Source-Ack', 'denied');
        }

        $payload = $acknowledged
            ? [
                ...$state,
                'activation_handshake' => $handshake,
                'diagnostic_detail' => 'authorized',
            ]
            : $this->redactedSourceStatus($state, $handshake);

        return response()
            ->json($payload, ($state['status'] ?? 'fail') === 'pass' ? 200 : 409)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Nexora-Platform-Version', (string) ($state['platform_version'] ?? 'unknown'))
            ->header('X-Nexora-Installer-Protocol', (string) ($state['running_protocol'] ?? 'unknown'))
            ->header('X-Nexora-Source-Generation', (string) ($state['running_generation'] ?? 'unknown'))
            ->header('X-Nexora-Source-Set', sprintf(
                '%d/%d',
                (int) ($state['critical_source_files_matched'] ?? 0),
                (int) ($state['critical_source_files'] ?? 0),
            ))
            ->header('X-Nexora-Runtime-Classes', sprintf(
                '%d/%d',
                (int) ($state['runtime_classes_matched'] ?? 0),
                (int) ($state['runtime_classes_total'] ?? 0),
            ))
            ->header('X-Nexora-Source-Ack', $acknowledged ? 'acknowledged' : 'token-required');
    }

    /** @param array<string,mixed> $state @param array<string,mixed> $handshake @return array<string,mixed> */
    private function redactedSourceStatus(array $state, array $handshake): array
    {
        return [
            ...$this->publicSourceIdentity($state),
            'activation_handshake' => [
                'status' => $handshake['status'] ?? 'pending',
                'web_ack_valid' => (bool) ($handshake['web_ack_valid'] ?? false),
            ],
            'acknowledgement_token_required' => true,
            'diagnostic_detail' => 'redacted',
        ];
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private function publicSourceIdentity(array $state): array
    {
        return [
            'status' => $state['status'] ?? 'fail',
            'current' => (bool) ($state['current'] ?? false),
            'platform_version' => $state['platform_version'] ?? 'unknown',
            'running_protocol' => $state['running_protocol'] ?? 'unknown',
            'running_generation' => $state['running_generation'] ?? 'unknown',
            'source_set_status' => $state['source_set_status'] ?? 'fail',
            'critical_source_files' => (int) ($state['critical_source_files'] ?? 0),
            'critical_source_files_matched' => (int) ($state['critical_source_files_matched'] ?? 0),
            'runtime_class_status' => $state['runtime_class_status'] ?? 'fail',
            'runtime_classes_total' => (int) ($state['runtime_classes_total'] ?? 0),
            'runtime_classes_matched' => (int) ($state['runtime_classes_matched'] ?? 0),
            'errors' => ($state['status'] ?? 'fail') === 'pass'
                ? []
                : ['Critical source/runtime mismatch. Run the local Nexora source-status command for detailed diagnostics.'],
        ];
    }

    public function testDatabase(Request $request): JsonResponse
    {
        if ($this->state->isInstalled()) {
            return response()->json(['ok' => false, 'message' => 'Nexora is already installed.'], 409);
        }

        $validated = $this->validatedDatabaseInput($request, true);
        $result = $this->database->test($validated, (bool) ($validated['create'] ?? false));
        if ($result['ok']) {
            $recovery = $this->runControl->recoveryForDatabase($validated);
            $result['interrupted_installation'] = $recovery !== null;
            $result['recoverable_installation'] = ($recovery['resume_compatible'] ?? false) === true;
            $result['recovery_compatible'] = ($recovery['resume_compatible'] ?? false) === true;
            $result['recovery_reason'] = $recovery['resume_reason'] ?? null;
            $result['recoverable_stage'] = $recovery['stage'] ?? null;
            $result['recoverable_status'] = $recovery['status'] ?? null;
            $result['recoverable_platform_version'] = $recovery['platform_version'] ?? null;
            $result['recoverable_installer_protocol'] = $recovery['installer_protocol'] ?? null;
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function backupDatabase(Request $request): StreamedResponse
    {
        if ($this->state->isInstalled()) {
            abort(409, 'Nexora is already installed.');
        }

        $database = $this->validatedDatabaseInput($request, false);
        $sessionId = $request->session()->getId();

        return response()->stream(function () use ($database, $sessionId): void {
            @ini_set('zlib.output_compression', '0');
            @set_time_limit(0);
            if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
            while (ob_get_level() > 0) { @ob_end_flush(); }
            ob_implicit_flush(true);
            $emit = static function (array $event): void {
                $event['timestamp'] ??= gmdate(DATE_ATOM);
                echo json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n";
                if (function_exists('ob_flush')) { @ob_flush(); }
                flush();
            };

            try {
                $emit(['type' => 'start', 'progress' => 0, 'message' => 'Preparing protected database backup…']);
                $metadata = $this->backups->create($database, $sessionId, $emit);
                $emit([
                    'type' => 'complete', 'ok' => true, 'progress' => 100,
                    'token' => $metadata['token'], 'file_name' => $metadata['download_name'],
                    'bytes' => $metadata['bytes'],
                    'download_url' => route('install.database.backup.download', ['token' => $metadata['token']]),
                    'message' => 'Backup complete. Download it, or choose the explicit continue-without-backup option instead.',
                ]);
            } catch (Throwable $exception) {
                report($exception);
                $emit(['type' => 'complete', 'ok' => false, 'progress' => 0, 'message' => $exception->getMessage()]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Accel-Buffering' => 'no', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadBackup(Request $request, string $token): BinaryFileResponse
    {
        if ($this->state->isInstalled()) {
            abort(404);
        }

        $file = $this->backups->file($token, $request->session()->getId());

        return response()->download($file['path'], $file['name'], [
            'Content-Type' => (string) ($file['content_type'] ?? 'application/octet-stream'),
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function stream(Request $request): StreamedResponse
    {
        if ($this->state->isInstalled()) {
            $handoff = $this->postInstallHandoff->inspect();
            $ready = ($handoff['ready'] ?? false) === true;

            return response()->stream(static function () use ($handoff, $ready): void {
                echo json_encode([
                    'type' => 'complete',
                    'ok' => $ready,
                    'committed' => true,
                    'runtime_handoff_ready' => $ready,
                    'progress' => 100,
                    'redirect' => $ready ? route('login') : null,
                    'recovery_url' => $ready ? null : route('install.runtime.handoff'),
                    'message' => $ready
                        ? 'Nexora is already installed and runtime handoff is ready.'
                        : 'Nexora is already installed, but runtime handoff still requires reconciliation.',
                    'handoff_errors' => $handoff['errors'] ?? [],
                ], JSON_UNESCAPED_SLASHES)."\n";
            }, 200, ['Content-Type' => 'application/x-ndjson; charset=UTF-8', 'Cache-Control' => 'no-store']);
        }

        $validated = $this->validatedInstallInput($request);
        $runId = bin2hex(random_bytes(12));
        $sessionId = $request->session()->getId();
        $validated['_installation_run_id'] = $runId;
        $this->runControl->start($runId, $sessionId);

        return response()->stream(function () use ($validated, $runId): void {
            @ini_set('zlib.output_compression', '0');
            @set_time_limit(0);
            if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            ob_implicit_flush(true);

            $emit = static function (array $event): void {
                $event['timestamp'] ??= gmdate(DATE_ATOM);
                echo json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n";
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();
            };

            $sourceState = $this->sourceActivation->inspect();
            $emit([
                'type' => 'start',
                'progress' => 0,
                'run_id' => $runId,
                'cancellable' => true,
                'label' => 'Installing Nexora',
                'platform_version' => (string) config('nexora.version', 'unknown'),
                'installer_protocol' => Installer::PROTOCOL,
                'source_generation' => Installer::SOURCE_GENERATION,
                'source_fingerprint' => $sourceState['fingerprint'] ?? null,
                'source_set_fingerprint' => $sourceState['source_set_fingerprint'] ?? null,
                'critical_source_files' => $sourceState['critical_source_files'] ?? null,
                'critical_source_files_matched' => $sourceState['critical_source_files_matched'] ?? null,
                'runtime_classes_total' => $sourceState['runtime_classes_total'] ?? null,
                'runtime_classes_matched' => $sourceState['runtime_classes_matched'] ?? null,
                'installer_sha256' => $sourceState['installer_sha256'] ?? null,
                'steps' => [
                    ['id' => 'preflight', 'label' => 'Installation preflight'],
                    ['id' => 'database', 'label' => 'Database verification'],
                    ['id' => 'runtime-readiness', 'label' => 'Runtime readiness preflight'],
                    ['id' => 'backup', 'label' => 'Existing database backup'],
                    ['id' => 'reset', 'label' => 'Database reset'],
                    ['id' => 'environment', 'label' => 'Environment configuration'],
                    ['id' => 'migrations', 'label' => 'Database migrations'],
                    ['id' => 'seed', 'label' => 'Core platform data'],
                    ['id' => 'admin', 'label' => 'Super Admin account'],
                    ['id' => 'runtime', 'label' => 'Nexora runtime'],
                    ['id' => 'cleanup', 'label' => 'Final cleanup'],
                    ['id' => 'lock', 'label' => 'Installation lock'],
                    ['id' => 'handoff', 'label' => 'Runtime handoff'],
                ],
            ]);
            $emit(['type' => 'padding', 'message' => str_repeat(' ', 4096)]);

            $lastStage = null;
            $lastLabel = null;

            try {
                $this->installer->install($validated, static function (array $event) use ($emit, &$lastStage, &$lastLabel): void {
                    if (($event['status'] ?? null) === 'running') {
                        $lastStage = $event['stage'] ?? $lastStage;
                        $lastLabel = $event['label'] ?? $lastLabel;
                    }
                    $output = $event['output'] ?? null;
                    unset($event['output']);
                    $emit($event);
                    if (is_string($output) && trim($output) !== '') {
                        $emit([
                            'type' => 'log',
                            'stage' => $event['stage'] ?? null,
                            'message' => $output,
                            'progress' => $event['progress'] ?? null,
                        ]);
                    }
                });

                $this->finishRunBestEffort($runId, 'committed-runtime-pending', 'handoff');
                $emit([
                    'type' => 'complete',
                    'ok' => false,
                    'committed' => true,
                    'runtime_handoff_ready' => false,
                    'progress' => 100,
                    'redirect' => null,
                    'recovery_url' => route('install.runtime.handoff'),
                    'message' => 'Installation committed. Opening a fresh runtime handoff request to seal the exact installed environment before login.',
                ]);
            } catch (InstallationCancelledException $exception) {
                $this->finishRunBestEffort($runId, 'cancelled');
                $emit(['type' => 'complete', 'ok' => false, 'cancelled' => true, 'progress' => 0, 'message' => $exception->getMessage()]);
            } catch (Throwable $exception) {
                report($exception);

                if ($this->installationCommitIsValid()) {
                    $handoff = $this->postInstallHandoff->inspect();
                    if (($handoff['ready'] ?? false) !== true) {
                        $this->finishRunBestEffort(
                            $runId,
                            'committed-runtime-pending',
                            $lastStage ?: 'handoff',
                            $exception->getMessage(),
                        );
                        $emit([
                            'type' => 'complete',
                            'ok' => false,
                            'committed' => true,
                            'runtime_handoff_ready' => false,
                            'progress' => 100,
                            'redirect' => null,
                            'recovery_url' => route('install.runtime.handoff'),
                            'message' => 'Nexora installation is durably committed, but runtime handoff is not ready. '
                                .'Do not retry the installer. Reload/restart PHP, then run '
                                .'`php artisan nexora:runtime:post-install-reconcile --confirm=RECONCILE` and '
                                .'`php artisan nexora:runtime:post-install-status --assert-ready`.',
                            'handoff_errors' => array_values((array) ($handoff['errors'] ?? [])),
                        ]);
                        return;
                    }

                    $this->finishRunBestEffort($runId, 'completed-with-warning', $lastStage, $exception->getMessage());
                    $emit([
                        'type' => 'complete',
                        'ok' => true,
                        'progress' => 100,
                        'redirect' => route('login'),
                        'message' => 'Nexora installation was committed and runtime handoff is ready. '
                            .'A non-critical post-commit bookkeeping warning was recorded.',
                    ]);
                    return;
                }

                $this->finishRunBestEffort($runId, 'failed', $lastStage, $exception->getMessage());
                if (is_string($lastStage) && $lastStage !== '') {
                    $emit([
                        'type' => 'step',
                        'stage' => $lastStage,
                        'label' => $lastLabel ?: $lastStage,
                        'status' => 'failed',
                        'message' => $exception->getMessage(),
                    ]);
                }
                $emit([
                    'type' => 'complete',
                    'ok' => false,
                    'progress' => 0,
                    'message' => $exception->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Accel-Buffering' => 'no',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        if ($this->state->isInstalled()) {
            return response()->json(['ok' => false, 'message' => 'Nexora is already installed and installer controls are locked.'], 409);
        }
        $validated = $request->validate(['run_id' => ['required', 'regex:/^[a-f0-9]{24}$/']]);
        try {
            $result = $this->runControl->requestCancel((string) $validated['run_id'], $request->session()->getId());
            return response()->json($result, $result['ok'] ? 200 : 409);
        } catch (Throwable $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function status(Request $request): JsonResponse
    {
        if ($this->state->isInstalled()) {
            return response()->json(['ok' => false, 'message' => 'Nexora is already installed and installer controls are locked.'], 409);
        }
        $validated = $request->validate(['run_id' => ['required', 'regex:/^[a-f0-9]{24}$/']]);
        try {
            return response()->json(['ok' => true, 'state' => $this->runControl->status((string) $validated['run_id'], $request->session()->getId())]);
        } catch (Throwable $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->state->isInstalled()) {
            $handoff = $this->postInstallHandoff->inspect();

            return ($handoff['ready'] ?? false) === true
                ? redirect()->route('login')
                : redirect()->route('install.runtime.handoff');
        }

        $validated = $this->validatedInstallInput($request);
        $runId = bin2hex(random_bytes(12));
        $validated['_installation_run_id'] = $runId;
        $this->runControl->start($runId, $request->session()->getId());

        try {
            $this->installer->install($validated);
            $this->finishRunBestEffort($runId, 'completed');
        } catch (Throwable $exception) {
            report($exception);

            if ($this->installationCommitIsValid()) {
                $handoff = $this->postInstallHandoff->inspect();
                if (($handoff['ready'] ?? false) !== true) {
                    $this->finishRunBestEffort(
                        $runId,
                        'committed-runtime-pending',
                        'handoff',
                        $exception->getMessage(),
                    );

                    return redirect()
                        ->route('install.runtime.handoff')
                        ->with('warning', 'Nexora is installed, but runtime handoff must be reconciled before login.');
                }

                $this->finishRunBestEffort($runId, 'completed-with-warning', 'store', $exception->getMessage());

                return redirect()
                    ->route('login')
                    ->with('success', 'Nexora installation was committed and runtime handoff is ready. A non-critical warning was recorded.');
            }

            $this->finishRunBestEffort($runId, 'failed', 'store', $exception->getMessage());

            return back()
                ->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors(['installer' => $exception->getMessage()]);
        }

        return redirect()->route('install.runtime.handoff')
            ->with('success', 'Installation committed. Nexora is sealing the exact installed runtime identity before login.');
    }

    private function installationCommitIsValid(): bool
    {
        if (! $this->state->isInstalled()) {
            return false;
        }

        return ($this->state->inspect()['valid'] ?? false) === true;
    }

    private function finishRunBestEffort(
        string $runId,
        string $status,
        ?string $failureStage = null,
        ?string $failureMessage = null,
    ): void {
        try {
            $this->runControl->finish($runId, $status, $failureStage, $failureMessage);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @return array<string,mixed> */
    private function validatedInstallInput(Request $request): array
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'language' => ['required', 'string', Rule::in(array_keys((array) config('localization.supported', ['en' => []])))],
            'db_driver' => ['required', 'string', Rule::in($this->databaseDrivers->keys())],
            'db_host' => ['nullable', 'string', 'max:255'],
            'db_port' => ['nullable', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:512'],
            'db_username' => ['nullable', 'string', 'max:128'],
            'db_password' => ['nullable', 'string', 'max:512'],
            'db_create' => ['nullable', 'boolean'],
            'db_reset_existing' => ['nullable', 'boolean'],
            'db_existing_action' => ['nullable', 'string', Rule::in(['resume', 'reset'])],
            'db_backup_token' => ['nullable', 'string', 'size:48'],
            'db_backup_confirmed' => ['nullable', 'boolean'],
            'db_skip_backup_consent' => ['nullable', 'boolean'],
            'db_skip_backup_database' => ['nullable', 'string', 'max:512'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email:rfc', 'max:255'],
            'admin_password' => ['required', 'confirmed', 'string', 'min:10', 'max:255'],
            'password_strength_consent' => ['nullable', 'boolean'],
            'requested_data_services' => ['nullable', 'array'],
            'requested_data_services.*' => ['string', Rule::in($this->dataConnections->keys())],
            'data_services' => ['nullable', 'array'],
            'data_services.*' => ['nullable', 'array'],
            'data_services.*.endpoint' => ['nullable', 'string', 'max:500'],
            'data_services.*.database' => ['nullable', 'string', 'max:180'],
            'data_services.*.username' => ['nullable', 'string', 'max:180'],
            'data_services.*.password' => ['nullable', 'string', 'max:1000'],
            'data_services.*.region' => ['nullable', 'string', 'max:80'],
            'data_services.*.access_key' => ['nullable', 'string', 'max:500'],
            'data_services.*.secret_key' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ]);

        $driver = $this->databaseDrivers->get((string) $validated['db_driver']);
        if (! ($driver['available'] ?? false)) {
            throw ValidationException::withMessages([
                'db_driver' => (string) ($driver['availability_message'] ?? 'The selected database driver is unavailable on this server.'),
            ]);
        }
        if (($driver['network'] ?? true) && preg_match('/^[A-Za-z0-9_]+$/', (string) $validated['db_database']) !== 1) {
            throw ValidationException::withMessages([
                'db_database' => 'Database name may contain only letters, numbers and underscores for this driver.',
            ]);
        }

        $strength = $this->passwordStrength->evaluate((string) $validated['admin_password']);
        if (! ($strength['minimum_accepted'] ?? false)) {
            throw ValidationException::withMessages([
                'admin_password' => 'Use at least 10 characters and at least three character types '
                    .'(lowercase, uppercase, number, symbol).',
            ]);
        }

        $passwordConsent = $request->boolean('password_strength_consent');
        if (($strength['consent_required'] ?? false) && ! $passwordConsent) {
            throw ValidationException::withMessages([
                'admin_password' => 'This password is not rated Strong. Confirm the password-risk consent '
                    .'to use it, or choose a stronger password.',
            ]);
        }

        $validated['_installer_session_id'] = $request->session()->getId();
        $validated['_password_strength'] = $strength['level'];
        $validated['_password_strength_consent'] = $passwordConsent;

        return $validated;
    }
    /** @return array{driver:string,host:string,port:int,database:string,username:string,password:string,create:bool} */
    private function validatedDatabaseInput(Request $request, bool $includeCreate): array
    {
        $validated = $request->validate([
            'db_driver' => ['required', 'string', Rule::in($this->databaseDrivers->keys())],
            'db_host' => ['nullable', 'string', 'max:255'],
            'db_port' => ['nullable', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:512'],
            'db_username' => ['nullable', 'string', 'max:128'],
            'db_password' => ['nullable', 'string', 'max:512'],
            'db_create' => ['nullable', 'boolean'],
        ]);
        $definition = $this->databaseDrivers->get((string) $validated['db_driver']);
        if (($definition['network'] ?? true) && preg_match('/^[A-Za-z0-9_]+$/', (string) $validated['db_database']) !== 1) {
            throw ValidationException::withMessages(['db_database' => 'Database name may contain only letters, numbers and underscores.']);
        }
        return [
            'driver' => (string) $validated['db_driver'],
            'host' => (string) ($validated['db_host'] ?? $definition['default_host'] ?? ''),
            'port' => (int) ($validated['db_port'] ?? $definition['default_port'] ?? 0),
            'database' => (string) $validated['db_database'],
            'username' => (string) ($validated['db_username'] ?? ''),
            'password' => (string) ($validated['db_password'] ?? ''),
            'create' => $includeCreate ? $request->boolean('db_create') : false,
        ];
    }

}
