<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
final class CrmCustomFieldDefinition extends Model { use BelongsToTenant; use HasUuids; protected $table='nx_crm_custom_field_definitions'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['options'=>'array','required'=>'boolean','active'=>'boolean'];} public function values():HasMany{return $this->hasMany(CrmCustomFieldValue::class,'field_id');} }
