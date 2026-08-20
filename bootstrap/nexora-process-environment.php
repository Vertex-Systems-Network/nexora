<?php

declare(strict_types=1);

/**
 * Framework-independent child-process environment for Nexora bootstrap tasks.
 *
 * This file is intentionally safe to load before Composer/Laravel. It preserves
 * real host variables when available and supplies isolated writable fallbacks
 * for web-server processes that do not inherit a normal login profile.
 */
final class NexoraBootstrapProcessEnvironment
{
    /** @return list<string> */
    public static function laragonRoots(string $root): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $candidates = [];
        $normalized = str_replace('\\', '/', $root);
        if (preg_match('#^(.+?)/www(?:/|$)#i', $normalized, $match) === 1) {
            $candidates[] = str_replace('/', DIRECTORY_SEPARATOR, $match[1]);
        }

        foreach (['LARAGON_ROOT', 'LARAGON_HOME'] as $key) {
            $value = trim((string) getenv($key));
            if ($value !== '') {
                $candidates[] = rtrim($value, "\\/");
            }
        }

        foreach (['C:\\laragon', 'D:\\laragon', 'E:\\laragon'] as $common) {
            $candidates[] = $common;
        }

        $unique = [];
        $seen = [];
        foreach ($candidates as $candidate) {
            $key = strtolower(str_replace('\\', '/', $candidate));
            if (is_dir($candidate) && ! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $candidate;
            }
        }

        return $unique;
    }

    /** @return array<string,string> */
    public static function build(string $root, array $extra = []): array
    {
        $env = getenv();
        if (! is_array($env)) {
            $env = [];
        }

        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && is_scalar($value) && ! array_key_exists($key, $env)) {
                $env[$key] = (string) $value;
            }
        }

        $tools = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'.DIRECTORY_SEPARATOR.'tools';
        $privateHome = $tools.DIRECTORY_SEPARATOR.'home';
        $composerHome = $tools.DIRECTORY_SEPARATOR.'composer-home';
        $composerCache = $tools.DIRECTORY_SEPARATOR.'composer-cache';
        $npmCache = $tools.DIRECTORY_SEPARATOR.'npm-cache';
        $privateTemp = $tools.DIRECTORY_SEPARATOR.'tmp';

        foreach ([$tools, $privateHome, $composerHome, $composerCache, $npmCache, $privateTemp] as $directory) {
            if (! is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }

        $firstNonEmpty = static function (array $keys) use (&$env): ?string {
            foreach ($keys as $key) {
                $value = trim((string) ($env[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
            return null;
        };

        if (PHP_OS_FAMILY === 'Windows') {
            $userProfile = $firstNonEmpty(['USERPROFILE']);
            if ($userProfile === null) {
                $drive = trim((string) ($env['HOMEDRIVE'] ?? ''));
                $path = trim((string) ($env['HOMEPATH'] ?? ''));
                if ($drive !== '' && $path !== '') {
                    $userProfile = $drive.$path;
                }
            }

            $appData = $firstNonEmpty(['APPDATA']);
            if ($appData === null && $userProfile !== null) {
                $candidate = rtrim($userProfile, "\\/").DIRECTORY_SEPARATOR.'AppData'.DIRECTORY_SEPARATOR.'Roaming';
                if (is_dir($candidate)) {
                    $appData = $candidate;
                }
            }

            $env['USERPROFILE'] = $userProfile ?? $privateHome;
            if ($appData !== null && is_dir($appData)) {
                $env['APPDATA'] = $appData;
            } elseif (isset($env['APPDATA'])) {
                unset($env['APPDATA']);
            }

            $explicitComposerHome = trim((string) ($env['COMPOSER_HOME'] ?? ''));
            if ($explicitComposerHome !== '') {
                if (! self::ensureWritableDirectory($explicitComposerHome)) {
                    unset($env['COMPOSER_HOME']);
                    $explicitComposerHome = '';
                }
            }

            // Prefer the real Windows Composer profile when Apache inherited a
            // valid APPDATA value. Only force Nexora's private COMPOSER_HOME when
            // neither usable COMPOSER_HOME nor APPDATA exists.
            if ($explicitComposerHome === '' && trim((string) ($env['APPDATA'] ?? '')) === '') {
                $env['COMPOSER_HOME'] = $composerHome;
            }

            if (trim((string) ($env['HOME'] ?? '')) === '') {
                $env['HOME'] = $env['USERPROFILE'];
            }
            if (trim((string) ($env['SystemRoot'] ?? '')) === '') {
                $systemRoot = trim((string) getenv('SystemRoot'));
                $env['SystemRoot'] = $systemRoot !== '' ? $systemRoot : 'C:\\Windows';
            }
            if (trim((string) ($env['ComSpec'] ?? '')) === '') {
                $candidate = rtrim((string) $env['SystemRoot'], "\\/").DIRECTORY_SEPARATOR.'System32'.DIRECTORY_SEPARATOR.'cmd.exe';
                if (is_file($candidate)) {
                    $env['ComSpec'] = $candidate;
                }
            }
        } else {
            $home = trim((string) ($env['HOME'] ?? ''));
            if ($home === '' || ! is_dir($home)) {
                $env['HOME'] = $privateHome;
            }
            $explicitComposerHome = trim((string) ($env['COMPOSER_HOME'] ?? ''));
            if ($explicitComposerHome !== '' && ! self::ensureWritableDirectory($explicitComposerHome)) {
                unset($env['COMPOSER_HOME']);
            }
        }

        if (trim((string) ($env['COMPOSER_CACHE_DIR'] ?? '')) === '') {
            $env['COMPOSER_CACHE_DIR'] = $composerCache;
        }
        if (trim((string) ($env['NPM_CONFIG_CACHE'] ?? '')) === '') {
            $env['NPM_CONFIG_CACHE'] = $npmCache;
        }
        if (trim((string) ($env['TEMP'] ?? '')) === '') {
            $env['TEMP'] = $privateTemp;
        }
        if (trim((string) ($env['TMP'] ?? '')) === '') {
            $env['TMP'] = $privateTemp;
        }

        $env['COMPOSER_NO_INTERACTION'] = '1';
        $env['NEXORA_DEPLOYMENT'] = '1';

        $pathEntries = [];
        $existingPath = trim((string) ($env['PATH'] ?? $env['Path'] ?? ''));
        if ($existingPath !== '') {
            $pathEntries = array_values(array_filter(explode(PATH_SEPARATOR, $existingPath)));
        }
        if (defined('PHP_BINARY') && is_file(PHP_BINARY)) {
            $pathEntries[] = dirname(PHP_BINARY);
        }
        foreach (self::laragonRoots($root) as $laragon) {
            foreach (['php', 'composer', 'nodejs'] as $tool) {
                foreach (glob($laragon.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.$tool.DIRECTORY_SEPARATOR.'*') ?: [] as $dir) {
                    if (is_dir($dir)) {
                        $pathEntries[] = $dir;
                    }
                }
            }
        }

        $pathEntries = array_values(array_unique(array_filter($pathEntries, static fn (string $entry): bool => $entry !== '' && is_dir($entry))));
        if ($pathEntries !== []) {
            $env['PATH'] = implode(PATH_SEPARATOR, $pathEntries);
            if (PHP_OS_FAMILY === 'Windows') {
                $env['Path'] = $env['PATH'];
            }
        }

        foreach ($extra as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $env[$key] = (string) $value;
            }
        }

        $normalized = [];
        foreach ($env as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $normalized[$key] = (string) $value;
            }
        }

        return $normalized;
    }

    private static function ensureWritableDirectory(string $directory): bool
    {
        if ($directory === '') {
            return false;
        }
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return false;
        }
        return is_writable($directory);
    }

    /** @return array{composer_home:string,composer_home_source:string,appdata:?string,home:string,npm_cache:string,composer_home_writable:bool} */
    public static function summary(string $root): array
    {
        $inheritedComposerHome = trim((string) getenv('COMPOSER_HOME'));
        $inheritedAppData = trim((string) getenv('APPDATA'));
        $env = self::build($root);

        $composerHome = trim((string) ($env['COMPOSER_HOME'] ?? ''));
        $source = 'Nexora private fallback';
        if ($composerHome !== '') {
            if ($inheritedComposerHome !== '' && $composerHome === $inheritedComposerHome) {
                $source = 'OS / web COMPOSER_HOME';
            }
        } elseif (PHP_OS_FAMILY === 'Windows' && trim((string) ($env['APPDATA'] ?? '')) !== '') {
            $composerHome = rtrim((string) $env['APPDATA'], "\\/").DIRECTORY_SEPARATOR.'Composer';
            $source = $inheritedAppData !== '' ? 'OS / web APPDATA' : 'Windows user profile APPDATA';
        } else {
            $composerHome = rtrim((string) ($env['HOME'] ?? ''), "\\/").DIRECTORY_SEPARATOR.'.composer';
            $source = 'OS / web HOME';
        }

        $composerHomeWritable = is_dir($composerHome)
            ? is_writable($composerHome)
            : (is_dir(dirname($composerHome)) && is_writable(dirname($composerHome)));

        return [
            'composer_home' => $composerHome,
            'composer_home_source' => $source,
            'appdata' => isset($env['APPDATA']) && trim((string) $env['APPDATA']) !== '' ? (string) $env['APPDATA'] : null,
            'home' => (string) ($env['HOME'] ?? $env['USERPROFILE'] ?? ''),
            'npm_cache' => (string) ($env['NPM_CONFIG_CACHE'] ?? ''),
            'composer_home_writable' => $composerHomeWritable,
        ];
    }
}
