<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_audit_logs', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable();
            $table->foreign('tenant_id', 'nx_audit_tenant_fk')
                ->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            $table->index(['tenant_id', 'created_at'], 'nx_audit_tenant_created_idx');
        });

        if (Schema::hasTable('nx_enterprise_organization_members')) {
            DB::table('nx_audit_logs')
                ->whereNull('tenant_id')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $tenantIds = DB::table('nx_enterprise_organization_members')
                            ->where('user_id', $row->user_id)
                            ->where('status', 'active')
                            ->pluck('organization_id')
                            ->filter(static fn ($value): bool => is_string($value) && $value !== '')
                            ->unique()
                            ->values();

                        if ($tenantIds->count() === 1) {
                            DB::table('nx_audit_logs')
                                ->where('id', $row->id)
                                ->whereNull('tenant_id')
                                ->update(['tenant_id' => $tenantIds->first()]);
                        }
                    }
                }, 'id');
        }

        Schema::create('nx_observability_incidents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_id', 100)->nullable()->index();
            $table->string('category', 40)->index();
            $table->string('severity', 20)->index();
            $table->string('code', 80)->index();
            $table->string('route_name', 180)->nullable();
            $table->string('method', 12)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('node_key', 190)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();

            $table->foreign('tenant_id', 'nx_obs_incident_tenant_fk')
                ->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            $table->index(['tenant_id', 'occurred_at'], 'nx_obs_incident_tenant_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_observability_incidents');

        Schema::table('nx_audit_logs', function (Blueprint $table): void {
            $table->dropForeign('nx_audit_tenant_fk');
            $table->dropIndex('nx_audit_tenant_created_idx');
            $table->dropColumn('tenant_id');
        });
    }
};
