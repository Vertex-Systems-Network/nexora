<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-resume-contracts.php';

final class TargetRuntimeResumeLineageTest extends TestCase
{
    /** @return list<array<string,mixed>> */
    private function prefix(bool $install): array
    {
        $ids = [
            'source-preflight', 'target-bootstrap', 'target-runtime-contract',
            'inertia-contract', 'dependency-lock-review',
            'dependency-contract-strict', 'dependency-runtime',
        ];
        if ($install) array_push($ids, 'composer-install', 'npm-ci');
        array_push($ids, 'frontend-typecheck', 'frontend-tests',
            'frontend-build', 'frontend-build-budget');
        return array_map(static fn(string $id):array => [
            'id'=>$id, 'required'=>true, 'status'=>'pass', 'exit_code'=>0,
        ], $ids);
    }

    /** @param list<array<string,mixed>> $steps */
    private function receipt(array $steps, bool $install = true): array
    {
        return ['schema'=>2, 'status'=>'blocked',
            'environment'=>['install_dependencies_requested'=>$install, 'full_certification_requested'=>false],
            'steps'=>$steps];
    }

    private function allowlist(): array
    {
        return [
            'composer-install'=>true,'npm-ci'=>true,'frontend-typecheck'=>true,
            'frontend-tests'=>true,'frontend-build'=>true,'frontend-build-budget'=>true,
        ];
    }

    #[Test]
    public function complete_installed_prefix_can_reuse_all_steps(): void
    {
        self::assertSame($this->allowlist(), \nexoraTargetResumeLineageAllowlist(
            $this->receipt($this->prefix(true)), $this->allowlist()));
    }

    #[Test]
    public function no_install_mode_never_reuses_composer_or_npm_install(): void
    {
        $allowed=\nexoraTargetResumeLineageAllowlist(
            $this->receipt($this->prefix(false), false), $this->allowlist());
        self::assertArrayNotHasKey('composer-install', $allowed);
        self::assertArrayNotHasKey('npm-ci', $allowed);
        self::assertArrayHasKey('frontend-typecheck', $allowed);
        self::assertArrayHasKey('frontend-build-budget', $allowed);
    }

    #[Test]
    public function lone_forged_frontend_pass_cannot_be_reused(): void
    {
        $steps=[['id'=>'frontend-build','required'=>true,'status'=>'pass','exit_code'=>0]];
        self::assertSame([], \nexoraTargetResumeLineageAllowlist(
            $this->receipt($steps), $this->allowlist()));
    }

    #[Test]
    public function reordered_early_steps_are_not_proof_of_downstream_pass(): void
    {
        $steps=$this->prefix(true);
        [$steps[0],$steps[1]]=[$steps[1],$steps[0]];
        self::assertSame([], \nexoraTargetResumeLineageAllowlist(
            $this->receipt($steps), $this->allowlist()));
    }

    #[Test]
    public function missing_dependency_review_step_prevents_install_and_frontend_reuse(): void
    {
        $steps=$this->prefix(true);
        array_splice($steps,4,1);
        self::assertSame([], \nexoraTargetResumeLineageAllowlist(
            $this->receipt($steps), $this->allowlist()));
    }

    #[Test]
    public function failed_npm_install_keeps_composer_proven_but_rejects_later_steps(): void
    {
        $steps=$this->prefix(true);
        $steps[8]['status']='fail';
        $steps[8]['exit_code']=1;
        self::assertSame(['composer-install'=>true], \nexoraTargetResumeLineageAllowlist(
            $this->receipt($steps), $this->allowlist()));
    }

    #[Test]
    public function failed_frontend_typecheck_stops_reuse_of_later_frontend_steps(): void
    {
        $steps=$this->prefix(true);
        $steps[9]['status']='fail';
        $steps[9]['exit_code']=1;
        self::assertSame(['composer-install'=>true,'npm-ci'=>true], \nexoraTargetResumeLineageAllowlist(
            $this->receipt($steps), $this->allowlist()));
    }

    #[Test]
    public function prerequisite_failure_receipt_blocks_downstream_lineage(): void
    {
        $steps=$this->prefix(true);
        array_splice($steps,4,0,[['id'=>'composer-required','required'=>true,'status'=>'fail','exit_code'=>127]]);
        self::assertSame([], \nexoraTargetResumeLineageAllowlist(
            $this->receipt($steps), $this->allowlist()));
    }

    #[Test]
    public function optional_gate_cannot_be_used_as_proof_of_required_gate(): void
    {
        $steps=$this->prefix(true);
        $steps[7]['required']=false;
        self::assertSame([], \nexoraTargetResumeLineageAllowlist(
            $this->receipt($steps), $this->allowlist()));
    }

    #[Test]
    public function malformed_or_missing_mode_flags_disable_all_reuse(): void
    {
        $receipt=$this->receipt($this->prefix(true));
        unset($receipt['environment']['install_dependencies_requested']);
        self::assertSame([], \nexoraTargetResumeLineageAllowlist($receipt, $this->allowlist()));
        $receipt=$this->receipt($this->prefix(true));
        $receipt['schema']=1;
        self::assertSame([], \nexoraTargetResumeLineageAllowlist($receipt, $this->allowlist()));
    }

    #[Test]
    public function missing_full_certification_mode_blocks_lineage_reuse(): void
    {
        $receipt = $this->receipt($this->prefix(true));
        unset($receipt['environment']['full_certification_requested']);
        self::assertSame([], \nexoraTargetResumeLineageAllowlist($receipt, $this->allowlist()));
    }

    #[Test]
    public function non_boolean_full_mode_blocks_lineage_reuse(): void
    {
        $receipt = $this->receipt($this->prefix(true));
        $receipt['environment']['full_certification_requested'] = 'false';
        self::assertSame([], \nexoraTargetResumeLineageAllowlist($receipt, $this->allowlist()));
    }

    #[Test]
    public function partial_valid_prefix_reuses_only_proven_steps(): void
    {
        $steps=array_slice($this->prefix(true),0,10);
        self::assertSame([
            'composer-install'=>true,'npm-ci'=>true,'frontend-typecheck'=>true,
        ], \nexoraTargetResumeLineageAllowlist(
            $this->receipt($steps), $this->allowlist()));
    }
}
