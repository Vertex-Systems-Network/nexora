<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_modules', function (Blueprint $table): void {
            $table->string('class', 255)->nullable();
            $table->unsignedSmallInteger('load_order')->default(100)->index();
            $table->string('trust_level', 30)->default('core')->index();
            $table->char('manifest_hash', 64)->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('last_booted_at')->nullable();
        });

        Schema::create('nx_module_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('nx_modules')->cascadeOnDelete();
            $table->string('dependency_identifier', 160);
            $table->string('version_constraint', 80)->default('*');
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            $table->unique(['module_id', 'dependency_identifier'], 'nx_module_dependency_unique');
            $table->index('dependency_identifier');
        });

        Schema::create('nx_module_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('nx_modules')->cascadeOnDelete();
            $table->foreignId('capability_id')->constrained('nx_capabilities')->cascadeOnDelete();
            $table->string('mode', 20)->default('requested');
            $table->timestamps();
            $table->unique(['module_id', 'capability_id', 'mode'], 'nx_module_capability_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_module_capabilities');
        Schema::dropIfExists('nx_module_dependencies');

        Schema::table('nx_modules', function (Blueprint $table): void {
            $table->dropColumn(['class', 'load_order', 'trust_level', 'manifest_hash', 'enabled_at', 'last_booted_at']);
        });
    }
};
