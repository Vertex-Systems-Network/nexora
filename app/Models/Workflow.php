<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Workflow extends Model
{ use BelongsToTenant;
    protected $table = 'nx_workflows';
    protected $guarded = [];
    protected function casts(): array { return ['trigger_config'=>'array','conditions'=>'array','actions'=>'array','last_run_at'=>'datetime']; }
    public function runs(): HasMany { return $this->hasMany(WorkflowRun::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
