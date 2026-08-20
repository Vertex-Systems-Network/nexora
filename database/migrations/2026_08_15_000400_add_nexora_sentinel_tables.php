<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_quarantine_packages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('original_name', 255);
            $table->string('stored_name', 255)->unique();
            $table->text('path');
            $table->char('sha256', 64)->index();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('mime_type', 150)->nullable();
            $table->string('status', 32)->default('quarantined')->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_security_scans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('quarantine_package_id')->nullable();
            $table->string('source_type', 32)->default('archive');
            $table->string('source_name', 255);
            $table->char('source_sha256', 64)->index();
            $table->string('engine_version', 32);
            $table->string('status', 32)->index();
            $table->string('decision', 32)->index();
            $table->unsignedTinyInteger('risk_score')->default(0)->index();
            $table->json('manifest')->nullable();
            $table->json('summary')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('quarantine_package_id', 'nx_scan_quarantine_fk')
                ->references('id')->on('nx_quarantine_packages')->cascadeOnDelete();
        });

        Schema::create('nx_security_findings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('scan_id');
            $table->string('rule_id', 64)->index();
            $table->string('severity', 16)->index();
            $table->string('category', 64)->index();
            $table->string('title', 255);
            $table->text('message');
            $table->string('file_path', 500)->nullable();
            $table->unsignedInteger('line_start')->nullable();
            $table->unsignedInteger('line_end')->nullable();
            $table->text('excerpt')->nullable();
            $table->boolean('hard_block')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('scan_id', 'nx_finding_scan_fk')
                ->references('id')->on('nx_security_scans')->cascadeOnDelete();
            $table->index(['scan_id', 'severity'], 'nx_finding_scan_severity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_security_findings');
        Schema::dropIfExists('nx_security_scans');
        Schema::dropIfExists('nx_quarantine_packages');
    }
};
