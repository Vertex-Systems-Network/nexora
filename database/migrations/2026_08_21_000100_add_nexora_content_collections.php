<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_content_collections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index('nx_col_tenant_idx');
            $table->uuid('uuid')->unique('nx_col_uuid_uq');
            $table->string('name', 180);
            $table->string('slug', 190);
            $table->text('description')->nullable();
            $table->string('status', 24)->default('active')->index('nx_col_status_idx');
            $table->string('document_type', 80)->nullable()->index('nx_col_doc_type_idx');
            $table->json('schema')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('tenant_id', 'nx_col_tenant_fk')->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            $table->unique(['tenant_id', 'slug'], 'nx_col_tenant_slug_uq');
        });

        Schema::create('nx_content_collection_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained('nx_content_collections')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['collection_id', 'document_id'], 'nx_col_doc_pair_uq');
            $table->index(['collection_id', 'position'], 'nx_col_doc_pos_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_content_collection_documents');
        Schema::dropIfExists('nx_content_collections');
    }
};
