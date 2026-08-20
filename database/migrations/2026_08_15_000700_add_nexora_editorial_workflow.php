<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_documents', function (Blueprint $table): void {
            $table->string('workflow_status', 40)->default('draft')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('review_due_at')->nullable()->index();
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamp('autosaved_at')->nullable();
        });

        Schema::table('nx_document_revisions', function (Blueprint $table): void {
            $table->string('document_status', 32)->nullable();
            $table->string('workflow_status', 40)->nullable();
        });

        Schema::create('nx_document_autosaves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('base_lock_version');
            $table->unsignedInteger('base_revision');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('excerpt')->nullable();
            $table->json('content');
            $table->json('metadata')->nullable();
            $table->string('workflow_status', 40)->default('draft');
            $table->timestamp('saved_at')->useCurrent();
            $table->timestamps();
            $table->unique(['document_id', 'user_id']);
        });

        Schema::create('nx_document_review_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('nx_documents')->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained('nx_document_revisions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('open')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_document_review_comments');
        Schema::dropIfExists('nx_document_autosaves');

        Schema::table('nx_document_revisions', function (Blueprint $table): void {
            $table->dropColumn(['document_status', 'workflow_status']);
        });

        Schema::table('nx_documents', function (Blueprint $table): void {
            $table->dropIndex(['workflow_status']);
            $table->dropIndex(['review_due_at']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropColumn(['workflow_status', 'review_due_at', 'lock_version', 'autosaved_at']);
        });
    }
};
