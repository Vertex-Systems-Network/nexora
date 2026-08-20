<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AutomationEvent extends Model
{ use BelongsToTenant;
    protected $table = 'nx_automation_events';
    protected $guarded = [];
    protected function casts(): array { return ['payload'=>'array','occurred_at'=>'datetime','processed_at'=>'datetime']; }
    public function runs(): HasMany { return $this->hasMany(WorkflowRun::class); }
}
