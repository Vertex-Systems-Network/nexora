<?php

declare(strict_types=1);

use App\Nexora\Foundation\Database\PortableNullableUnique;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_workflows', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 180);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->string('trigger_key', 120)->index();
            $table->json('trigger_config')->nullable();
            $table->json('conditions')->nullable();
            $table->json('actions');
            $table->unsignedBigInteger('run_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'trigger_key'], 'nx_workflow_status_trigger_idx');
        });

        Schema::create('nx_automation_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event_key', 120)->index();
            $table->string('source_type', 120)->nullable();
            $table->string('source_id', 120)->nullable();
            $table->string('idempotency_key', 190)->nullable();
            $table->json('payload');
            $table->timestamp('occurred_at')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id', 'occurred_at'], 'nx_automation_event_source_idx');
        });

        PortableNullableUnique::create('nx_automation_events', 'idempotency_key', 'nx_automation_event_idempotency_uq');

        Schema::create('nx_workflow_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('nx_workflows')->cascadeOnDelete();
            $table->foreignId('automation_event_id')->nullable()->constrained('nx_automation_events')->nullOnDelete();
            $table->string('status', 24)->default('queued')->index();
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->json('context')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['workflow_id', 'created_at'], 'nx_workflow_run_workflow_created_idx');
        });

        Schema::create('nx_workflow_step_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained('nx_workflow_runs')->cascadeOnDelete();
            $table->string('step_key', 80);
            $table->string('action_type', 80);
            $table->string('status', 24)->default('queued')->index();
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['workflow_run_id', 'step_key'], 'nx_workflow_step_unique');
        });

        Schema::create('nx_webhook_destinations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 180);
            $table->string('url', 1500);
            $table->text('secret');
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedSmallInteger('timeout_seconds')->default(10);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->json('headers')->nullable();
            $table->timestamp('last_delivered_at')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nx_webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 180);
            $table->string('slug', 180)->unique();
            $table->text('secret');
            $table->text('previous_secret')->nullable();
            $table->timestamp('previous_secret_valid_until')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->json('allowed_ips')->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nx_webhook_receipts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('webhook_endpoint_id')->constrained('nx_webhook_endpoints')->cascadeOnDelete();
            $table->string('idempotency_key', 190);
            $table->char('payload_hash', 64);
            $table->char('source_hash', 64)->nullable();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->string('status', 24)->default('accepted')->index();
            $table->timestamp('received_at');
            $table->timestamps();
            $table->unique(['webhook_endpoint_id', 'idempotency_key'], 'nx_webhook_receipt_idempotency_uq');
        });

        Schema::create('nx_webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('webhook_destination_id')->constrained('nx_webhook_destinations')->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('nx_workflow_runs')->nullOnDelete();
            $table->string('event_key', 120)->index();
            $table->string('idempotency_key', 190)->unique();
            $table->json('payload');
            $table->string('status', 24)->default('queued')->index();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['webhook_destination_id', 'created_at'], 'nx_webhook_delivery_destination_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_webhook_deliveries');
        Schema::dropIfExists('nx_webhook_receipts');
        Schema::dropIfExists('nx_webhook_endpoints');
        Schema::dropIfExists('nx_webhook_destinations');
        Schema::dropIfExists('nx_workflow_step_runs');
        Schema::dropIfExists('nx_workflow_runs');
        Schema::dropIfExists('nx_automation_events');
        Schema::dropIfExists('nx_workflows');
    }
};
