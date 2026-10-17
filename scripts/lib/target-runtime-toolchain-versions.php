<?php

declare(strict_types=1);

/**
 * Record a normalized executable version or null on failure. Missing versions
 * must not authorize reuse. The provided runner should use argument-vector
 * process execution (the shared target command runner resolves Windows shims).
 *
 * @param callable(list<string>):array{exit_code:int,stdout:string,stderr:string} $run
 * @return array{composer_version:?string,node_version:?string,npm_version:?string}
 */
function nexoraTargetResumeToolchainVersions(callable $run): array
{
    $commands = [
        'composer_version' => ['composer', '--version', '--no-ansi'],
        'node_version' => ['node', '--version'],
        'npm_version' => ['npm', '--version'],
    ];
    $versions = [];
    foreach ($commands as $name => $command) {
        try {
            $result = $run($command);
            if (!is_array($result) || ($result['exit_code'] ?? null) !== 0) {
                $versions[$name] = null;
                continue;
            }
            $stdout = (string)($result['stdout'] ?? '');
            $stderr = (string)($result['stderr'] ?? '');
            $raw = trim($stdout !== '' ? $stdout : $stderr);
            $versions[$name] = preg_match('/(?<![0-9])(\d+\.\d+(?:\.\d+)?)(?![0-9])/', $raw, $match) === 1
                ? $match[1] : null;
        } catch (Throwable) {
            $versions[$name] = null;
        }
    }
    return $versions;
}
