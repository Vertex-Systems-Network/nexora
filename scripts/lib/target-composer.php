<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2).'/bootstrap/nexora-process-environment.php';

/** @return array{available:bool,version:?string,raw:?string,source:?string,path:?string,command:list<string>,candidates:list<string>} */
function nexoraLocateTargetComposer(string $root): array
{
    $env = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
    $candidates = [];
    $seen = [];

    $add = static function (string $path, string $source) use (&$candidates, &$seen): void {
        if (! is_file($path)) return;
        $normalized = str_replace('\\', '/', $path);
        $key = strtolower($normalized);
        if (isset($seen[$key])) return;
        $seen[$key] = true;
        $name = strtolower(basename($path));
        $command = $name === 'composer.phar' ? [PHP_BINARY, $path] : [$path];
        $candidates[] = ['path' => $path, 'source' => $source, 'command' => $command];
    };

    // An explicit offline handoff is preferred after PATH when the operator provides one.
    // The bootstrap command validates/copies it before normal discovery; this locator only
    // recognizes the canonical local copy so arbitrary external PHARs are never executed here.
    // PATH is preferred because it represents the operator's selected Composer.
    $pathCommand = ['composer', '--version', '--no-ansi'];
    $pathProbe = nexoraRunTargetCommand($pathCommand, $root, $env);
    if ($pathProbe['exit_code'] === 0) {
        $version = nexoraParseToolVersion($pathProbe['stdout'] !== '' ? $pathProbe['stdout'] : $pathProbe['stderr']);
        return [
            'available' => true,
            'version' => $version,
            'raw' => trim($pathProbe['stdout'] !== '' ? $pathProbe['stdout'] : $pathProbe['stderr']) ?: null,
            'source' => 'PATH',
            'path' => 'composer',
            'command' => ['composer'],
            'candidates' => [],
        ];
    }

    $localComposer = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'
        .DIRECTORY_SEPARATOR.'nexora'.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'composer'
        .DIRECTORY_SEPARATOR.'composer.phar';
    $add($localComposer, 'Nexora-local');

    foreach (NexoraBootstrapProcessEnvironment::laragonRoots($root) as $laragon) {
        $base = $laragon.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'composer';
        if (! is_dir($base)) continue;
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if (! $file->isFile()) continue;
                $name = strtolower($file->getFilename());
                if (in_array($name, ['composer', 'composer.bat', 'composer.cmd', 'composer.exe', 'composer.phar'], true)) {
                    $add($file->getPathname(), 'Laragon');
                }
            }
        } catch (Throwable) {
            // An unreadable optional Laragon tool directory must not become a fatal bootstrap error.
        }
    }

    usort($candidates, static function (array $a, array $b): int {
        $rank = static function (array $candidate): int {
            $name = strtolower(basename((string) $candidate['path']));
            return match ($name) {
                'composer.exe', 'composer.cmd', 'composer.bat', 'composer' => 0,
                'composer.phar' => 1,
                default => 2,
            };
        };
        return ($rank($a) <=> $rank($b)) ?: strcmp(strtolower((string) $a['path']), strtolower((string) $b['path']));
    });

    foreach ($candidates as $candidate) {
        $probe = nexoraRunTargetCommand(array_merge($candidate['command'], ['--version', '--no-ansi']), $root, $env);
        if ($probe['exit_code'] !== 0) continue;
        $raw = trim($probe['stdout'] !== '' ? $probe['stdout'] : $probe['stderr']);
        return [
            'available' => true,
            'version' => nexoraParseToolVersion($raw),
            'raw' => $raw !== '' ? $raw : null,
            'source' => (string) $candidate['source'],
            'path' => str_replace('\\', '/', (string) $candidate['path']),
            'command' => array_values(array_map('strval', $candidate['command'])),
            'candidates' => array_values(array_map(static fn (array $row): string => str_replace('\\', '/', (string) $row['path']), $candidates)),
        ];
    }

    return [
        'available' => false,
        'version' => null,
        'raw' => null,
        'source' => null,
        'path' => null,
        'command' => [],
        'candidates' => array_values(array_map(static fn (array $row): string => str_replace('\\', '/', (string) $row['path']), $candidates)),
    ];
}


/**
 * Resolve a Windows command to an executable-only argv where possible.
 *
 * Windows npm/npx launchers are .cmd files. proc_open() with bypass_shell=true
 * cannot execute them directly. For npm/npx we resolve the Node executable and
 * invoke the corresponding JS CLI directly, keeping argument boundaries intact
 * and avoiding cmd.exe quoting/injection ambiguity.
 *
 * @param list<string> $command
 * @return list<string>
 */
function nexoraNormalizeTargetCommand(array $command, string $root, ?array $env = null): array
{
    $command = array_values(array_map('strval', $command));
    if ($command === [] || PHP_OS_FAMILY !== 'Windows') return $command;

    $env ??= NexoraBootstrapProcessEnvironment::build($root, $_ENV);
    $program = strtolower(basename(str_replace('\\', '/', $command[0])));
    $npmLike = match ($program) {
        'npm', 'npm.cmd', 'npm.bat' => ['launcher' => 'npm', 'cli' => 'npm-cli.js'],
        'npx', 'npx.cmd', 'npx.bat' => ['launcher' => 'npx', 'cli' => 'npx-cli.js'],
        default => null,
    };
    if ($npmLike === null) return $command;

    $findExecutable = static function (string $name) use ($root, $env): ?string {
        $proc = @proc_open(
            ['where.exe', $name],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
            $env,
            ['bypass_shell' => true],
        );
        if (! is_resource($proc)) return null;
        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]); fclose($pipes[1]);
        stream_get_contents($pipes[2]); fclose($pipes[2]);
        if (proc_close($proc) !== 0) return null;
        foreach (preg_split('/\\R+/', trim($stdout)) ?: [] as $candidate) {
            $candidate = trim($candidate, " \t\r\n\"'");
            if ($candidate !== '' && is_file($candidate)) return $candidate;
        }
        return null;
    };

    $node = $findExecutable('node.exe') ?? $findExecutable('node');
    $launcher = null;
    $explicit = $command[0];
    if (is_file($explicit)) {
        $launcher = $explicit;
    } else {
        $launcher = $findExecutable($npmLike['launcher'].'.cmd')
            ?? $findExecutable($npmLike['launcher']);
    }
    if (! is_string($node) || ! is_string($launcher)) return $command;

    $base = dirname($launcher);
    $cliCandidates = [
        $base.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'npm'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.$npmLike['cli'],
        dirname($base).DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'npm'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.$npmLike['cli'],
    ];
    $cli = null;
    foreach ($cliCandidates as $candidate) {
        if (is_file($candidate)) {
            $cli = $candidate;
            break;
        }
    }
    if ($cli === null) return $command;

    return array_merge([$node, $cli], array_slice($command, 1));
}

/** @return array{exit_code:int,stdout:string,stderr:string} */
function nexoraRunTargetCommand(array $command, string $root, ?array $env = null): array
{
    if ($command === []) return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'empty command'];
    $env ??= NexoraBootstrapProcessEnvironment::build($root, $_ENV);
    $command = nexoraNormalizeTargetCommand($command, $root, $env);
    $proc = @proc_open(
        array_values(array_map('strval', $command)),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
        $env,
        ['bypass_shell' => true]
    );
    if (! is_resource($proc)) return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'unable to start process'];
    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]); fclose($pipes[2]);
    return ['exit_code' => proc_close($proc), 'stdout' => trim($stdout), 'stderr' => trim($stderr)];
}

function nexoraParseToolVersion(string $raw): ?string
{
    return preg_match('/(\d+\.\d+(?:\.\d+)?)/', $raw, $match) === 1 ? $match[1] : null;
}
