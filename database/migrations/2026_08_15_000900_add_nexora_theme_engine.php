<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier', 160)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('inactive')->index();
            $table->foreignId('current_version_id')->nullable()->index();
            $table->boolean('is_builtin')->default(false)->index();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nx_theme_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained('nx_themes')->cascadeOnDelete();
            $table->string('version', 64);
            $table->string('engine', 40)->default('nexora-safe-html');
            $table->string('install_path', 1024);
            $table->string('asset_base_path', 1024)->nullable();
            $table->string('sha256', 64);
            $table->json('manifest');
            $table->string('source_type', 40)->default('package');
            $table->string('source_scan_id', 36)->nullable()->index();
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
            $table->unique(['theme_id', 'version']);
        });

        Schema::table('nx_themes', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('nx_theme_versions')->nullOnDelete();
        });

        Schema::create('nx_theme_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained('nx_themes')->cascadeOnDelete();
            $table->foreignId('theme_version_id')->nullable()->constrained('nx_theme_versions')->cascadeOnDelete();
            $table->string('key', 160);
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['theme_id', 'key']);
        });

        Schema::create('nx_theme_activations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained('nx_themes')->cascadeOnDelete();
            $table->foreignId('theme_version_id')->constrained('nx_theme_versions')->cascadeOnDelete();
            $table->foreignId('previous_theme_id')->nullable()->constrained('nx_themes')->nullOnDelete();
            $table->foreignId('previous_theme_version_id')->nullable()->constrained('nx_theme_versions')->nullOnDelete();
            $table->string('action', 32)->default('activate');
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nx_theme_preview_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('theme_version_id')->constrained('nx_theme_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_theme_preview_tokens');
        Schema::dropIfExists('nx_theme_activations');
        Schema::dropIfExists('nx_theme_settings');
        Schema::table('nx_themes', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('nx_theme_versions');
        Schema::dropIfExists('nx_themes');
    }
};
