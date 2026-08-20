<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/target-composer.php';
require_once $root.'/scripts/lib/source-attestation.php';

$platform = require $root.'/config/nexora.php';
$version = (string) ($platform['version'] ?? 'unknown');
$dir = $root.'/storage/app/nexora/target-remediation';
$applyExtensions = in_array('--apply-extensions', $argv, true);
$jsonOnly = in_array('--json', $argv, true);
$write = ! in_array('--no-write', $argv, true);

$ini = php_ini_loaded_file() ?: null;
$extensionDir = (string) (ini_get('extension_dir') ?: '');
$laragonRoots = NexoraBootstrapProcessEnvironment::laragonRoots($root);
$laragonDetected = $laragonRoots !== []
    || stripos(str_replace('\\', '/', PHP_BINARY), '/laragon/') !== false
    || stripos(str_replace('\\', '/', $root), '/laragon/') !== false;

$manifest = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$requiredExtensions = [];
foreach ((array) ($manifest['require'] ?? []) as $name => $constraint) {
    if (str_starts_with((string) $name, 'ext-')) {
        $requiredExtensions[] = substr((string) $name, 4);
    }
}
sort($requiredExtensions);

$resolveExtensionDir = static function (string $configured): ?string {
    $configured = trim($configured, " \t\n\r\0\x0B\"'");
    if ($configured === '') return null;
    if (preg_match('/^[A-Za-z]:[\\\\\/]/', $configured) === 1 || str_starts_with($configured, '/') || str_starts_with($configured, '\\\\')) {
        return is_dir($configured) ? $configured : null;
    }
    $candidate = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configured);
    return is_dir($candidate) ? $candidate : null;
};
$resolvedExtensionDir = $resolveExtensionDir($extensionDir);

$extensionRows = [];
foreach ($requiredExtensions as $extension) {
    $dll = $resolvedExtensionDir !== null ? $resolvedExtensionDir.DIRECTORY_SEPARATOR.'php_'.$extension.'.dll' : null;
    $extensionRows[] = [
        'name' => $extension,
        'loaded' => extension_loaded($extension),
        'dll' => $dll,
        'dll_present' => $dll !== null && is_file($dll),
    ];
}
$missingRows = array_values(array_filter($extensionRows, static fn (array $row): bool => ! $row['loaded']));

$composerTool = nexoraLocateTargetComposer($root);
$composerCandidates = (array) ($composerTool['candidates'] ?? []);
$composerAvailable = (bool) ($composerTool['available'] ?? false);
$composerVersion = is_string($composerTool['version'] ?? null) ? $composerTool['version'] : null;
$actions = [];
$changes = [];
$status = 'ready';
$backup = null;
$restartTicket = null;

if ($missingRows !== []) {
    $status = 'blocked';
    if ($ini === null || ! is_file($ini)) {
        $actions[] = 'Active php.ini is not a readable file; select the intended Laragon PHP build and rerun.';
    } else {
        foreach ($missingRows as $row) {
            if ($row['dll_present']) {
                $actions[] = "Enable extension={$row['name']} in {$ini}; matching DLL is present at {$row['dll']}.";
            } else {
                $actions[] = "PHP extension {$row['name']} is missing and no matching DLL was found in the active extension_dir; select/install a Laragon PHP build that includes it.";
            }
        }
    }
}

if (! $composerAvailable) {
    $status = 'blocked';
    if ($composerCandidates !== []) {
        $actions[] = 'Trusted Laragon Composer candidate(s) were found but none executed successfully. Review the generated session helper/candidate and the PHP extension prerequisites; no global PATH mutation is performed by Nexora.';
    } else {
        $actions[] = 'Composer 2.x was not found in PATH or Laragon bin/composer. Install/enable trusted Composer manually, then rerun target intake.';
    }
}

if ($applyExtensions) {
    if (PHP_OS_FAMILY !== 'Windows' || ! $laragonDetected) {
        fwrite(STDERR, "[Nexora Target Remediation] --apply-extensions is restricted to an explicitly detected Windows/Laragon target.\n");
        exit(2);
    }
    if ($ini === null || ! is_file($ini) || ! is_readable($ini) || ! is_writable($ini)) {
        fwrite(STDERR, "[Nexora Target Remediation] Active php.ini must be readable and writable for --apply-extensions.\n");
        exit(2);
    }
    $eligible = array_values(array_filter($missingRows, static fn (array $row): bool => $row['dll_present']));
    if ($eligible === []) {
        fwrite(STDERR, "[Nexora Target Remediation] No missing extension has a matching DLL in the active extension_dir; nothing can be enabled safely.\n");
        exit(2);
    }

    $original = file_get_contents($ini);
    if (! is_string($original)) throw new RuntimeException('Unable to read active php.ini.');
    $originalHash = hash('sha256', $original);
    $eol = str_contains($original, "\r\n") ? "\r\n" : "\n";
    $updated = $original;

    foreach ($eligible as $row) {
        $name = preg_quote((string) $row['name'], '/');
        $dllName = preg_quote('php_'.$row['name'].'.dll', '/');
        $patterns = [
            '/^[ \t]*;[ \t]*extension[ \t]*=[ \t]*(?:"|\')?'.$name.'(?:"|\')?[ \t]*$/mi',
            '/^[ \t]*;[ \t]*extension[ \t]*=[ \t]*(?:"|\')?'.$dllName.'(?:"|\')?[ \t]*$/mi',
        ];
        $replacement = 'extension='.$row['name'];
        $changed = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $updated) === 1) {
                $updated = (string) preg_replace($pattern, $replacement, $updated, 1);
                $changed = true;
                break;
            }
        }
        if (! $changed) {
            if ($updated !== '' && ! str_ends_with($updated, "\n") && ! str_ends_with($updated, "\r")) $updated .= $eol;
            $updated .= $replacement.$eol;
        }
        $changes[] = ['extension' => $row['name'], 'directive' => $replacement, 'dll' => $row['dll']];
    }

    if ($updated !== $original) {
        $backup = $ini.'.nexora-'.gmdate('YmdHis').'.bak';
        if (! copy($ini, $backup)) throw new RuntimeException('Unable to create php.ini backup before remediation.');
        if (! hash_equals($originalHash, (string) hash_file('sha256', $backup))) {
            @unlink($backup);
            throw new RuntimeException('php.ini backup checksum verification failed.');
        }
        $tmp = $ini.'.nexora-'.bin2hex(random_bytes(4)).'.tmp';
        $handle = fopen($tmp, 'xb');
        if ($handle === false) throw new RuntimeException('Unable to create php.ini staging file.');
        try {
            $length = strlen($updated);
            $written = 0;
            while ($written < $length) {
                $n = fwrite($handle, substr($updated, $written));
                if ($n === false || $n === 0) throw new RuntimeException('Unable to complete php.ini staging write.');
                $written += $n;
            }
            fflush($handle);
            if (function_exists('fsync')) @fsync($handle);
        } finally {
            fclose($handle);
        }
        if (! copy($tmp, $ini)) {
            @unlink($tmp);
            throw new RuntimeException("Unable to publish php.ini remediation; verified backup remains at {$backup}.");
        }
        @unlink($tmp);
        if (! hash_equals(hash('sha256', $updated), (string) hash_file('sha256', $ini))) {
            @copy($backup, $ini);
            throw new RuntimeException('Published php.ini checksum mismatch; backup restoration was attempted.');
        }
        $status = 'restart_required';
        $source = nexoraComputeSourceAttestation($root);
        $restartTicket = [
            'schema' => 1,
            'status' => 'restart-required',
            'platform_version' => $version,
            'source_tree_sha256' => $source['tree_sha256'],
            'issued_at' => gmdate(DATE_ATOM),
            'php_binary' => PHP_BINARY,
            'php_ini' => $ini,
            'php_ini_sha256_before' => $originalHash,
            'php_ini_sha256_after' => hash_file('sha256', $ini) ?: null,
            'required_extensions' => $requiredExtensions,
            'enabled_extensions' => array_values(array_map(static fn (array $row): string => (string) $row['name'], $eligible)),
            'backup_sha256' => $backup !== null && is_file($backup) ? (hash_file('sha256', $backup) ?: null) : null,
        ];
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) throw new RuntimeException('Unable to create target-remediation evidence directory.');
        file_put_contents($dir.'/restart-ticket.json', json_encode($restartTicket, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
        @unlink($dir.'/restart-verified.json');
        $actions = [
            'Restart Laragon completely and open a fresh terminal so PHP reloads the updated php.ini.',
            'Run scripts\\target-prerequisite-restart-verify.bat (or the master runner); only continue when the restart ticket verifies PASS.',
        ];
    }
}

if ($write && ! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
    throw new RuntimeException('Unable to create target-remediation evidence directory.');
}

$sessionHelper = null;
$composerSessionCommand = null;
if ($write && PHP_OS_FAMILY === 'Windows' && ! $composerAvailable && $composerCandidates !== []) {
    usort($composerCandidates, static function (string $a, string $b): int {
        $rank = static function (string $path): int {
            $name = strtolower(basename($path));
            return match ($name) {
                'composer.bat', 'composer.cmd', 'composer.exe', 'composer' => 0,
                'composer.phar' => 1,
                default => 2,
            };
        };
        return ($rank($a) <=> $rank($b)) ?: strcmp(strtolower($a), strtolower($b));
    });
    $candidate = $composerCandidates[0];
    $candidateName = strtolower(basename($candidate));
    $candidateDir = dirname($candidate);
    if ($candidateName === 'composer.phar') {
        $shimDir = $dir.'/bin';
        if (! is_dir($shimDir) && ! mkdir($shimDir, 0775, true) && ! is_dir($shimDir)) throw new RuntimeException('Unable to create Composer session shim directory.');
        $shim = $shimDir.'/composer.cmd';
        file_put_contents($shim, '@echo off'."\r\n".'"'.PHP_BINARY.'" "'.$candidate.'" %*'."\r\n");
        $candidateDir = $shimDir;
        $composerSessionCommand = 'storage/app/nexora/target-remediation/bin/composer.cmd';
    } else {
        $composerSessionCommand = str_replace('\\', '/', $candidate);
    }
    $sessionHelper = $dir.'/nexora-target-env.cmd';
    $lines = [
        '@echo off',
        'rem Nexora session-only target tool helper. Does not change the machine/user PATH.',
        'set "PATH='.$candidateDir.';%PATH%"',
        'echo Nexora target session PATH prepared for Composer candidate:',
        'echo '.$candidate,
    ];
    file_put_contents($sessionHelper, implode("\r\n", $lines)."\r\n");
    $actions[] = 'In the same terminal run: call storage\\app\\nexora\\target-remediation\\nexora-target-env.cmd, then composer --version. This changes PATH for that terminal only.';
}

$payload = [
    'schema' => 1,
    'platform_version' => $version,
    'status' => $status,
    'checked_at' => gmdate(DATE_ATOM),
    'os_family' => PHP_OS_FAMILY,
    'laragon_detected' => $laragonDetected,
    'php_binary' => PHP_BINARY,
    'php_ini' => $ini,
    'extension_dir' => $extensionDir !== '' ? $extensionDir : null,
    'resolved_extension_dir' => $resolvedExtensionDir,
    'extensions' => $extensionRows,
    'composer_available' => $composerAvailable,
    'composer_version' => $composerVersion,
    'composer_candidates' => array_map(static fn (string $path): string => str_replace('\\', '/', $path), $composerCandidates),
    'changes' => $changes,
    'backup' => $backup,
    'session_helper' => $sessionHelper !== null ? 'storage/app/nexora/target-remediation/'.basename($sessionHelper) : null,
    'composer_session_command' => $composerSessionCommand,
    'restart_ticket' => $restartTicket !== null ? 'storage/app/nexora/target-remediation/restart-ticket.json' : null,
    'actions' => $actions,
];

if ($write) {
    file_put_contents($dir.'/latest.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    $md = "# Nexora {$version} target prerequisite remediation\n\n";
    $md .= "Status: **".strtoupper($status)."**\n\n";
    $md .= "- PHP binary: `".PHP_BINARY."`\n- php.ini: `".($ini ?? 'not loaded')."`\n- extension_dir: `".($resolvedExtensionDir ?? $extensionDir ?: 'unknown')."`\n- Composer on PATH: **".($composerAvailable ? 'yes' : 'no')."**\n\n";
    $md .= "## Required PHP extensions\n\n| Extension | Loaded | DLL present |\n|---|---:|---:|\n";
    foreach ($extensionRows as $row) $md .= '| '.$row['name'].' | '.($row['loaded'] ? 'yes' : 'no').' | '.($row['dll_present'] ? 'yes' : 'no')." |\n";
    if ($composerCandidates !== []) {
        $md .= "\n## Composer candidates\n";
        foreach ($composerCandidates as $candidate) $md .= '- `'.str_replace('\\', '/', $candidate)."`\n";
    }
    if ($changes !== []) {
        $md .= "\n## Applied php.ini changes\n";
        foreach ($changes as $change) $md .= '- `'.$change['directive'].'`\n';
        if ($backup !== null) $md .= '- Backup: `'.str_replace('\\', '/', $backup)."`\n";
    }
    $md .= "\n## Next actions\n";
    foreach ($actions as $action) $md .= '- '.$action."\n";
    file_put_contents($dir.'/latest.md', $md);
}

if ($jsonOnly) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    fwrite(STDOUT, "[Nexora Target Remediation] ".strtoupper($status)." — {$version}\n");
    foreach ($extensionRows as $row) {
        fwrite(STDOUT, sprintf("[%s] PHP extension %s — DLL %s\n", $row['loaded'] ? 'PASS' : 'MISS', $row['name'], $row['dll_present'] ? 'present' : 'not found'));
    }
    fwrite(STDOUT, '[ '.($composerAvailable ? 'PASS' : 'MISS').' ] Composer'.($composerVersion ? ' '.$composerVersion : '')."\n");
    foreach ($actions as $action) fwrite(STDOUT, " - {$action}\n");
    if ($write) fwrite(STDOUT, "Evidence: storage/app/nexora/target-remediation/latest.md\n");
}

exit($status === 'ready' ? 0 : ($status === 'restart_required' ? 2 : 1));
