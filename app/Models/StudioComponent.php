<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudioComponent extends Model
{ use BelongsToTenant;
    protected $table = 'nx_studio_components';
    protected $guarded = [];

    protected function casts(): array { return ['content' => 'array']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
