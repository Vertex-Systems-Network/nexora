<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SecurityScan extends Model
{
    use HasUuids;

    protected $table = 'nx_security_scans';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'risk_score' => 'integer',
            'manifest' => 'array',
            'summary' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function quarantinePackage(): BelongsTo
    {
        return $this->belongsTo(QuarantinePackage::class, 'quarantine_package_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(SecurityFindingRecord::class, 'scan_id');
    }
}
