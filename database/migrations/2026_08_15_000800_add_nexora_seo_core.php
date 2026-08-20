<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_seo_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 80);
            $table->unsignedBigInteger('resource_id');
            $table->string('locale', 12)->default('en');
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('canonical_url')->nullable();
            $table->text('url_path')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->json('robots_directives')->nullable();
            $table->string('schema_type', 120)->default('WebPage');
            $table->json('schema_overrides')->nullable();
            $table->json('social')->nullable();
            $table->boolean('sitemap_include')->default(true);
            $table->string('indexing_state', 40)->default('eligible');
            $table->timestamp('last_indexed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['resource_type', 'resource_id', 'locale'], 'nx_seo_entries_resource_locale_unique');
            $table->index(['sitemap_include', 'robots_index'], 'nx_seo_entries_sitemap_index');
            $table->index('indexing_state');
        });

        Schema::create('nx_seo_schema_nodes', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 80)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('locale', 12)->default('en');
            $table->string('node_id', 255);
            $table->string('schema_type', 120);
            $table->json('properties');
            $table->string('source', 120)->default('core');
            $table->integer('priority')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['node_id', 'locale'], 'nx_seo_schema_nodes_id_locale_unique');
            $table->index(['resource_type', 'resource_id'], 'nx_seo_schema_nodes_resource_index');
        });

        Schema::create('nx_seo_internal_link_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->foreignId('target_document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->string('source_block_id', 80)->nullable();
            $table->string('anchor_text');
            $table->string('status', 30)->default('suggested');
            $table->text('reason')->nullable();
            $table->decimal('confidence', 5, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_document_id', 'status'], 'nx_seo_internal_links_source_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_seo_internal_link_suggestions');
        Schema::dropIfExists('nx_seo_schema_nodes');
        Schema::dropIfExists('nx_seo_entries');
    }
};
