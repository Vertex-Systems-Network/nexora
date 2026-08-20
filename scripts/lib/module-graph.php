<?php

declare(strict_types=1);

use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;

/**
 * Static, dependency-free verification of Nexora's configured Core module graph.
 *
 * @return array{ok:bool,modules:array<string,array{class:string,file:string,version:string,dependencies:list<array{identifier:string,constraint:string,optional:bool}>}>,boot_order:list<string>,errors:list<string>}
 */
function nexoraAnalyzeModuleGraph(string $root): array
{
    require_once $root.'/app/Nexora/Foundation/Runtime/VersionConstraintMatcher.php';

    /** @var array<string,mixed> $config */
    $config = require $root.'/config/nexora.php';
    $classes = array_values((array) ($config['modules']['classes'] ?? []));
    $errors = [];
    $modules = [];
    $seenClasses = [];

    foreach ($classes as $class) {
        $class = (string) $class;
        if ($class === '') {
            $errors[] = 'Configured module class is empty.';
            continue;
        }
        if (isset($seenClasses[$class])) {
            $errors[] = "Duplicate configured module class [{$class}].";
            continue;
        }
        $seenClasses[$class] = true;
        $basename = substr($class, strrpos($class, '\\') + 1);
        $file = $root.'/app/Nexora/Modules/Core/'.$basename.'.php';
        if (! is_file($file)) {
            $errors[] = "Configured module class [{$class}] has no Core source file [{$file}].";
            continue;
        }
        $source = (string) file_get_contents($file);
        if (preg_match("/identifier\\s*:\\s*'([^']+)'/", $source, $idMatch) !== 1) {
            $errors[] = "Module [{$class}] is missing a statically verifiable identifier.";
            continue;
        }
        if (preg_match("/version\\s*:\\s*'([^']+)'/", $source, $versionMatch) !== 1) {
            $errors[] = "Module [{$idMatch[1]}] is missing a statically verifiable version.";
            continue;
        }
        $identifier = $idMatch[1];
        if (isset($modules[$identifier])) {
            $errors[] = "Duplicate Nexora module identifier [{$identifier}] in [{$modules[$identifier]['file']}] and [{$file}].";
            continue;
        }
        $dependencies = [];
        if (preg_match_all("/new\\s+ModuleDependency\\(\\s*'([^']+)'(?:\\s*,\\s*'([^']+)')?(?:\\s*,\\s*(true|false))?\\s*\\)/", $source, $depMatches, PREG_SET_ORDER)) {
            foreach ($depMatches as $match) {
                $dependencies[] = [
                    'identifier' => $match[1],
                    'constraint' => ($match[2] ?? '') !== '' ? $match[2] : '*',
                    'optional' => ($match[3] ?? 'false') === 'true',
                ];
            }
        }
        $modules[$identifier] = [
            'class' => $class,
            'file' => str_replace('\\', '/', substr($file, strlen($root) + 1)),
            'version' => $versionMatch[1],
            'dependencies' => $dependencies,
        ];
    }

    $coreFiles = glob($root.'/app/Nexora/Modules/Core/*Module.php') ?: [];
    foreach ($coreFiles as $file) {
        $basename = basename($file, '.php');
        $expected = 'App\\Nexora\\Modules\\Core\\'.$basename;
        if (! isset($seenClasses[$expected])) {
            $errors[] = "Core module source [app/Nexora/Modules/Core/{$basename}.php] is not registered in config/nexora.php.";
        }
    }

    $versions = new VersionConstraintMatcher();
    foreach ($modules as $identifier => $manifest) {
        foreach ($manifest['dependencies'] as $dependency) {
            $depId = $dependency['identifier'];
            if ($depId === $identifier) {
                $errors[] = "Module [{$identifier}] depends on itself.";
                continue;
            }
            if (! isset($modules[$depId])) {
                if (! $dependency['optional']) {
                    $errors[] = "Module [{$identifier}] requires missing module [{$depId}] ({$dependency['constraint']}).";
                }
                continue;
            }
            $installed = $modules[$depId]['version'];
            if (! $versions->matches($installed, $dependency['constraint']) && ! $dependency['optional']) {
                $errors[] = "Module [{$identifier}] requires [{$depId}] {$dependency['constraint']}, configured {$installed}.";
            }
        }
    }

    $temporary = [];
    $permanent = [];
    $bootOrder = [];
    $visit = function (string $identifier) use (&$visit, &$temporary, &$permanent, &$bootOrder, &$errors, $modules): void {
        if (isset($permanent[$identifier])) return;
        if (isset($temporary[$identifier])) {
            $errors[] = "Circular Nexora module dependency detected at [{$identifier}].";
            return;
        }
        $temporary[$identifier] = true;
        foreach ($modules[$identifier]['dependencies'] as $dependency) {
            if (! isset($modules[$dependency['identifier']])) continue;
            $visit($dependency['identifier']);
        }
        unset($temporary[$identifier]);
        $permanent[$identifier] = true;
        $bootOrder[] = $identifier;
    };
    foreach (array_keys($modules) as $identifier) $visit($identifier);

    $errors = array_values(array_unique($errors));

    return [
        'ok' => $errors === [],
        'modules' => $modules,
        'boot_order' => array_values(array_unique($bootOrder)),
        'errors' => $errors,
    ];
}
