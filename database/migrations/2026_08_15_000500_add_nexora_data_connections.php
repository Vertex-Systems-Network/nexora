<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_data_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('provider', 80)->index();
            $table->string('driver', 80)->index();
            $table->string('purpose', 40)->default('auxiliary')->index();
            $table->string('status', 30)->default('unconfigured')->index();
            $table->boolean('is_enabled')->default(false)->index();
            $table->string('endpoint', 500)->nullable();
            $table->string('database', 180)->nullable();
            $table->string('username', 180)->nullable();
            $table->longText('secret_payload')->nullable();
            $table->json('options')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_data_connections');
    }
};
