<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Support;

use App\Models\QuarantinePackage;
use App\Models\SecurityScan;
use App\Nexora\Security\Sentinel\Contracts\PackageScannerContract;
use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\SupplyChain\Services\SupplyChainAnalyzer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class ScanRecorder
{
    public function __construct(
        private PackageScannerContract $scanner,
        private QuarantinePathGuard $paths,
        private SupplyChainAnalyzer $supplyChain,
        private SentinelFailureReference $failures,
    ) {
    }

    public function scan(QuarantinePackage $package, ?int $userId): SecurityScan
    {
        $scanPath = $this->paths->assertInside((string) $package->path);
        $currentSha256 = hash_file('sha256', $scanPath);
        if (! is_string($currentSha256)) {
            throw new \RuntimeException('Unable to hash the quarantined package before scanning.');
        }
        $baselineTampered = ! hash_equals((string) $package->sha256, $currentSha256);

        $scan = SecurityScan::query()->create([
            'id' => (string) Str::uuid(),
            'quarantine_package_id' => $package->id,
            'source_type' => 'archive',
            'source_name' => $package->original_name,
            'source_sha256' => $currentSha256,
            'engine_version' => (string) config('sentinel.engine_version', '0.5.0'),
            'status' => 'scanning',
            'decision' => 'pending',
            'risk_score' => 0,
            'requested_by' => $userId,
            'started_at' => now(),
        ]);

        try {
            $report = $this->scanner->scan($scanPath);
            $tamperFinding = $baselineTampered ? new SecurityFinding(
                'NEX-QRT-0001', FindingSeverity::Critical, 'integrity', 'Quarantined package digest no longer matches upload baseline',
                'The stored ZIP changed after quarantine. The original upload SHA-256 is preserved and activation remains blocked.',
                hardBlock: true,
                metadata: ['baseline_sha256' => $package->sha256, 'current_sha256' => $currentSha256],
            ) : null;

            DB::transaction(function () use ($package, $scan, $report, $tamperFinding): void {
                $allFindings = $report->findings;
                if ($tamperFinding !== null) {
                    $allFindings[] = $tamperFinding;
                }
                foreach ($allFindings as $finding) {
                    $scan->findings()->create($finding->toArray());
                }

                $severity = $report->severityCounts();
                if ($tamperFinding !== null) {
                    $severity['critical'] = ($severity['critical'] ?? 0) + 1;
                }
                $decision = $tamperFinding !== null ? 'block' : $report->decision->value;
                $riskScore = $tamperFinding !== null ? 100 : $report->riskScore;

                $scan->forceFill([
                    'status' => 'completed',
                    'decision' => $decision,
                    'risk_score' => $riskScore,
                    'manifest' => $report->manifest,
                    'summary' => [
                        'severity' => $severity,
                        'metrics' => $report->metrics,
                        'finding_count' => count($allFindings),
                    ],
                    'error' => null,
                    'completed_at' => now(),
                ])->save();

                $package->forceFill([
                    'status' => $decision === 'allow' ? 'scanned' : 'quarantined',
                    'scanned_at' => now(),
                ])->save();
            });

            try {
                $this->supplyChain->analyze($package, $scan->fresh() ?? $scan);
            } catch (Throwable $supplyChainException) {
                report($supplyChainException);
                $summary = (array) ($scan->fresh()?->summary ?? $scan->summary ?? []);
                $summary['supply_chain'] = [
                    'status' => 'failed',
                    'message' => 'Supply-chain analysis could not complete; the package is not promoted to a trusted execution tier.',
                ];
                $scan->forceFill(['summary'=>$summary])->save();
            }
        } catch (Throwable $exception) {
            $failure = $this->failures->report($exception, (string) $scan->id, [
                'scan_id' => (string) $scan->id,
                'package_id' => (string) $package->id,
            ]);
            $summary = (array) ($scan->summary ?? []);
            $summary['failure'] = [
                'reference' => $failure['reference'],
                'class_fingerprint' => $failure['class_fingerprint'],
            ];
            $scan->forceFill([
                'status' => 'failed',
                'decision' => 'block',
                'risk_score' => 100,
                'summary' => $summary,
                'error' => $failure['message'],
                'completed_at' => now(),
            ])->save();
            $package->forceFill(['status' => 'quarantined', 'scanned_at' => now()])->save();
            throw $exception;
        }

        return $scan->fresh(['findings', 'quarantinePackage']) ?? $scan;
    }
}
