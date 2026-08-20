<?php

declare(strict_types=1);

use App\Nexora\Foundation\Database\PortableNullableUnique;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_membership_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 180);
            $table->string('slug', 190)->unique();
            $table->text('description')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->unsignedInteger('duration_days')->nullable();
            $table->uuid('commerce_price_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('commerce_price_id', 'nx_membership_plan_price_fk')->references('id')->on('nx_commerce_prices')->nullOnDelete();
        });

        PortableNullableUnique::create('nx_membership_plans', 'commerce_price_id', 'nx_membership_plan_price_uq');

        Schema::create('nx_membership_entitlements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->string('key', 160);
            $table->string('label', 180);
            $table->string('value_type', 32)->default('boolean');
            $table->json('value')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->foreign('plan_id', 'nx_membership_entitlement_plan_fk')->references('id')->on('nx_membership_plans')->cascadeOnDelete();
            $table->unique(['plan_id', 'key'], 'nx_membership_entitlement_key_uq');
        });

        Schema::create('nx_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('commerce_customer_id')->nullable()->index();
            $table->uuid('commerce_subscription_id')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('plan_id', 'nx_membership_plan_fk')->references('id')->on('nx_membership_plans')->restrictOnDelete();
            $table->foreign('commerce_customer_id', 'nx_membership_customer_fk')->references('id')->on('nx_commerce_customers')->nullOnDelete();
            $table->foreign('commerce_subscription_id', 'nx_membership_subscription_fk')->references('id')->on('nx_commerce_subscriptions')->nullOnDelete();
            $table->index(['user_id', 'status', 'ends_at'], 'nx_membership_user_status_idx');
        });

        PortableNullableUnique::create('nx_memberships', 'commerce_subscription_id', 'nx_membership_subscription_uq');

        Schema::create('nx_membership_access_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 180);
            $table->string('resource_type', 48)->index();
            $table->string('resource_id', 64)->index();
            $table->string('evaluation', 12)->default('any');
            $table->json('required_plan_ids')->nullable();
            $table->json('required_entitlements')->nullable();
            $table->string('unauthenticated_action', 24)->default('deny');
            $table->boolean('active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['resource_type', 'resource_id'], 'nx_membership_access_resource_uq');
        });

        Schema::create('nx_membership_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('membership_id')->nullable()->index();
            $table->string('event_type', 120)->index();
            $table->json('payload')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->foreign('membership_id', 'nx_membership_event_membership_fk')->references('id')->on('nx_memberships')->cascadeOnDelete();
        });

        Schema::create('nx_helpdesk_sla_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 180);
            $table->string('priority', 24)->nullable()->index();
            $table->unsignedInteger('first_response_minutes')->nullable();
            $table->unsignedInteger('resolution_minutes')->nullable();
            $table->json('business_hours')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('nx_helpdesk_tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference', 40)->unique();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('requester_contact_id')->nullable()->index();
            $table->uuid('commerce_customer_id')->nullable()->index();
            $table->string('requester_name', 180)->nullable();
            $table->string('requester_email', 255)->nullable()->index();
            $table->string('subject', 240)->index();
            $table->string('status', 24)->default('open')->index();
            $table->string('priority', 24)->default('normal')->index();
            $table->string('category', 120)->nullable()->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('sla_policy_id')->nullable()->index();
            $table->timestamp('first_response_due_at')->nullable()->index();
            $table->timestamp('resolution_due_at')->nullable()->index();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('first_response_breached')->default(false)->index();
            $table->boolean('resolution_breached')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('requester_contact_id', 'nx_helpdesk_ticket_contact_fk')->references('id')->on('nx_crm_contacts')->nullOnDelete();
            $table->foreign('commerce_customer_id', 'nx_helpdesk_ticket_customer_fk')->references('id')->on('nx_commerce_customers')->nullOnDelete();
            $table->foreign('sla_policy_id', 'nx_helpdesk_ticket_sla_fk')->references('id')->on('nx_helpdesk_sla_policies')->nullOnDelete();
            $table->index(['status', 'priority', 'created_at'], 'nx_helpdesk_ticket_queue_idx');
        });

        Schema::create('nx_helpdesk_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name', 180)->nullable();
            $table->string('author_email', 255)->nullable();
            $table->text('body');
            $table->boolean('is_internal')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('ticket_id', 'nx_helpdesk_message_ticket_fk')->references('id')->on('nx_helpdesk_tickets')->cascadeOnDelete();
            $table->index(['ticket_id', 'created_at'], 'nx_helpdesk_message_ticket_time_idx');
        });

        Schema::create('nx_helpdesk_ticket_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->string('event_type', 120)->index();
            $table->json('payload')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->foreign('ticket_id', 'nx_helpdesk_event_ticket_fk')->references('id')->on('nx_helpdesk_tickets')->cascadeOnDelete();
            $table->index(['ticket_id', 'occurred_at'], 'nx_helpdesk_event_ticket_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_helpdesk_ticket_events');
        Schema::dropIfExists('nx_helpdesk_messages');
        Schema::dropIfExists('nx_helpdesk_tickets');
        Schema::dropIfExists('nx_helpdesk_sla_policies');
        Schema::dropIfExists('nx_membership_events');
        Schema::dropIfExists('nx_membership_access_policies');
        Schema::dropIfExists('nx_memberships');
        Schema::dropIfExists('nx_membership_entitlements');
        Schema::dropIfExists('nx_membership_plans');
    }
};
