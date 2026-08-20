<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_runtime_nodes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('node_key', 128)->unique();
            $table->string('hostname', 190)->nullable();
            $table->string('role', 48)->default('application')->index();
            $table->string('status', 32)->default('active')->index();
            $table->string('version', 40)->nullable();
            $table->string('environment', 40)->nullable();
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('nx_runtime_leases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 160)->unique();
            $table->string('owner_node_key', 128)->nullable()->index();
            $table->string('token', 64)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('heartbeat_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_runtime_metrics', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('node_key', 128)->nullable()->index();
            $table->string('metric', 160)->index();
            $table->decimal('value', 22, 6);
            $table->string('unit', 32)->default('count');
            $table->json('tags')->nullable();
            $table->timestamp('observed_at')->index();
            $table->index(['metric', 'observed_at'], 'nx_runtime_metric_time_idx');
        });

        Schema::create('nx_runtime_backup_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 40)->default('database')->index();
            $table->string('status', 32)->default('queued')->index();
            $table->string('driver', 64)->nullable();
            $table->string('storage_disk', 80)->nullable();
            $table->text('storage_path')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->json('manifest')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('nx_runtime_restore_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('backup_run_id');
            $table->string('status', 32)->default('planned')->index();
            $table->json('plan');
            $table->string('confirmation_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->foreign('backup_run_id', 'nx_runtime_restore_backup_fk')->references('id')->on('nx_runtime_backup_runs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_runtime_restore_plans');
        Schema::dropIfExists('nx_runtime_backup_runs');
        Schema::dropIfExists('nx_runtime_metrics');
        Schema::dropIfExists('nx_runtime_leases');
        Schema::dropIfExists('nx_runtime_nodes');
    }
};
