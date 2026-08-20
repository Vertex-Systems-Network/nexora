<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class CrmCustomFieldValue extends Model { use HasUuids; protected $table='nx_crm_custom_field_values'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['value'=>'array'];} public function field():BelongsTo{return $this->belongsTo(CrmCustomFieldDefinition::class,'field_id');} }
