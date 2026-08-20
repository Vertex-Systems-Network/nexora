<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class CrmLead extends Model { use BelongsToTenant; use HasUuids; protected $table='nx_crm_leads'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['metadata'=>'array','converted_at'=>'datetime'];} public function organization():BelongsTo{return $this->belongsTo(CrmOrganization::class,'organization_id');} public function contact():BelongsTo{return $this->belongsTo(CrmContact::class,'contact_id');} public function owner():BelongsTo{return $this->belongsTo(User::class,'owner_id');} public function convertedOpportunity():BelongsTo{return $this->belongsTo(CrmOpportunity::class,'converted_opportunity_id');} }
