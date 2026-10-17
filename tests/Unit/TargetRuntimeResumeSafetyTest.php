<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-resume-contracts.php';

final class TargetRuntimeResumeSafetyTest extends TestCase
{
    private const RUN_ID = '20261016-050000-a1b2c3';

    /** @return array<string,mixed> */
    private function summary(array $steps, string $status = 'blocked'): array
    {
        return ['run_id' => self::RUN_ID, 'status' => $status, 'steps' => $steps];
    }

    /** @return array<string,mixed> */
    private function step(string $id, string $status = 'pass', int $exit = 0, bool $required = true): array
    {
        return ['id' => $id, 'status' => $status, 'exit_code' => $exit, 'required' => $required];
    }

    private function reusable(array $summary): array
    {
        return \nexoraTargetReusableStepIds(
            $summary,
            ['composer-install' => true, 'npm-ci' => true, 'frontend-tests' => true],
            self::RUN_ID
        );
    }

    #[Test]
    public function genuinely_completed_required_step_is_reused_from_blocked_previous_run(): void
    {
        self::assertSame(
            ['composer-install' => true, 'npm-ci' => true],
            $this->reusable($this->summary([
                $this->step('composer-install'),
                $this->step('npm-ci'),
                $this->step('source-preflight'),
            ]))
        );
    }

    #[Test]
    public function contradictory_pass_and_nonzero_exit_must_be_rerun(): void
    {
        self::assertSame([], $this->reusable($this->summary([$this->step('npm-ci', 'pass', 3)])));
    }

    #[Test]
    public function optional_pass_cannot_skip_required_dependency_gate(): void
    {
        self::assertSame([], $this->reusable($this->summary([$this->step('npm-ci', 'pass', 0, false)])));
    }

    #[Test]
    public function a_duplicate_step_record_cannot_mask_a_failure(): void
    {
        self::assertSame([], $this->reusable($this->summary([
            $this->step('npm-ci'), $this->step('npm-ci', 'fail', 2),
        ])));
    }

    #[Test]
    public function a_different_run_id_cannot_supply_resumable_steps(): void
    {
        $summary = $this->summary([$this->step('npm-ci')]);
        $summary['run_id'] = '20261016-050100-deadbe';
        self::assertSame([], $this->reusable($summary));
    }

    #[Test]
    public function incomplete_step_metadata_cannot_be_reused(): void
    {
        self::assertSame([], $this->reusable($this->summary([
            ['id' => 'npm-ci', 'status' => 'pass'],
        ])));
    }

    #[Test]
    public function failed_previous_step_is_rerun_while_prior_success_remains_eligible(): void
    {
        self::assertSame(
            ['composer-install' => true],
            $this->reusable($this->summary([
                $this->step('composer-install'), $this->step('npm-ci', 'fail', 1),
            ]))
        );
    }

    #[Test]
    public function unknown_status_or_non_list_steps_are_not_reused(): void
    {
        self::assertSame([], $this->reusable($this->summary([$this->step('npm-ci')], 'unknown')));
        self::assertSame([], $this->reusable($this->summary(['npm-ci' => $this->step('npm-ci')])));
    }
}
