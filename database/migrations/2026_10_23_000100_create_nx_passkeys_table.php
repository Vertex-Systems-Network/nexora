<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SECURITY-BASELINE-200 / SYS-MFA-PASSKEY: WebAuthn credentials. Public keys only; no secret material is stored.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_passkeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            // base64url of the raw credential id; the authenticator-chosen lookup key at sign-in.
            $table->string('credential_id', 512)->unique('nx_passkeys_credential_unique');
            // Serialized WebAuthn credential record (public key, counter, aaguid, backup flags).
            $table->text('credential_record');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at'], 'nx_passkeys_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_passkeys');
    }
};
