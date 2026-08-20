<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class QuarantinePackage extends Model
{
    use HasUuids;

    protected $table = 'nx_quarantine_packages';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime', 'size_bytes' => 'integer'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(SecurityScan::class, 'quarantine_package_id');
    }
}
