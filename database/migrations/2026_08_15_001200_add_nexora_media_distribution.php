<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_media_folders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('nx_media_folders')->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 180);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['parent_id', 'sort_order'], 'nx_media_folder_parent_sort_idx');
            $table->unique(['parent_id', 'slug'], 'nx_media_folder_parent_slug_uq');
        });

        Schema::create('nx_media_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('folder_id')->nullable()->constrained('nx_media_folders')->nullOnDelete();
            $table->string('disk', 80)->default('public');
            $table->string('visibility', 20)->default('public')->index();
            $table->string('media_type', 24)->index();
            $table->string('mime_type', 160)->index();
            $table->string('extension', 24)->nullable();
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('path', 700)->unique();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->char('checksum_sha256', 64)->index();
            $table->string('title', 255)->nullable();
            $table->string('alt_text', 500)->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->decimal('focal_x', 5, 2)->nullable();
            $table->decimal('focal_y', 5, 2)->nullable();
            $table->json('variants')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['folder_id', 'media_type', 'created_at'], 'nx_media_folder_type_created_idx');
        });

        Schema::create('nx_media_collections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 180);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nx_media_collection_items', function (Blueprint $table): void {
            $table->foreignId('collection_id')->constrained('nx_media_collections')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('nx_media_assets')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->primary(['collection_id', 'asset_id']);
            $table->index(['collection_id', 'position'], 'nx_media_collection_position_idx');
        });

        Schema::create('nx_media_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('nx_media_assets')->cascadeOnDelete();
            $table->string('resource_type', 80);
            $table->unsignedBigInteger('resource_id');
            $table->string('slot', 120);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['asset_id', 'resource_type', 'resource_id', 'slot'], 'nx_media_usage_unique');
            $table->index(['resource_type', 'resource_id'], 'nx_media_usage_resource_idx');
        });

        Schema::table('nx_article_metadata', function (Blueprint $table): void {
            $table->foreignId('hero_media_id')->nullable()->constrained('nx_media_assets')->nullOnDelete();
        });

        Schema::create('nx_newsletter_lists', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 180);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_newsletter_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email', 320)->unique();
            $table->string('name', 180)->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->string('locale', 12)->default('en')->index();
            $table->string('consent_source', 120)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->char('unsubscribe_token', 64)->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_newsletter_list_subscribers', function (Blueprint $table): void {
            $table->foreignId('list_id')->constrained('nx_newsletter_lists')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('nx_newsletter_subscribers')->cascadeOnDelete();
            $table->string('status', 24)->default('subscribed')->index();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->primary(['list_id', 'subscriber_id']);
        });

        Schema::create('nx_newsletter_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 180);
            $table->string('subject', 255);
            $table->string('preview_text', 500)->nullable();
            $table->foreignId('document_id')->nullable()->constrained('nx_documents')->nullOnDelete();
            $table->foreignId('list_id')->nullable()->constrained('nx_newsletter_lists')->nullOnDelete();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_newsletter_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('nx_newsletter_campaigns')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('nx_newsletter_subscribers')->cascadeOnDelete();
            $table->string('status', 24)->default('queued')->index();
            $table->string('message_id', 255)->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'subscriber_id'], 'nx_newsletter_delivery_unique');
            $table->index(['campaign_id', 'status'], 'nx_newsletter_delivery_status_idx');
        });

        Schema::create('nx_distribution_channels', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('adapter_key', 80)->index();
            $table->string('name', 180);
            $table->boolean('enabled')->default(false)->index();
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_distribution_channels');
        Schema::dropIfExists('nx_newsletter_deliveries');
        Schema::dropIfExists('nx_newsletter_campaigns');
        Schema::dropIfExists('nx_newsletter_list_subscribers');
        Schema::dropIfExists('nx_newsletter_subscribers');
        Schema::dropIfExists('nx_newsletter_lists');
        Schema::table('nx_article_metadata', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('hero_media_id');
        });
        Schema::dropIfExists('nx_media_usages');
        Schema::dropIfExists('nx_media_collection_items');
        Schema::dropIfExists('nx_media_collections');
        Schema::dropIfExists('nx_media_assets');
        Schema::dropIfExists('nx_media_folders');
    }
};
