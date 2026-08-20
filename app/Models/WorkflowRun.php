<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WorkflowRun extends Model
{ use BelongsToTenant;
    protected $table = 'nx_workflow_runs';
    protected $guarded = [];
    protected function casts(): array { return ['context'=>'array','output'=>'array','started_at'=>'datetime','completed_at'=>'datetime']; }
    public function workflow(): BelongsTo { return $this->belongsTo(Workflow::class); }
    public function event(): BelongsTo { return $this->belongsTo(AutomationEvent::class, 'automation_event_id'); }
    public function steps(): HasMany { return $this->hasMany(WorkflowStepRun::class); }
}
