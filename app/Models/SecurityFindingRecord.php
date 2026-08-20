<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SecurityFindingRecord extends Model
{
    protected $table = 'nx_security_findings';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['hard_block' => 'boolean', 'metadata' => 'array', 'line_start' => 'integer', 'line_end' => 'integer'];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(SecurityScan::class, 'scan_id');
    }
}
