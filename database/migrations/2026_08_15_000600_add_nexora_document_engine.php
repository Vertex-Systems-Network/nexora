<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('type', 64)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('title');
            $table->string('slug')->nullable()->index();
            $table->text('excerpt')->nullable();
            $table->json('content');
            $table->json('metadata')->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['type', 'status']);
        });

        Schema::create('nx_document_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->json('content');
            $table->json('metadata')->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['document_id', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_document_revisions');
        Schema::dropIfExists('nx_documents');
    }
};
