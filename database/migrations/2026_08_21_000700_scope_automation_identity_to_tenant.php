<?php

declare(strict_types=1);

use App\Nexora\Foundation\Database\PortableNullableUnique;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const WORKFLOWS = 'nx_workflows';
    private const WORKFLOW_GLOBAL_SLUG = 'nx_workflows_slug_unique';
    private const WORKFLOW_TENANT_SLUG = 'nx_workflow_tenant_slug_uq';

    private const EVENTS = 'nx_automation_events';
    private const EVENT_GLOBAL_IDEMPOTENCY = 'nx_automation_event_idempotency_uq';
    private const EVENT_TENANT_IDEMPOTENCY = 'nx_automation_event_tenant_idempotency_uq';

    private const STEPS = 'nx_workflow_step_runs';
    private const STEP_TENANT_INDEX = 'nx_workflow_step_tenant_run_idx';
    private const STEP_TENANT_FOREIGN = 'nx_workflow_step_tenant_fk';

    public function up(): void
    {
        Schema::table(self::STEPS, static function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable();
            $table->index(['tenant_id', 'workflow_run_id'], self::STEP_TENANT_INDEX);
            $table->foreign('tenant_id', self::STEP_TENANT_FOREIGN)
                ->references('id')
                ->on('nx_enterprise_organizations')
                ->nullOnDelete();
        });

        $this->backfillStepTenants();

        Schema::table(self::WORKFLOWS, static function (Blueprint $table): void {
            $table->dropUnique(self::WORKFLOW_GLOBAL_SLUG);
            $table->unique(['tenant_id', 'slug'], self::WORKFLOW_TENANT_SLUG);
        });

        PortableNullableUnique::drop(self::EVENTS, self::EVENT_GLOBAL_IDEMPOTENCY);
        PortableNullableUnique::createScoped(
            self::EVENTS,
            'tenant_id',
            'idempotency_key',
            self::EVENT_TENANT_IDEMPOTENCY,
        );
    }

    public function down(): void
    {
        PortableNullableUnique::drop(self::EVENTS, self::EVENT_TENANT_IDEMPOTENCY);
        PortableNullableUnique::create(
            self::EVENTS,
            'idempotency_key',
            self::EVENT_GLOBAL_IDEMPOTENCY,
        );

        Schema::table(self::WORKFLOWS, static function (Blueprint $table): void {
            $table->dropUnique(self::WORKFLOW_TENANT_SLUG);
            $table->unique('slug', self::WORKFLOW_GLOBAL_SLUG);
        });

        Schema::table(self::STEPS, static function (Blueprint $table): void {
            $table->dropForeign(self::STEP_TENANT_FOREIGN);
            $table->dropIndex(self::STEP_TENANT_INDEX);
            $table->dropColumn('tenant_id');
        });
    }

    private function backfillStepTenants(): void
    {
        DB::table(self::STEPS)
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(250, static function ($steps): void {
                $runIds = $steps->pluck('workflow_run_id')->filter()->unique()->values();
                if ($runIds->isEmpty()) {
                    return;
                }

                $runTenants = DB::table('nx_workflow_runs')
                    ->whereIn('id', $runIds)
                    ->pluck('tenant_id', 'id');

                foreach ($steps as $step) {
                    $tenantId = $runTenants->get($step->workflow_run_id);
                    if (! is_string($tenantId) || $tenantId === '') {
                        throw new \RuntimeException("Workflow step {$step->id} has no trustworthy parent-run tenant identity.");
                    }

                    DB::table(self::STEPS)
                        ->where('id', $step->id)
                        ->whereNull('tenant_id')
                        ->update(['tenant_id' => $tenantId]);
                }
            });
    }
};
