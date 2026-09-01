<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

final class EnterpriseSsoProvider extends Model
{
    use HasUuids;

    protected $table = 'nx_enterprise_sso_providers';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $hidden = ['secret_payload'];

    protected static function booted(): void
    {
        static::saving(function (self $provider): void {
            self::assertPublicConfiguration((array) ($provider->configuration ?? []));
        });
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'enforce_for_members' => 'boolean',
            'configuration' => 'array',
            'secret_payload' => 'encrypted:array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(EnterpriseOrganization::class, 'organization_id');
    }

    /** @param array<string|int,mixed> $configuration */
    private static function assertPublicConfiguration(array $configuration): void
    {
        $forbidden = [
            'secret',
            'client_secret',
            'shared_secret',
            'password',
            'private_key',
            'signing_key',
            'api_key',
            'access_token',
            'refresh_token',
            'bearer_token',
            'credential',
            'credentials',
        ];

        foreach ($configuration as $key => $value) {
            if (is_string($key)) {
                $normalized = strtolower(str_replace(['-', ' '], '_', trim($key)));
                if (in_array($normalized, $forbidden, true)) {
                    throw ValidationException::withMessages([
                        'configuration' => 'Secret credentials must be stored in the encrypted identity-provider secret payload.',
                    ]);
                }
            }

            if (is_array($value)) {
                self::assertPublicConfiguration($value);
            }
        }
    }
}
