<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Models\QuarantinePackage;
use App\Nexora\Security\SupplyChain\Services\SupplyChainAnalyzer;
use Illuminate\Console\Command;

final class SupplyChainVerifyCommand extends Command
{
    protected $signature = 'nexora:supply-chain:verify {package : Quarantine package UUID} {--json : Print machine-readable JSON}';
    protected $description = 'Re-evaluate SBOM, signature, provenance and execution trust for an already scanned quarantined package.';

    public function handle(SupplyChainAnalyzer $analyzer): int
    {
        $package = QuarantinePackage::query()->with(['scans' => fn ($query) => $query->latest('created_at')])->find((string) $this->argument('package'));
        if (! $package) {
            $this->error('Quarantine package was not found.');
            return self::FAILURE;
        }
        $scan = $package->scans->first();
        if (! $scan || $scan->status !== 'completed') {
            $this->error('Run a completed Sentinel scan before supply-chain verification.');
            return self::FAILURE;
        }

        try {
            $artifact = $analyzer->analyze($package, $scan);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $payload = [
            'package' => $package->id,
            'scan' => $scan->id,
            'artifact_sha256' => $artifact->artifact_sha256,
            'content_sha256' => $artifact->content_sha256,
            'signature_status' => $artifact->signature_status,
            'provenance_status' => $artifact->provenance_status,
            'trust_tier' => $artifact->trust_tier,
            'sandbox_profile' => $artifact->sandbox_profile,
            'components' => $artifact->components()->count(),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($payload as $label => $value) $this->components->twoColumnDetail(str_replace('_', ' ', ucfirst($label)), (string) $value);
        }

        return in_array($artifact->signature_status, ['verified', 'missing', 'untrusted'], true) ? self::SUCCESS : self::FAILURE;
    }
}
