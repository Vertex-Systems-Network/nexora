<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status', 20)->default('active')->index();
            $table->string('timezone', 80)->default('UTC');
            $table->string('locale', 12)->default('en');
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('nx_saved_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 120)->index();
            $table->string('name', 120);
            $table->boolean('is_default')->default(false);
            $table->json('state');
            $table->timestamps();
            $table->unique(['user_id', 'scope', 'name']);
        });

        Schema::create('nx_admin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('info')->index();
            $table->string('title', 160);
            $table->text('message')->nullable();
            $table->string('action_url', 500)->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_admin_notifications');
        Schema::dropIfExists('nx_saved_views');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'timezone', 'locale', 'last_login_at']);
        });
    }
};
