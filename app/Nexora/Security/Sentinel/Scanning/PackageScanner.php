<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Contracts\PackageScannerContract;
use App\Nexora\Security\Sentinel\Data\ScanReport;
use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;

final readonly class PackageScanner implements PackageScannerContract
{
    public function __construct(
        private ArchiveInspector $archives,
        private ManifestValidator $manifests,
        private ComposerManifestScanner $composer,
        private NpmManifestScanner $npm,
        private PhpAstScanner $phpAst,
        private PhpStaticScanner $php,
        private JavaScriptStaticScanner $javascript,
        private WebAssetScanner $webAssets,
        private CssStaticScanner $css,
        private SecretPatternScanner $secrets,
        private MigrationPolicyScanner $migrations,
        private RoutePolicyScanner $routes,
        private RiskEngine $risk,
    ) {
    }

    public function scan(string $path): ScanReport
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("Sentinel scan source [{$path}] does not exist or is not a file.");
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'zip') {
            throw new \InvalidArgumentException('N0.5 package scanning accepts ZIP archives only. Directory/package-builder scanning will be added through Nexora Forge.');
        }

        $sha256 = hash_file('sha256', $path);
        if (! is_string($sha256)) {
            throw new \RuntimeException('Unable to calculate package SHA-256 digest.');
        }

        $inspection = $this->archives->inspect($path);
        $manifestResult = $this->manifests->validate($inspection['manifest']);
        $findings = [...$inspection['findings'], ...$manifestResult['findings']];

        $composerJson = $inspection['files']['composer.json'] ?? null;
        $findings = [...$findings, ...$this->composer->scan(is_string($composerJson) ? $composerJson : null)];
        $packageJson = $inspection['files']['package.json'] ?? null;
        $findings = [...$findings, ...$this->npm->scan(is_string($packageJson) ? $packageJson : null)];

        foreach ($inspection['files'] as $filePath => $contents) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $findings = [...$findings, ...$this->secrets->scan($filePath, $contents)];
            if (in_array($extension, ['php', 'phtml', 'inc'], true)) {
                $findings = [
                    ...$findings,
                    ...$this->phpAst->scan($filePath, $contents),
                    ...$this->php->scan($filePath, $contents),
                    ...$this->migrations->scan($filePath, $contents),
                    ...$this->routes->scan($filePath, $contents),
                ];
                continue;
            }
            if (in_array($extension, ['js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx'], true)) {
                $findings = [...$findings, ...$this->javascript->scan($filePath, $contents)];
                continue;
            }
            if ($extension === 'css') {
                $findings = [...$findings, ...$this->css->scan($filePath, $contents)];
                continue;
            }
            if (in_array($extension, ['svg', 'html', 'htm'], true)) {
                $findings = [...$findings, ...$this->webAssets->scan($filePath, $contents)];
            }
        }

        $findings = $this->deduplicateFindings($findings);
        $findings = [...$findings, ...$this->capabilityMismatchFindings($manifestResult['manifest'], $findings)];
        $findings = $this->deduplicateFindings($findings);

        $sha256After = hash_file('sha256', $path);
        if (! is_string($sha256After) || ! hash_equals($sha256, $sha256After)) {
            $findings[] = new SecurityFinding(
                'NEX-QRT-0002', FindingSeverity::Critical, 'integrity', 'Package changed while Sentinel was scanning it',
                'The archive digest changed during inspection. Sentinel treats time-of-check/time-of-use mutation as quarantine tampering.',
                hardBlock: true,
            );
        }

        $assessment = $this->risk->evaluate($findings);

        return new ScanReport(
            sourceName: basename($path),
            sourceSha256: $sha256,
            decision: $assessment['decision'],
            riskScore: $assessment['score'],
            findings: $findings,
            manifest: $manifestResult['manifest'],
            metrics: $inspection['metrics'],
        );
    }

    /** @param array<string,mixed> $manifest @param list<SecurityFinding> $findings @return list<SecurityFinding> */
    private function capabilityMismatchFindings(array $manifest, array $findings): array
    {
        $declared = array_values(array_filter((array) ($manifest['capabilities'] ?? []), 'is_string'));
        $requiresNetwork = false;
        $requiresEnvironment = false;
        $requiresFilesystem = false;

        foreach ($findings as $finding) {
            if (in_array($finding->ruleId, ['NEX-PHP-0020', 'NEX-PHP-0021', 'NEX-PHP-0022', 'NEX-PHP-0023', 'NEX-JS-0020', 'NEX-JS-0021', 'NEX-JS-0022', 'NEX-WEB-0005', 'NEX-CSS-0001', 'NEX-CSS-0002'], true)) {
                $requiresNetwork = true;
            }
            if (in_array($finding->ruleId, ['NEX-PHP-0040', 'NEX-PHP-0064', 'NEX-JS-0010'], true)) {
                $requiresEnvironment = true;
            }
            if (in_array($finding->ruleId, ['NEX-PHP-0030', 'NEX-PHP-0031', 'NEX-PHP-0032'], true)) {
                $requiresFilesystem = true;
            }
        }

        $mismatches = [];
        if ($requiresNetwork && ! in_array('http.outbound', $declared, true)) {
            $mismatches[] = new SecurityFinding('NEX-CAP-0001', FindingSeverity::High, 'capability', 'Undeclared network behavior detected', 'Code attempts direct network access but nexora.json does not declare the http.outbound capability.', 'nexora.json', hardBlock: true);
        }
        if ($requiresEnvironment && ! in_array('secrets.read', $declared, true)) {
            $mismatches[] = new SecurityFinding('NEX-CAP-0002', FindingSeverity::High, 'capability', 'Undeclared environment/secret access detected', 'Code reads environment values but the package does not declare a secret-access capability.', 'nexora.json', hardBlock: true);
        }
        if ($requiresFilesystem && ! array_intersect(['filesystem.private', 'filesystem.public'], $declared)) {
            $mismatches[] = new SecurityFinding('NEX-CAP-0003', FindingSeverity::High, 'capability', 'Undeclared filesystem behavior detected', 'Code mutates the filesystem without declaring a scoped filesystem capability.', 'nexora.json', hardBlock: true);
        }

        return $mismatches;
    }
    /** @param list<SecurityFinding> $findings @return list<SecurityFinding> */
    private function deduplicateFindings(array $findings): array
    {
        $seen = [];
        $result = [];
        foreach ($findings as $finding) {
            $key = implode('|', [$finding->ruleId, $finding->filePath ?? '', (string) ($finding->lineStart ?? 0)]);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $finding;
        }

        return $result;
    }

}
