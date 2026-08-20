<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkflowStepRun extends Model
{
    protected $table = 'nx_workflow_step_runs';
    protected $guarded = [];
    protected function casts(): array { return ['input'=>'array','output'=>'array','started_at'=>'datetime','completed_at'=>'datetime']; }
    public function run(): BelongsTo { return $this->belongsTo(WorkflowRun::class, 'workflow_run_id'); }
}
