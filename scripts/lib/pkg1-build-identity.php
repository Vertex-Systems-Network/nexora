<?php

declare(strict_types=1);

require_once __DIR__.'/source-attestation.php';

/** @return list<string> */
function nexoraPkg1BuildIdentityInputs(): array
{
    return [
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'tsconfig.json',
        'vite.config.ts',
        'resources/js/admin/pages/Admin/Automation/Form.tsx',
        'resources/js/admin/pages/Admin/Cloud/Index.tsx',
        'resources/js/admin/pages/Admin/Discovery/Index.tsx',
        'resources/js/admin/pages/Admin/Distribution/Index.tsx',
        'resources/js/admin/pages/Admin/Documents/Form.tsx',
        'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx',
        'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx',
        'resources/js/admin/pages/Admin/Media/Index.tsx',
        'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx',
        'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx',
        'resources/js/admin/pages/Admin/Studio/Editor.tsx',
    ];
}

/** @return array<string,mixed> */
function nexoraPkg1BuildIdentity(string $root): array
{
    $platform = require $root.'/config/nexora.php';
    $installerSource = (string) file_get_contents($root.'/app/Nexora/Installation/Installer.php');
    preg_match("/public const PROTOCOL = '([^']+)'/", $installerSource, $protocolMatch);
    preg_match("/public const SOURCE_GENERATION = '([^']+)'/", $installerSource, $generationMatch);
    $source = nexoraComputeSourceAttestation($root);
    $files = [];
    foreach (nexoraPkg1BuildIdentityInputs() as $relative) {
        $path = $root.'/'.$relative;
        $files[$relative] = is_file($path)
            ? (hash_file('sha256', $path) ?: null)
            : null;
    }

    $identity = [
        'schema' => 1,
        'platform_version' => (string) ($platform['version'] ?? 'unknown'),
        'installer_protocol' => (string) ($protocolMatch[1] ?? 'unknown'),
        'source_generation' => (string) ($generationMatch[1] ?? 'unknown'),
        'source_tree_sha256' => $source['tree_sha256'],
        'inputs' => $files,
    ];
    $identity['identity_sha256'] = nexoraPkg1BuildIdentityHash($identity);

    return $identity;
}

/** @param array<string,mixed> $identity */
function nexoraPkg1BuildIdentityHash(array $identity): string
{
    unset($identity['identity_sha256'], $identity['status'], $identity['generated_at']);
    $canonicalize = static function (mixed $value) use (&$canonicalize): mixed {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map($canonicalize, $value);
        }
        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $canonicalize($item);
        }
        return $value;
    };

    return hash('sha256', json_encode(
        $canonicalize($identity),
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ));
}
