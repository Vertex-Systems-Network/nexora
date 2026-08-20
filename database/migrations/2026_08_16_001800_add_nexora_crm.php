<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_crm_organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 200)->index();
            $table->string('domain', 190)->nullable()->index();
            $table->string('website', 2048)->nullable();
            $table->string('industry', 120)->nullable()->index();
            $table->string('email', 255)->nullable()->index();
            $table->string('phone', 80)->nullable();
            $table->string('lifecycle_stage', 40)->default('prospect')->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->json('address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_crm_contacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable()->index();
            $table->string('first_name', 120);
            $table->string('last_name', 120)->nullable();
            $table->string('display_name', 240)->index();
            $table->string('email', 255)->nullable()->index();
            $table->string('phone', 80)->nullable();
            $table->string('mobile', 80)->nullable();
            $table->string('job_title', 160)->nullable();
            $table->string('lifecycle_stage', 40)->default('lead')->index();
            $table->string('source', 120)->nullable()->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('organization_id', 'nx_crm_contacts_org_fk')->references('id')->on('nx_crm_organizations')->nullOnDelete();
        });

        Schema::create('nx_crm_pipelines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('nx_crm_pipeline_stages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('pipeline_id');
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->unsignedInteger('position')->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();
            $table->foreign('pipeline_id', 'nx_crm_stages_pipeline_fk')->references('id')->on('nx_crm_pipelines')->cascadeOnDelete();
            $table->unique(['pipeline_id', 'slug'], 'nx_crm_stage_pipeline_slug_uq');
            $table->index(['pipeline_id', 'position'], 'nx_crm_stage_pipeline_pos_idx');
        });

        Schema::create('nx_crm_opportunities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('pipeline_id');
            $table->uuid('stage_id');
            $table->uuid('organization_id')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->string('name', 220)->index();
            $table->string('status', 32)->default('open')->index();
            $table->char('currency', 3)->nullable()->index();
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->string('source', 120)->nullable()->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expected_close_at')->nullable()->index();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('pipeline_id', 'nx_crm_opp_pipeline_fk')->references('id')->on('nx_crm_pipelines')->restrictOnDelete();
            $table->foreign('stage_id', 'nx_crm_opp_stage_fk')->references('id')->on('nx_crm_pipeline_stages')->restrictOnDelete();
            $table->foreign('organization_id', 'nx_crm_opp_org_fk')->references('id')->on('nx_crm_organizations')->nullOnDelete();
            $table->foreign('contact_id', 'nx_crm_opp_contact_fk')->references('id')->on('nx_crm_contacts')->nullOnDelete();
        });

        Schema::create('nx_crm_leads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->uuid('converted_opportunity_id')->nullable();
            $table->string('title', 220)->index();
            $table->string('status', 32)->default('new')->index();
            $table->string('source', 120)->nullable()->index();
            $table->unsignedTinyInteger('score')->default(0)->index();
            $table->char('currency', 3)->nullable();
            $table->unsignedBigInteger('estimated_value_minor')->default(0);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->foreign('organization_id', 'nx_crm_leads_org_fk')->references('id')->on('nx_crm_organizations')->nullOnDelete();
            $table->foreign('contact_id', 'nx_crm_leads_contact_fk')->references('id')->on('nx_crm_contacts')->nullOnDelete();
            $table->foreign('converted_opportunity_id', 'nx_crm_leads_opp_fk')->references('id')->on('nx_crm_opportunities')->nullOnDelete();
        });

        Schema::create('nx_crm_activities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 40)->index();
            $table->uuid('subject_id')->index();
            $table->string('type', 40)->index();
            $table->string('title', 220);
            $table->text('body')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->string('external_provider', 120)->nullable();
            $table->string('external_reference', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'nx_crm_activity_subject_time_idx');
        });

        Schema::create('nx_crm_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 40)->index();
            $table->uuid('subject_id')->index();
            $table->text('body');
            $table->boolean('pinned')->default(false)->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id', 'created_at'], 'nx_crm_notes_subject_time_idx');
        });

        Schema::create('nx_crm_timeline_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 40)->index();
            $table->uuid('subject_id')->index();
            $table->string('event_type', 120)->index();
            $table->string('title', 220);
            $table->text('summary')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'nx_crm_timeline_subject_time_idx');
        });

        Schema::create('nx_crm_opportunity_stage_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('opportunity_id');
            $table->uuid('from_stage_id')->nullable();
            $table->uuid('to_stage_id');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent()->index();
            $table->foreign('opportunity_id', 'nx_crm_stage_hist_opp_fk')->references('id')->on('nx_crm_opportunities')->cascadeOnDelete();
            $table->foreign('from_stage_id', 'nx_crm_stage_hist_from_fk')->references('id')->on('nx_crm_pipeline_stages')->nullOnDelete();
            $table->foreign('to_stage_id', 'nx_crm_stage_hist_to_fk')->references('id')->on('nx_crm_pipeline_stages')->restrictOnDelete();
        });

        Schema::create('nx_crm_custom_field_definitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('entity_type', 40)->index();
            $table->string('key', 120);
            $table->string('label', 180);
            $table->string('field_type', 40)->index();
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['entity_type', 'key'], 'nx_crm_custom_field_key_uq');
        });

        Schema::create('nx_crm_custom_field_values', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('field_id');
            $table->string('entity_type', 40)->index();
            $table->uuid('entity_id')->index();
            $table->json('value');
            $table->timestamps();
            $table->foreign('field_id', 'nx_crm_custom_value_field_fk')->references('id')->on('nx_crm_custom_field_definitions')->cascadeOnDelete();
            $table->unique(['field_id', 'entity_type', 'entity_id'], 'nx_crm_custom_value_uq');
        });

        Schema::create('nx_crm_commerce_links', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('contact_id')->nullable();
            $table->uuid('organization_id')->nullable();
            $table->uuid('commerce_customer_id')->unique();
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamps();
            $table->foreign('contact_id', 'nx_crm_commerce_contact_fk')->references('id')->on('nx_crm_contacts')->cascadeOnDelete();
            $table->foreign('organization_id', 'nx_crm_commerce_org_fk')->references('id')->on('nx_crm_organizations')->cascadeOnDelete();
            $table->foreign('commerce_customer_id', 'nx_crm_commerce_customer_fk')->references('id')->on('nx_commerce_customers')->cascadeOnDelete();
            $table->index(['contact_id', 'organization_id'], 'nx_crm_commerce_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_crm_commerce_links');
        Schema::dropIfExists('nx_crm_custom_field_values');
        Schema::dropIfExists('nx_crm_custom_field_definitions');
        Schema::dropIfExists('nx_crm_opportunity_stage_history');
        Schema::dropIfExists('nx_crm_timeline_events');
        Schema::dropIfExists('nx_crm_notes');
        Schema::dropIfExists('nx_crm_activities');
        Schema::dropIfExists('nx_crm_leads');
        Schema::dropIfExists('nx_crm_opportunities');
        Schema::dropIfExists('nx_crm_pipeline_stages');
        Schema::dropIfExists('nx_crm_pipelines');
        Schema::dropIfExists('nx_crm_contacts');
        Schema::dropIfExists('nx_crm_organizations');
    }
};
