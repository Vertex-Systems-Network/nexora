<?php

declare(strict_types=1);

namespace App\Nexora\Extensions\Services;

use App\Models\ExtensionVersion;
use App\Nexora\Security\SupplyChain\Contracts\SandboxAdapterContract;
use Illuminate\Database\Migrations\Migrator;
use RuntimeException;

final readonly class ExtensionMigrationRunner
{
    public function __construct(private Migrator $migrator, private SandboxAdapterContract $sandbox) {}

    public function apply(ExtensionVersion $version): void
    {
        if ($version->migration_policy !== 'forward-only' || $version->migrations_applied_at) return;
        $artifact=$version->artifact;
        if (! $artifact) throw new RuntimeException('Extension migrations require a verified supply-chain artifact.');
        $policy=$this->sandbox->evaluate($artifact);
        if (! $policy['execution_allowed']) throw new RuntimeException('Extension migration execution is blocked by supply-chain policy: '.$policy['reason']);
        $path=rtrim((string)$version->install_path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        if (! is_dir($path)) { $version->forceFill(['migrations_applied_at'=>now()])->save(); return; }
        $files=glob($path.DIRECTORY_SEPARATOR.'*.php') ?: [];
        if ($files===[]) { $version->forceFill(['migrations_applied_at'=>now()])->save(); return; }
        $this->migrator->run([$path], ['pretend'=>false,'step'=>true]);
        $version->forceFill(['migrations_applied_at'=>now()])->save();
    }
}
