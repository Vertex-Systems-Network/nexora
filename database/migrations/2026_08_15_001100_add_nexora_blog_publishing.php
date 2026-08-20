<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_author_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('display_name', 180);
            $table->string('slug', 180)->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_url', 2048)->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->json('social_links')->nullable();
            $table->json('expertise')->nullable();
            $table->boolean('is_public')->default(true)->index();
            $table->timestamps();
            $table->index(['user_id', 'is_public'], 'nx_author_user_public_idx');
        });

        Schema::create('nx_taxonomy_terms', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('taxonomy', 40)->index();
            $table->string('name', 180);
            $table->string('slug', 180);
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('nx_taxonomy_terms')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['taxonomy', 'slug'], 'nx_taxonomy_slug_uq');
            $table->index(['taxonomy', 'sort_order'], 'nx_taxonomy_sort_idx');
        });

        Schema::create('nx_document_terms', function (Blueprint $table): void {
            $table->foreignId('document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('nx_taxonomy_terms')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->primary(['document_id', 'term_id']);
            $table->index(['term_id', 'document_id'], 'nx_doc_terms_term_doc_idx');
        });

        Schema::create('nx_content_series', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 200);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_content_series_items', function (Blueprint $table): void {
            $table->foreignId('series_id')->constrained('nx_content_series')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->primary(['series_id', 'document_id']);
            $table->index(['series_id', 'position'], 'nx_series_position_idx');
        });

        Schema::create('nx_article_metadata', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained('nx_documents')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('featured_until')->nullable();
            $table->string('hero_image_url', 2048)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->boolean('allow_comments')->default(false);
            $table->boolean('is_sponsored')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_document_authors', function (Blueprint $table): void {
            $table->foreignId('document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->foreignId('author_profile_id')->constrained('nx_author_profiles')->cascadeOnDelete();
            $table->string('role', 40)->default('author');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->primary(['document_id', 'author_profile_id']);
            $table->index(['author_profile_id', 'document_id'], 'nx_doc_authors_author_doc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_document_authors');
        Schema::dropIfExists('nx_article_metadata');
        Schema::dropIfExists('nx_content_series_items');
        Schema::dropIfExists('nx_content_series');
        Schema::dropIfExists('nx_document_terms');
        Schema::dropIfExists('nx_taxonomy_terms');
        Schema::dropIfExists('nx_author_profiles');
    }
};
