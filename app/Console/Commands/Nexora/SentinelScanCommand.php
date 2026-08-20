<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Security\Sentinel\Contracts\PackageScannerContract;
use Illuminate\Console\Command;

final class SentinelScanCommand extends Command
{
    protected $signature = 'nexora:sentinel:scan {path : Absolute or project-relative ZIP path} {--json : Print machine-readable JSON}';
    protected $description = 'Scan a Nexora package with Sentinel without executing or installing it.';

    public function handle(PackageScannerContract $scanner): int
    {
        $input = (string) $this->argument('path');
        $path = $this->absolutePath($input);

        try {
            $report = $scanner->scan($path);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'source' => $report->sourceName,
                'sha256' => $report->sourceSha256,
                'decision' => $report->decision->value,
                'risk_score' => $report->riskScore,
                'manifest' => $report->manifest,
                'metrics' => $report->metrics,
                'severity' => $report->severityCounts(),
                'findings' => array_map(static fn ($finding): array => $finding->toArray(), $report->findings),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->newLine();
            $this->components->twoColumnDetail('Decision', strtoupper($report->decision->value));
            $this->components->twoColumnDetail('Risk score', $report->riskScore.'/100');
            $this->components->twoColumnDetail('SHA-256', $report->sourceSha256);
            $this->components->twoColumnDetail('Findings', (string) count($report->findings));
            $this->newLine();

            foreach ($report->findings as $finding) {
                $location = $finding->filePath ? $finding->filePath.($finding->lineStart ? ':'.$finding->lineStart : '') : 'package';
                $this->line(sprintf('[%s] %s %s — %s', strtoupper($finding->severity->value), $finding->ruleId, $location, $finding->title));
            }
        }

        return $report->decision->value === 'allow' ? self::SUCCESS : self::FAILURE;
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
