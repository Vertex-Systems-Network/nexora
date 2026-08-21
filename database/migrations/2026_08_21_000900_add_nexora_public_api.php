<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_api_access_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('token_hash', 64)->unique();
            $table->string('token_hint', 32);
            $table->json('abilities');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('tenant_id', 'nx_api_token_tenant_fk')
                ->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
            $table->index(['tenant_id', 'user_id', 'revoked_at'], 'nx_api_token_tenant_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_api_access_tokens');
    }
};
