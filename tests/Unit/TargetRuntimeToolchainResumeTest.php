<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-resume-contracts.php';
require_once __DIR__.'/../../scripts/lib/target-runtime-toolchain-versions.php';

final class TargetRuntimeToolchainResumeTest extends TestCase
{
    private function environment(): array
    {
        return ['composer_version'=>'2.9.1','node_version'=>'22.16.0','npm_version'=>'10.9.2',
            'vendor_autoload'=>true,'vendor_installed_sha256'=>str_repeat('a',64),
            'node_modules'=>true,'node_modules_lock_sha256'=>str_repeat('b',64)];
    }

    private function allow(): array
    {
        return ['composer-install'=>true,'npm-ci'=>true,'frontend-typecheck'=>true,
            'frontend-tests'=>true,'frontend-build'=>true,'frontend-build-budget'=>true];
    }

    #[Test]
    public function parser_accepts_semver_from_three_tools(): void
    {
        $responses=['composer'=>'Composer version 2.9.1 2026-10-01',
            'node'=>'v22.16.0','npm'=>'10.9.2'];
        $versions=\nexoraTargetResumeToolchainVersions(
            static fn(array $command):array => [
                'exit_code'=>0,'stdout'=>$responses[$command[0]],'stderr'=>'']
        );
        self::assertSame(['composer_version'=>'2.9.1','node_version'=>'22.16.0',
            'npm_version'=>'10.9.2'], $versions);
    }

    #[Test]
    public function failed_or_malformed_probes_are_null(): void
    {
        $versions=\nexoraTargetResumeToolchainVersions(
            static fn(array $command):array => ['exit_code'=>$command[0]==='composer'?1:0,
                'stdout'=>$command[0]==='npm'?'garbage':'','stderr'=>'failed']
        );
        self::assertSame(['composer_version'=>null,'node_version'=>null,'npm_version'=>null],$versions);
    }

    #[Test]
    public function missing_composer_version_only_disables_composer_install(): void
    {
        $env=$this->environment();$env['composer_version']=null;
        $filtered=\nexoraTargetResumeSafeStepAllowlist($env,$this->allow());
        self::assertArrayNotHasKey('composer-install',$filtered);
        self::assertArrayHasKey('npm-ci',$filtered);
    }

    #[Test]
    public function node_or_npm_version_missing_disables_frontend_and_npm_reuse(): void
    {
        foreach(['node_version','npm_version'] as $key){
            $env=$this->environment();$env[$key]=null;
            self::assertSame(['composer-install'=>true],
                \nexoraTargetResumeSafeStepAllowlist($env,$this->allow()));
        }
    }

    #[Test]
    public function toolchain_version_drift_aborts_resume_even_when_locks_match(): void
    {
        $before=['composer_version'=>'2.9.1','node_version'=>'22.16.0','npm_version'=>'10.9.2'];
        $after=$before;$after['node_version']='22.17.0';
        $keys=['platform_version','source_tree_sha256','php_version','os_family','php_binary',
            'php_extensions_sha256','composer_available','node_available','npm_available',
            'composer_lock_sha256','package_lock_sha256','vendor_installed_sha256',
            'node_modules_lock_sha256','vendor_autoload','node_modules','install_dependencies_requested'];
        foreach($keys as $key){$before[$key]='same';$after[$key]='same';}
        $before['install_dependencies_requested']=true;
        $after['install_dependencies_requested']=true;
        $before['full_certification_requested']=false;
        $after['full_certification_requested']=false;
        self::assertSame('node_version',\nexoraTargetResumeEnvironmentDrift($before,$after));
    }
}
