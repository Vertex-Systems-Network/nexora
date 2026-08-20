<?php

declare(strict_types=1);

if (! function_exists('nexoraAnalyzeLaravelRuntimeContracts')) {
    /**
     * Dependency-free source analysis for Laravel runtime entry-point contracts.
     *
     * @return array{ok:bool,errors:list<string>,checks:array<string,mixed>}
     */
    function nexoraAnalyzeLaravelRuntimeContracts(string $root): array
    {
        $errors = [];
        $checks = [
            'middleware_files' => 0,
            'middleware_entries' => 0,
            'route_middleware_aliases' => 0,
            'scheduled_commands' => 0,
            'scheduled_callbacks' => 0,
            'queue_jobs' => 0,
            'service_providers' => 0,
        ];

        $read = static function (string $path) use (&$errors): string {
            if (! is_file($path)) {
                $errors[] = 'missing-file: '.str_replace('\\', '/', $path);
                return '';
            }
            return (string) file_get_contents($path);
        };

        $extractParams = static function (string $source, string $method): ?string {
            if (! preg_match('/function\s+'.preg_quote($method, '/').'\s*\(/', $source, $match, PREG_OFFSET_CAPTURE)) {
                return null;
            }
            $start = (int) $match[0][1] + strlen((string) $match[0][0]) - 1;
            $depth = 0;
            $quote = null;
            $escaped = false;
            $length = strlen($source);
            for ($i = $start; $i < $length; $i++) {
                $char = $source[$i];
                if ($quote !== null) {
                    if ($escaped) {
                        $escaped = false;
                        continue;
                    }
                    if ($char === '\\') {
                        $escaped = true;
                        continue;
                    }
                    if ($char === $quote) $quote = null;
                    continue;
                }
                if ($char === "'" || $char === '"') {
                    $quote = $char;
                    continue;
                }
                if ($char === '(') {
                    $depth++;
                    continue;
                }
                if ($char === ')') {
                    $depth--;
                    if ($depth === 0) return substr($source, $start + 1, $i - $start - 1);
                }
            }
            return null;
        };

        $splitParams = static function (?string $params): array {
            if ($params === null || trim($params) === '') return [];
            $result = [];
            $buffer = '';
            $depth = 0;
            $quote = null;
            $escaped = false;
            $length = strlen($params);
            for ($i = 0; $i < $length; $i++) {
                $char = $params[$i];
                if ($quote !== null) {
                    $buffer .= $char;
                    if ($escaped) {
                        $escaped = false;
                        continue;
                    }
                    if ($char === '\\') {
                        $escaped = true;
                        continue;
                    }
                    if ($char === $quote) $quote = null;
                    continue;
                }
                if ($char === "'" || $char === '"') {
                    $quote = $char;
                    $buffer .= $char;
                    continue;
                }
                if (in_array($char, ['(', '[', '{'], true)) $depth++;
                if (in_array($char, [')', ']', '}'], true)) $depth--;
                if ($char === ',' && $depth === 0) {
                    $result[] = trim($buffer);
                    $buffer = '';
                    continue;
                }
                $buffer .= $char;
            }
            if (trim($buffer) !== '') $result[] = trim($buffer);
            return $result;
        };

        $typeOf = static function (string $param): string {
            $param = preg_replace('/#\[[\s\S]*?\]\]\s*/', '', $param) ?? $param;
            if (! preg_match('/^\s*(?:public|protected|private|readonly|static|\s)*\s*([^$=]+?)\s*&?\s*\.\.\.?\s*\$[A-Za-z_][A-Za-z0-9_]*/', $param, $m)) {
                if (! preg_match('/^\s*([^$=]+?)\s*&?\s*\$[A-Za-z_][A-Za-z0-9_]*/', $param, $m)) return '';
            }
            return trim((string) ($m[1] ?? ''));
        };

        $isBuiltinType = static function (string $type): bool {
            $type = strtolower(trim($type, " ?\\"));
            if ($type === '') return true;
            foreach (explode('|', $type) as $part) {
                $part = trim($part, " ?\\");
                if (! in_array($part, ['string','int','integer','float','bool','boolean','array','mixed','null'], true)) return false;
            }
            return true;
        };

        // Middleware contract: Request + Closure first, service dependencies in constructor,
        // only route-supplied scalar parameters may follow $next.
        foreach (glob($root.'/app/Http/Middleware/*.php') ?: [] as $file) {
            $checks['middleware_files']++;
            $source = $read($file);
            $base = basename($file);
            $handle = $extractParams($source, 'handle');
            if ($handle === null) {
                if (str_contains($source, 'extends Middleware')) continue; // Inertia base middleware supplies handle().
                $errors[] = "middleware.missing-handle: {$base}";
                continue;
            }
            $checks['middleware_entries']++;
            $params = $splitParams($handle);
            if (count($params) < 2) {
                $errors[] = "middleware.handle-arity: {$base} requires Request and Closure";
                continue;
            }
            $firstType = ltrim($typeOf($params[0]), '?\\');
            $secondType = ltrim($typeOf($params[1]), '?\\');
            if (! str_ends_with($firstType, 'Request')) $errors[] = "middleware.first-param: {$base} must receive Request first";
            if (! str_ends_with($secondType, 'Closure')) $errors[] = "middleware.second-param: {$base} must receive Closure second";
            foreach (array_slice($params, 2) as $index => $param) {
                $type = $typeOf($param);
                if (! $isBuiltinType($type)) {
                    $errors[] = "middleware.container-param-after-next: {$base} parameter ".($index + 3)." [{$type}] must be constructor-injected; only scalar route parameters may follow Closure";
                }
            }
        }

        // Bootstrap middleware declarations and custom aliases must resolve to local middleware classes.
        $bootstrap = $read($root.'/bootstrap/app.php');
        $imports = [];
        if (preg_match_all('/^use\s+(App\\\\Http\\\\Middleware\\\\([A-Za-z0-9_]+));/m', $bootstrap, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) $imports[$match[2]] = $match[1];
        }
        $declaredMiddleware = [];
        if (preg_match('/\$middleware->web\(append:\s*\[(.*?)\]\);/s', $bootstrap, $match)) {
            preg_match_all('/([A-Za-z0-9_]+)::class/', $match[1], $classes);
            foreach ($classes[1] ?? [] as $class) $declaredMiddleware[] = $class;
        }
        $customAliases = [];
        if (preg_match('/\$middleware->alias\(\[(.*?)\]\);/s', $bootstrap, $match)) {
            if (preg_match_all('/[\'\"]([^\'\"]+)[\'\"]\s*=>\s*([A-Za-z0-9_]+)::class/', $match[1], $aliases, PREG_SET_ORDER)) {
                foreach ($aliases as $alias) {
                    $customAliases[$alias[1]] = $alias[2];
                    $declaredMiddleware[] = $alias[2];
                }
            }
        }
        $checks['route_middleware_aliases'] = count($customAliases);
        foreach (array_values(array_unique($declaredMiddleware)) as $class) {
            $fqcn = $imports[$class] ?? null;
            if ($fqcn === null) {
                $errors[] = "middleware.bootstrap-import-missing: {$class}";
                continue;
            }
            $relative = 'app/'.str_replace('\\', '/', substr($fqcn, 4)).'.php';
            if (! is_file($root.'/'.$relative)) $errors[] = "middleware.bootstrap-class-missing: {$class} ({$relative})";
        }

        $routes = $read($root.'/routes/web.php');
        $builtInAliases = ['auth','auth.basic','auth.session','cache.headers','can','guest','password.confirm','precognitive','signed','subscribed','throttle','verified','web'];
        if (preg_match_all('/->middleware\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/', $routes, $matches)) {
            foreach ($matches[1] ?? [] as $middleware) {
                $alias = explode(':', $middleware, 2)[0];
                if (! in_array($alias, $builtInAliases, true) && ! array_key_exists($alias, $customAliases)) {
                    $errors[] = "route.middleware-alias-missing: {$alias}";
                }
            }
        }

        // Scheduler callbacks must be named, unique and overlap-safe; scheduled commands must exist.
        $console = $read($root.'/routes/console.php');
        $registeredCommands = [];
        if (preg_match_all('/Artisan::command\(\s*[\'\"]([^\'\"]+)[\'\"]/', $console, $matches)) {
            foreach ($matches[1] ?? [] as $signature) $registeredCommands[explode(' ', trim($signature), 2)[0]] = true;
        }
        foreach (glob($root.'/app/Console/Commands/**/*.php') ?: [] as $file) {
            $source = $read($file);
            if (preg_match('/protected\s+\$signature\s*=\s*[\'\"]([^\'\"]+)[\'\"]/', $source, $match)) {
                $registeredCommands[explode(' ', trim($match[1]), 2)[0]] = true;
            }
        }
        // Recursive fallback because glob ** is not portable on every PHP host.
        $commandRoot = $root.'/app/Console/Commands';
        if (is_dir($commandRoot)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($commandRoot, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') continue;
                $source = $read($file->getPathname());
                if (preg_match('/protected\s+\$signature\s*=\s*[\'\"]([^\'\"]+)[\'\"]/', $source, $match)) {
                    $registeredCommands[explode(' ', trim($match[1]), 2)[0]] = true;
                }
            }
        }

        $schedulePositions = [];
        if (preg_match_all('/Schedule::(?:command|call)\s*\(/', $console, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) $schedulePositions[] = (int) $match[1];
        }
        sort($schedulePositions);
        $callbackNames = [];
        foreach ($schedulePositions as $i => $position) {
            $end = $schedulePositions[$i + 1] ?? strlen($console);
            $snippet = substr($console, $position, $end - $position);
            if (str_starts_with($snippet, 'Schedule::command')) {
                if (! preg_match('/Schedule::command\(\s*[\'\"]([^\'\"]+)[\'\"]/', $snippet, $match)) continue;
                $checks['scheduled_commands']++;
                $command = trim($match[1]);
                $commandName = explode(' ', $command, 2)[0];
                if (! isset($registeredCommands[$commandName])) $errors[] = "scheduler.command-not-registered: {$command}";
                $nonLeaderInfrastructureHeartbeats = ['nexora:node:heartbeat', 'nexora:runtime:process-heartbeat scheduler'];
                if (! in_array($command, $nonLeaderInfrastructureHeartbeats, true) && ! str_contains($snippet, '->when($leaderCheck)')) {
                    $errors[] = "scheduler.command-not-leader-gated: {$command}";
                }
            } else {
                $checks['scheduled_callbacks']++;
                if (! preg_match('/->name\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/', $snippet, $match)) {
                    $errors[] = 'scheduler.callback-missing-name';
                    continue;
                }
                $name = $match[1];
                if (isset($callbackNames[$name])) $errors[] = "scheduler.callback-duplicate-name: {$name}";
                $callbackNames[$name] = true;
                if (str_contains($snippet, 'withoutOverlapping') && ! str_contains($snippet, '->name(')) $errors[] = "scheduler.callback-overlap-without-name: {$name}";
                if (! str_contains($snippet, '->when($leaderCheck)')) $errors[] = "scheduler.callback-not-leader-gated: {$name}";
            }
        }

        // Queue jobs are container-driven entry points but must not depend on HTTP request/session context.
        foreach (glob($root.'/app/Jobs/*.php') ?: [] as $file) {
            $source = $read($file);
            if (! str_contains($source, 'ShouldQueue')) continue;
            $checks['queue_jobs']++;
            $handle = $extractParams($source, 'handle');
            if ($handle === null) {
                $errors[] = 'queue.missing-handle: '.basename($file);
                continue;
            }
            if (preg_match('/(?:^|\\\\)Request\b|(?:^|\\\\)Response\b/', $handle) === 1) {
                $errors[] = 'queue.http-handle-dependency: '.basename($file);
            }
            if (preg_match('/\b(?:request|session)\s*\(/i', $source) === 1) {
                $errors[] = 'queue.http-context-access: '.basename($file);
            }
        }

        // Service provider register() is zero-argument; boot() dependencies must be container-resolvable class/interface types.
        foreach (glob($root.'/app/Providers/*.php') ?: [] as $file) {
            $source = $read($file);
            $checks['service_providers']++;
            $register = $extractParams($source, 'register');
            if ($register !== null && count($splitParams($register)) !== 0) {
                $errors[] = 'provider.register-parameters: '.basename($file);
            }
            $boot = $extractParams($source, 'boot');
            foreach ($splitParams($boot) as $param) {
                $type = $typeOf($param);
                if ($type === '' || $isBuiltinType($type)) {
                    $errors[] = 'provider.boot-non-container-param: '.basename($file).' ['.$param.']';
                }
            }
        }

        // Registered providers themselves must resolve to source files.
        $providers = $read($root.'/bootstrap/providers.php');
        $providerImports = [];
        if (preg_match_all('/^use\s+(App\\\\Providers\\\\([A-Za-z0-9_]+));/m', $providers, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) $providerImports[$match[2]] = $match[1];
        }
        if (preg_match_all('/([A-Za-z0-9_]+)::class/', $providers, $matches)) {
            foreach ($matches[1] ?? [] as $class) {
                $fqcn = $providerImports[$class] ?? null;
                if ($fqcn === null) {
                    $errors[] = "provider.bootstrap-import-missing: {$class}";
                    continue;
                }
                $relative = 'app/'.str_replace('\\', '/', substr($fqcn, 4)).'.php';
                if (! is_file($root.'/'.$relative)) $errors[] = "provider.bootstrap-class-missing: {$class}";
            }
        }

        $errors = array_values(array_unique($errors));
        return ['ok' => $errors === [], 'errors' => $errors, 'checks' => $checks];
    }
}
