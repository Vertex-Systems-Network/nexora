<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 80)->default('general')->index();
            $table->string('key', 160)->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('nx_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('nx_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->string('group', 80)->default('general')->index();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('nx_role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('nx_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('nx_permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('nx_user_roles', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('nx_roles')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('nx_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 180)->unique();
            $table->string('name', 120);
            $table->string('group', 80)->index();
            $table->string('risk_level', 20)->default('normal');
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('nx_modules', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier', 160)->unique();
            $table->string('name', 120);
            $table->string('version', 40);
            $table->string('status', 30)->default('inactive')->index();
            $table->boolean('is_core')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_module_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('nx_modules')->cascadeOnDelete();
            $table->string('version', 40);
            $table->string('checksum', 128)->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['module_id', 'version']);
        });

        Schema::create('nx_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 160)->index();
            $table->string('subject_type', 180)->nullable();
            $table->string('subject_id', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->string('request_id', 100)->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('nx_system_health', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 160)->unique();
            $table->string('status', 30)->index();
            $table->string('message', 500)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_system_health');
        Schema::dropIfExists('nx_audit_logs');
        Schema::dropIfExists('nx_module_versions');
        Schema::dropIfExists('nx_modules');
        Schema::dropIfExists('nx_capabilities');
        Schema::dropIfExists('nx_user_roles');
        Schema::dropIfExists('nx_role_permissions');
        Schema::dropIfExists('nx_permissions');
        Schema::dropIfExists('nx_roles');
        Schema::dropIfExists('nx_settings');
    }
};
