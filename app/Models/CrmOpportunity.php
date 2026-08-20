<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
final class CrmOpportunity extends Model { use BelongsToTenant; use HasUuids; protected $table='nx_crm_opportunities'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['metadata'=>'array','expected_close_at'=>'datetime','won_at'=>'datetime','lost_at'=>'datetime'];} public function pipeline():BelongsTo{return $this->belongsTo(CrmPipeline::class,'pipeline_id');} public function stage():BelongsTo{return $this->belongsTo(CrmPipelineStage::class,'stage_id');} public function organization():BelongsTo{return $this->belongsTo(CrmOrganization::class,'organization_id');} public function contact():BelongsTo{return $this->belongsTo(CrmContact::class,'contact_id');} public function owner():BelongsTo{return $this->belongsTo(User::class,'owner_id');} public function stageHistory():HasMany{return $this->hasMany(CrmOpportunityStageHistory::class,'opportunity_id')->orderByDesc('changed_at');} }
