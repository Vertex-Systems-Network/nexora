<?php

declare(strict_types=1);

use App\Nexora\Foundation\Database\PortableNullableUnique;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_trusted_publishers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 180);
            $table->string('key_id', 160)->unique();
            $table->string('algorithm', 32)->default('ed25519');
            $table->text('public_key');
            $table->char('fingerprint_sha256', 64)->unique();
            $table->string('trust_tier', 24)->default('verified')->index();
            $table->string('status', 24)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nx_supply_chain_artifacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('quarantine_package_id')->nullable()->index();
            $table->uuid('scan_id')->nullable();
            $table->string('package_identifier', 180)->nullable()->index();
            $table->string('package_version', 64)->nullable();
            $table->char('artifact_sha256', 64)->index();
            $table->char('content_sha256', 64)->index();
            $table->uuid('publisher_id')->nullable()->index();
            $table->string('signature_status', 24)->default('missing')->index();
            $table->string('provenance_status', 24)->default('missing')->index();
            $table->string('trust_tier', 24)->default('untrusted')->index();
            $table->string('sandbox_profile', 40)->default('deny-execution')->index();
            $table->string('sbom_format', 40)->nullable();
            $table->string('sbom_version', 20)->nullable();
            $table->json('sbom')->nullable();
            $table->json('provenance')->nullable();
            $table->text('verification_error')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('quarantine_package_id', 'nx_supply_artifact_package_fk')->references('id')->on('nx_quarantine_packages')->cascadeOnDelete();
            $table->foreign('scan_id', 'nx_supply_artifact_scan_fk')->references('id')->on('nx_security_scans')->cascadeOnDelete();
            $table->foreign('publisher_id', 'nx_supply_artifact_publisher_fk')->references('id')->on('nx_trusted_publishers')->nullOnDelete();
        });

        PortableNullableUnique::create('nx_supply_chain_artifacts', 'scan_id', 'nx_supply_artifact_scan_uq');

        Schema::create('nx_supply_chain_components', function (Blueprint $table): void {
            $table->id();
            $table->uuid('artifact_id');
            $table->string('ecosystem', 40)->index();
            $table->string('name', 255);
            $table->string('version', 120)->nullable();
            $table->string('scope', 32)->default('runtime')->index();
            $table->boolean('is_direct')->default(false)->index();
            $table->string('purl', 700)->nullable();
            $table->json('licenses')->nullable();
            $table->json('hashes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('artifact_id', 'nx_supply_component_artifact_fk')->references('id')->on('nx_supply_chain_artifacts')->cascadeOnDelete();
            $table->index(['artifact_id', 'ecosystem'], 'nx_supply_component_ecosystem_idx');
        });

        Schema::create('nx_supply_chain_attestations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('artifact_id');
            $table->string('kind', 40)->index();
            $table->string('predicate_type', 180)->nullable();
            $table->char('subject_sha256', 64)->nullable()->index();
            $table->string('issuer', 180)->nullable();
            $table->boolean('verified')->default(false)->index();
            $table->json('payload')->nullable();
            $table->text('verification_error')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('artifact_id', 'nx_supply_attestation_artifact_fk')->references('id')->on('nx_supply_chain_artifacts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_supply_chain_attestations');
        Schema::dropIfExists('nx_supply_chain_components');
        Schema::dropIfExists('nx_supply_chain_artifacts');
        Schema::dropIfExists('nx_trusted_publishers');
    }
};
