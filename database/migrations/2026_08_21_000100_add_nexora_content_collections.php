<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_content_collections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index('nx_col_tenant_idx');
            $table->uuid('uuid')->unique('nx_col_uuid_uq');
            $table->string('name', 180);
            $table->string('slug', 190);
            $table->text('description')->nullable();
            $table->string('status', 24)->default('active')->index('nx_col_status_idx');
            $table->string('document_type', 80)->nullable()->index('nx_col_doc_type_idx');
            $table->json('schema')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('tenant_id', 'nx_col_tenant_fk')->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            $table->unique(['tenant_id', 'slug'], 'nx_col_tenant_slug_uq');
        });

        Schema::create('nx_content_collection_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained('nx_content_collections')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['collection_id', 'document_id'], 'nx_col_doc_pair_uq');
            $table->index(['collection_id', 'position'], 'nx_col_doc_pos_idx');
        });

        $now = now();
        foreach ([
            ['name' => 'View Content Collections', 'slug' => 'collections.view', 'group' => 'content'],
            ['name' => 'Manage Content Collections', 'slug' => 'collections.manage', 'group' => 'content'],
        ] as $permission) {
            DB::table('nx_permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission + ['description' => null, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        $permissionIds = DB::table('nx_permissions')->whereIn('slug', ['collections.view', 'collections.manage'])->pluck('id');
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
        $permissionIds = DB::table('nx_permissions')->whereIn('slug', ['collections.view', 'collections.manage'])->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('nx_role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('nx_permissions')->whereIn('id', $permissionIds)->delete();
        }
        Schema::dropIfExists('nx_content_collection_documents');
        Schema::dropIfExists('nx_content_collections');
    }
};
