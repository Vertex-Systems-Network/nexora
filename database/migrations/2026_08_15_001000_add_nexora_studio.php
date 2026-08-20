<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_studio_canvases', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name');
            $table->string('scope', 32)->default('standalone')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('document_id')->nullable()->constrained('nx_documents')->nullOnDelete();
            $table->foreignId('theme_id')->nullable()->constrained('nx_themes')->nullOnDelete();
            $table->string('template_key', 100)->nullable();
            $table->json('content');
            $table->json('metadata')->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->unsignedInteger('lock_version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['scope', 'status']);
            $table->index(['document_id', 'status']);
        });

        Schema::create('nx_studio_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('canvas_id')->constrained('nx_studio_canvases')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->string('name');
            $table->json('content');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['canvas_id', 'revision']);
        });

        Schema::create('nx_studio_components', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name');
            $table->string('category', 64)->default('user')->index();
            $table->json('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_studio_components');
        Schema::dropIfExists('nx_studio_revisions');
        Schema::dropIfExists('nx_studio_canvases');
    }
};
