<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SECURITY-BASELINE-200 / SYS-MFA-PASSKEY: one registered WebAuthn credential. */
final class Passkey extends Model
{
    protected $table = 'nx_passkeys';

    protected $fillable = ['user_id', 'name', 'credential_id', 'credential_record', 'last_used_at'];

    protected $hidden = ['credential_record'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
