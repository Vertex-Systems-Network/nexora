<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-runtime-receipt-contracts.php';

final class TargetRuntimePassReceiptTest extends TestCase
{
    /** @return array<string,mixed> */
    private function receipt(bool $install = false, bool $full = false): array
    {
        $ids = [
            'source-preflight', 'target-bootstrap', 'target-runtime-contract', 'inertia-contract',
            'dependency-lock-review', 'dependency-contract-strict', 'dependency-runtime',
        ];
        if ($install) {
            array_push($ids, 'composer-install', 'npm-ci');
        }
        array_push($ids,
            'frontend-typecheck', 'frontend-tests', 'frontend-build', 'frontend-build-budget',
            'package-discover', 'optimize-clear', 'artisan-about', 'route-list', 'schedule-list',
            'database-doctor', 'environment-doctor', 'filesystem-doctor', 'transfer-doctor',
            'runtime-doctor', 'concurrency-doctor'
        );
        if ($full) {
            $ids[] = 'isolated-full-certification';
        }
        $ids[] = 'closure-status';
        $steps = array_map(static fn(string $id): array => [
            'id' => $id, 'required' => $id !== 'closure-status',
            'status' => 'pass', 'exit_code' => 0,
        ], $ids);
        return [
            'schema' => 2,
            'run_id' => '20261016-140000-a1b2c3',
            'status' => $full ? 'target-certification-pass' : 'target-readiness-pass',
            'environment' => [
                'full_certification_requested' => $full,
                'install_dependencies_requested' => $install,
            ],
            'steps' => $steps,
        ];
    }

    #[Test]
    public function complete_readiness_receipt_passes(): void
    {
        self::assertSame([], \nexoraTargetRuntimePassReceiptErrors($this->receipt()));
    }

    #[Test]
    public function complete_install_and_full_certification_receipt_passes(): void
    {
        self::assertSame([], \nexoraTargetRuntimePassReceiptErrors($this->receipt(true, true)));
    }

    #[Test]
    public function all_four_producer_execution_modes_have_valid_complete_receipts(): void
    {
        foreach ([false, true] as $install) {
            foreach ([false, true] as $full) {
                self::assertSame([], \nexoraTargetRuntimePassReceiptErrors($this->receipt($install, $full)));
            }
        }
    }

    #[Test]
    public function even_a_successful_preflight_only_receipt_is_rejected(): void
    {
        $r = $this->receipt();
        $r['steps'] = [$r['steps'][0]];
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
    }

    #[Test]
    public function missing_required_gate_and_reordered_gates_are_rejected(): void
    {
        $r = $this->receipt();
        array_splice($r['steps'], 10, 1);
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
        $r = $this->receipt();
        [$r['steps'][1], $r['steps'][2]] = [$r['steps'][2], $r['steps'][1]];
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
    }

    #[Test]
    public function forged_readiness_when_full_requested_is_rejected(): void
    {
        $r = $this->receipt(true, true);
        $r['status'] = 'target-readiness-pass';
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
        $r = $this->receipt();
        $r['status'] = 'target-certification-pass';
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
    }

    #[Test]
    public function missing_install_gates_are_rejected_when_install_was_requested(): void
    {
        $r = $this->receipt();
        $r['environment']['install_dependencies_requested'] = true;
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
    }

    #[Test]
    public function required_flag_tampering_and_failed_required_step_are_rejected(): void
    {
        $r = $this->receipt();
        $r['steps'][4]['required'] = false;
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
        $r = $this->receipt();
        $r['steps'][4]['status'] = 'fail';
        $r['steps'][4]['exit_code'] = 1;
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
    }

    #[Test]
    public function failed_informational_closure_is_allowed_but_cannot_be_required(): void
    {
        $r = $this->receipt();
        $i = count($r['steps']) - 1;
        $r['steps'][$i]['status'] = 'fail';
        $r['steps'][$i]['exit_code'] = 1;
        self::assertSame([], \nexoraTargetRuntimePassReceiptErrors($r));
        $r['steps'][$i]['required'] = true;
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
    }

    #[Test]
    public function invalid_run_identity_schema_and_mode_flags_are_rejected(): void
    {
        $r = $this->receipt();
        $r['schema'] = 1;
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
        $r = $this->receipt();
        $r['run_id'] = '../another-run';
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
        $r = $this->receipt();
        unset($r['environment']['full_certification_requested']);
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
    }

    #[Test]
    public function unknown_or_duplicate_gate_does_not_pass(): void
    {
        $r = $this->receipt();
        $r['steps'][6]['id'] = 'not-a-gate';
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
        $r = $this->receipt();
        $r['steps'][6]['id'] = $r['steps'][5]['id'];
        self::assertNotSame([], \nexoraTargetRuntimePassReceiptErrors($r));
    }
}
