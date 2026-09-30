<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_content_migration_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 40);
            $table->string('source_name', 255);
            $table->string('source_path', 500);
            $table->string('source_hash', 64);
            $table->unsignedBigInteger('source_bytes');
            $table->string('status', 24)->default('queued');
            $table->unsignedBigInteger('cursor')->default(0);
            $table->unsignedBigInteger('processed_items')->default(0);
            $table->unsignedBigInteger('imported_items')->default(0);
            $table->unsignedBigInteger('skipped_items')->default(0);
            $table->unsignedBigInteger('failed_items')->default(0);
            $table->json('options')->nullable();
            $table->json('result')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id', 'nx_content_migration_run_tenant_fk')
                ->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
            $table->unique(['tenant_id', 'source_type', 'source_hash'], 'nx_content_migration_source_uq');
            $table->index(['tenant_id', 'status', 'created_at'], 'nx_content_migration_status_idx');
        });

        Schema::create('nx_content_migration_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('tenant_id');
            $table->uuid('migration_run_id');
            $table->string('source_key', 190);
            $table->string('source_kind', 60)->nullable();
            $table->string('source_hash', 64);
            $table->string('status', 24)->default('pending');
            $table->string('destination_type', 60)->nullable();
            $table->string('destination_id', 190)->nullable();
            $table->json('metadata')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id', 'nx_content_migration_item_tenant_fk')
                ->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
            $table->foreign('migration_run_id', 'nx_content_migration_item_run_fk')
                ->references('id')->on('nx_content_migration_runs')->cascadeOnDelete();
            $table->unique(['migration_run_id', 'source_key'], 'nx_content_migration_item_source_uq');
            $table->index(['tenant_id', 'status', 'id'], 'nx_content_migration_item_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_content_migration_items');
        Schema::dropIfExists('nx_content_migration_runs');
    }
};
