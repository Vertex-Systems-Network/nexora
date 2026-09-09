<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_ai_connections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index('nx_ai_conn_tenant_idx');
            $table->uuid('uuid')->unique('nx_ai_conn_uuid_uq');
            $table->string('name', 180);
            $table->string('provider_key', 120)->index('nx_ai_conn_provider_idx');
            $table->string('model', 190);
            $table->boolean('enabled')->default(false)->index('nx_ai_conn_enabled_idx');
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('max_input_chars')->default(20000);
            $table->unsignedInteger('max_output_tokens')->default(2048);
            $table->unsignedInteger('daily_request_limit')->default(100);
            $table->string('last_health_status', 24)->nullable();
            $table->string('last_health_message', 500)->nullable();
            $table->timestamp('last_health_checked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('tenant_id', 'nx_ai_conn_tenant_fk')->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            $table->unique(['tenant_id', 'name'], 'nx_ai_conn_tenant_name_uq');
        });

        Schema::create('nx_ai_generation_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index('nx_ai_run_tenant_idx');
            $table->uuid('uuid')->unique('nx_ai_run_uuid_uq');
            $table->foreignId('ai_connection_id')->constrained('nx_ai_connections')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider_key', 120);
            $table->string('model', 190);
            $table->string('status', 24)->default('running')->index('nx_ai_run_status_idx');
            $table->char('prompt_sha256', 64);
            $table->unsignedInteger('prompt_chars');
            $table->unsignedInteger('requested_output_tokens');
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->char('output_sha256', 64)->nullable();
            $table->unsignedInteger('output_chars')->nullable();
            $table->string('provider_request_id', 255)->nullable();
            $table->string('error_code', 80)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('started_at')->index('nx_ai_run_started_idx');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id', 'nx_ai_run_tenant_fk')->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            $table->index(['ai_connection_id', 'started_at'], 'nx_ai_run_conn_date_idx');
        });

        $now = now();
        foreach ([
            ['name' => 'View AI Platform', 'slug' => 'ai.view', 'group' => 'ai'],
            ['name' => 'Manage AI Connections', 'slug' => 'ai.connections.manage', 'group' => 'ai'],
            ['name' => 'Generate with AI', 'slug' => 'ai.generate', 'group' => 'ai'],
        ] as $permission) {
            DB::table('nx_permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission + ['description' => null, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        $permissionIds = DB::table('nx_permissions')->whereIn('slug', ['ai.view', 'ai.connections.manage', 'ai.generate'])->pluck('id');
        $roleIds = DB::table('nx_roles')->whereIn('slug', ['super-admin', 'administrator'])->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('nx_role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('nx_permissions')->whereIn('slug', ['ai.view', 'ai.connections.manage', 'ai.generate'])->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('nx_role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('nx_permissions')->whereIn('id', $permissionIds)->delete();
        }
        Schema::dropIfExists('nx_ai_generation_runs');
        Schema::dropIfExists('nx_ai_connections');
    }
};
