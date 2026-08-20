<?php

declare(strict_types=1);

require_once __DIR__.'/n1-certification-session.php';
require_once __DIR__.'/n1-target-run-lock.php';

require_once __DIR__.'/target-composer.php';
require_once __DIR__.'/n1-target-plan.php';

function nexoraTargetSupportRedact(string $text, string $root): string
{
    $patterns = [
        ['/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i', '$1[REDACTED]'],
        ['/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i', '$1[REDACTED]'],
        ['/([a-z][a-z0-9+.-]*:\/\/[^\s\/:@]+:)[^\s@]+(@)/i', '$1[REDACTED]$2'],
        ['/([?&](?:password|passwd|secret|token|api[_-]?key)=)[^&#\s]+/i', '$1[REDACTED]'],
    ];
    foreach ($patterns as [$pattern, $replacement]) {
        $text = (string) preg_replace($pattern, $replacement, $text);
    }

    $replacements = [];
    $normalizedRoot = str_replace('\\', '/', rtrim($root, '/\\'));
    if ($normalizedRoot !== '') {
        $replacements[$normalizedRoot] = '[PROJECT_ROOT]';
        $replacements[str_replace('/', '\\', $normalizedRoot)] = '[PROJECT_ROOT]';
    }
    foreach (['USERPROFILE', 'HOME', 'TMP', 'TEMP'] as $key) {
        $value = getenv($key);
        if (! is_string($value) || trim($value) === '') continue;
        $value = rtrim($value, '/\\');
        $label = in_array($key, ['TMP', 'TEMP'], true) ? '[TEMP]' : '[USER_HOME]';
        $replacements[$value] = $label;
        $replacements[str_replace('\\', '/', $value)] = $label;
        $replacements[str_replace('/', '\\', $value)] = $label;
    }
    if ($replacements !== []) $text = strtr($text, $replacements);
    $text = (string) preg_replace('/([A-Za-z]:\\\\Users\\\\)[^\\\\\r\n]+/i', '$1[USER]', $text);
    return $text;
}

function nexoraTargetSupportRead(string $path, string $root, int $maxBytes = 131072): ?string
{
    if (! is_file($path) || ! is_readable($path)) return null;
    $size = filesize($path);
    $data = file_get_contents($path, false, null, 0, $maxBytes);
    if (! is_string($data)) return null;
    if (is_int($size) && $size > $maxBytes) $data .= "\n[TRUNCATED after {$maxBytes} bytes]\n";
    return nexoraTargetSupportRedact($data, $root);
}

/** @return array<string,mixed> */
function nexoraBuildTargetSupportCapsule(string $root, string $runDir, array $summary): array
{
    $composer = nexoraLocateTargetComposer($root);
    $node = nexoraRunTargetCommand(['node', '--version'], $root);
    $npm = nexoraRunTargetCommand(['npm', '--version'], $root);
    $hash = static fn (string $file): ?string => is_file($file) ? (hash_file('sha256', $file) ?: null) : null;
    $extensionNames = ['fileinfo', 'mbstring', 'openssl', 'pdo', 'zip'];
    $extensions = [];
    foreach ($extensionNames as $extension) $extensions[$extension] = extension_loaded($extension);

    $stepLogs = [];
    foreach ((array) ($summary['steps'] ?? []) as $step) {
        if (! is_array($step)) continue;
        $stdoutPath = isset($step['stdout_log']) ? $runDir.'/'.ltrim((string) $step['stdout_log'], '/\\') : '';
        $stderrPath = isset($step['stderr_log']) ? $runDir.'/'.ltrim((string) $step['stderr_log'], '/\\') : '';
        $stepLogs[] = [
            'id' => (string) ($step['id'] ?? 'unknown'),
            'label' => (string) ($step['label'] ?? ''),
            'status' => (string) ($step['status'] ?? ''),
            'exit_code' => (int) ($step['exit_code'] ?? 0),
            'stdout' => $stdoutPath !== '' ? nexoraTargetSupportRead($stdoutPath, $root) : null,
            'stderr' => $stderrPath !== '' ? nexoraTargetSupportRead($stderrPath, $root) : null,
        ];
    }

    $jsonReport = static function (string $path) use ($root): ?array {
        $raw = nexoraTargetSupportRead($path, $root, 262144);
        if (! is_string($raw) || trim($raw) === '') return null;
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return ['unparsed_redacted_text' => $raw];
        }
    };

    $targetPlan = nexoraBuildN10TargetPlan($root);
    $nextAction = (string)($targetPlan['next_action']['command'] ?? 'Resolve the first blocker shown in this capsule, then rerun the same N1.0 target execution command.');
    $blocker = (string) (($summary['first_blocker']['id'] ?? '') ?: '');
    if(trim($nextAction)==='') $nextAction = 'Resolve the first blocker shown in this capsule, then rerun the same N1.0 target execution command.';

    return [
        'schema' => 1,
        'kind' => 'nexora-target-support-capsule',
        'generated_at' => gmdate(DATE_ATOM),
        'platform_version' => (string) ($summary['platform_version'] ?? 'unknown'),
        'source_tree_sha256' => (string) ($summary['source_tree_sha256'] ?? ''),
        'run_id' => (string) ($summary['run_id'] ?? ''),
        'status' => (string) ($summary['status'] ?? 'unknown'),
        'first_blocker' => $summary['first_blocker'] ?? null,
        'next_action' => $nextAction,
        'target_plan' => $targetPlan,
        'certification_session' => $targetPlan['certification_session'] ?? null,
        'target_execution_lock_active' => (bool)($targetPlan['target_execution_lock_active'] ?? false),
        'runtime' => [
            'os_family' => PHP_OS_FAMILY,
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'php_binary' => nexoraTargetSupportRedact(PHP_BINARY, $root),
            'php_ini' => nexoraTargetSupportRedact((string) (php_ini_loaded_file() ?: 'not-loaded'), $root),
            'required_extensions' => $extensions,
            'composer' => [
                'available' => (bool) ($composer['available'] ?? false),
                'version' => $composer['version'] ?? null,
                'source' => $composer['source'] ?? null,
                'path' => isset($composer['path']) && is_string($composer['path']) ? nexoraTargetSupportRedact($composer['path'], $root) : null,
            ],
            'node' => ['exit_code' => $node['exit_code'], 'version' => trim($node['stdout'] !== '' ? $node['stdout'] : $node['stderr']) ?: null],
            'npm' => ['exit_code' => $npm['exit_code'], 'version' => trim($npm['stdout'] !== '' ? $npm['stdout'] : $npm['stderr']) ?: null],
        ],
        'dependency_state' => [
            'composer_lock' => ['exists' => is_file($root.'/composer.lock'), 'sha256' => $hash($root.'/composer.lock')],
            'package_lock' => ['exists' => is_file($root.'/package-lock.json'), 'sha256' => $hash($root.'/package-lock.json')],
            'reviewed_locks' => ['exists' => is_file($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'), 'sha256' => $hash($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json')],
            'vendor' => is_dir($root.'/vendor'),
            'node_modules' => is_dir($root.'/node_modules'),
            'public_build' => is_dir($root.'/public/build'),
        ],
        'execution' => [
            'options' => [
                'install_dependencies' => (bool) (($summary['options']['install_dependencies'] ?? false)),
                'apply_extensions' => (bool) (($summary['options']['apply_extensions'] ?? false)),
                'refresh_locks' => (bool) (($summary['options']['refresh_locks'] ?? false)),
                'prepare_kits' => (bool) (($summary['options']['prepare_kits'] ?? false)),
                'base_url_supplied' => ! empty($summary['options']['base_url']),
                'operator_supplied' => ! empty($summary['options']['operator']),
            ],
            'artifacts' => $summary['artifacts'] ?? [],
            'steps' => $stepLogs,
        ],
        'related_reports' => [
            'prerequisite_intake' => $jsonReport($root.'/storage/app/nexora/target-intake/latest.json'),
            'prerequisite_remediation' => $jsonReport($root.'/storage/app/nexora/target-remediation/latest.json'),
            'lock_refresh' => $jsonReport($root.'/storage/app/nexora/dependency-intake/lock-refresh.json'),
        ],
        'privacy' => [
            'env_dump_included' => false,
            'secret_shaped_values_redacted' => true,
            'project_and_home_paths_redacted' => true,
            'step_log_limit_bytes' => 131072,
        ],
    ];
}
