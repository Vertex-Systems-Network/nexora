<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_forms', function (Blueprint $table): void {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index('nx_forms_tenant_idx');
            $table->uuid('uuid')->unique('nx_forms_uuid_uq');
            $table->string('name', 180);
            $table->string('slug', 190);
            $table->text('description')->nullable();
            $table->string('status', 24)->default('draft')->index('nx_forms_status_idx');
            $table->json('fields');
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('submission_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('tenant_id', 'nx_forms_tenant_fk')->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            $table->unique(['tenant_id', 'slug'], 'nx_forms_tenant_slug_uq');
        });

        Schema::create('nx_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index('nx_form_sub_tenant_idx');
            $table->uuid('uuid')->unique('nx_form_sub_uuid_uq');
            $table->foreignId('form_id')->constrained('nx_forms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('received')->index('nx_form_sub_status_idx');
            $table->json('values');
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at')->index('nx_form_submitted_idx');
            $table->timestamps();
            $table->foreign('tenant_id', 'nx_form_sub_tenant_fk')->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            $table->index(['form_id', 'submitted_at'], 'nx_form_sub_form_date_idx');
        });

        $now = now();
        foreach ([
            ['name' => 'View Forms', 'slug' => 'forms.view', 'group' => 'forms'],
            ['name' => 'Manage Forms', 'slug' => 'forms.manage', 'group' => 'forms'],
            ['name' => 'View Form Submissions', 'slug' => 'forms.submissions.view', 'group' => 'forms'],
        ] as $permission) {
            DB::table('nx_permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission + ['description' => null, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        $permissionIds = DB::table('nx_permissions')
            ->whereIn('slug', ['forms.view', 'forms.manage', 'forms.submissions.view'])
            ->pluck('id');
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
        $permissionIds = DB::table('nx_permissions')
            ->whereIn('slug', ['forms.view', 'forms.manage', 'forms.submissions.view'])
            ->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('nx_role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('nx_permissions')->whereIn('id', $permissionIds)->delete();
        }

        Schema::dropIfExists('nx_form_submissions');
        Schema::dropIfExists('nx_forms');
    }
};
