<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class CrmOpportunityStageHistory extends Model { use HasUuids; protected $table='nx_crm_opportunity_stage_history'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; public $timestamps=false; protected function casts():array{return['changed_at'=>'datetime'];} public function opportunity():BelongsTo{return $this->belongsTo(CrmOpportunity::class,'opportunity_id');} public function fromStage():BelongsTo{return $this->belongsTo(CrmPipelineStage::class,'from_stage_id');} public function toStage():BelongsTo{return $this->belongsTo(CrmPipelineStage::class,'to_stage_id');} public function changer():BelongsTo{return $this->belongsTo(User::class,'changed_by');} }
