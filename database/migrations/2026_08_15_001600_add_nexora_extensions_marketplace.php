<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_extensions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('identifier', 180)->unique();
            $table->string('name', 180);
            $table->string('type', 40)->index();
            $table->string('status', 32)->default('installed')->index();
            $table->string('current_version', 64)->nullable();
            $table->uuid('publisher_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
            $table->timestamps();
            $table->foreign('publisher_id', 'nx_extensions_publisher_fk')->references('id')->on('nx_trusted_publishers')->nullOnDelete();
        });

        Schema::create('nx_extension_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('extension_id');
            $table->uuid('artifact_id')->nullable()->index();
            $table->string('version', 64);
            $table->string('state', 32)->default('installed')->index();
            $table->char('content_sha256', 64);
            $table->string('install_path', 700);
            $table->string('compatibility_status', 32)->default('compatible')->index();
            $table->string('runtime_mode', 32)->default('declarative')->index();
            $table->string('migration_policy', 32)->default('none');
            $table->boolean('schema_compatible_rollback')->default(false);
            $table->json('manifest');
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('migrations_applied_at')->nullable();
            $table->timestamps();
            $table->foreign('extension_id', 'nx_extension_versions_extension_fk')->references('id')->on('nx_extensions')->cascadeOnDelete();
            $table->foreign('artifact_id', 'nx_extension_versions_artifact_fk')->references('id')->on('nx_supply_chain_artifacts')->nullOnDelete();
            $table->unique(['extension_id', 'version'], 'nx_extension_versions_unique');
        });

        Schema::create('nx_extension_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('extension_version_id');
            $table->string('dependency_identifier', 180)->index();
            $table->string('version_constraint', 80)->default('*');
            $table->boolean('optional')->default(false);
            $table->timestamps();
            $table->foreign('extension_version_id', 'nx_extension_dependencies_version_fk')->references('id')->on('nx_extension_versions')->cascadeOnDelete();
            $table->unique(['extension_version_id', 'dependency_identifier'], 'nx_extension_dependency_unique');
        });

        Schema::create('nx_extension_capability_grants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('extension_id');
            $table->string('capability_slug', 180)->index();
            $table->boolean('granted')->default(false)->index();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->foreign('extension_id', 'nx_extension_grants_extension_fk')->references('id')->on('nx_extensions')->cascadeOnDelete();
            $table->unique(['extension_id', 'capability_slug'], 'nx_extension_grant_unique');
        });

        Schema::create('nx_extension_lifecycle_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('extension_id');
            $table->uuid('extension_version_id')->nullable();
            $table->string('event', 64)->index();
            $table->string('status', 32)->default('completed')->index();
            $table->json('context')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('extension_id', 'nx_extension_events_extension_fk')->references('id')->on('nx_extensions')->cascadeOnDelete();
            $table->foreign('extension_version_id', 'nx_extension_events_version_fk')->references('id')->on('nx_extension_versions')->nullOnDelete();
        });

        Schema::create('nx_marketplace_sources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 180);
            $table->string('base_url', 700)->unique();
            $table->string('status', 32)->default('active')->index();
            $table->boolean('trusted_publishers_only')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nx_marketplace_catalog_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('source_id');
            $table->string('package_identifier', 180)->index();
            $table->string('name', 180);
            $table->string('type', 40)->index();
            $table->string('latest_version', 64);
            $table->text('description')->nullable();
            $table->string('publisher_key_id', 160)->nullable()->index();
            $table->string('artifact_url', 1000);
            $table->char('artifact_sha256', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->foreign('source_id', 'nx_marketplace_items_source_fk')->references('id')->on('nx_marketplace_sources')->cascadeOnDelete();
            $table->unique(['source_id', 'package_identifier'], 'nx_marketplace_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_marketplace_catalog_items');
        Schema::dropIfExists('nx_marketplace_sources');
        Schema::dropIfExists('nx_extension_lifecycle_events');
        Schema::dropIfExists('nx_extension_capability_grants');
        Schema::dropIfExists('nx_extension_dependencies');
        Schema::dropIfExists('nx_extension_versions');
        Schema::dropIfExists('nx_extensions');
    }
};
