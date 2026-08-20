<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class CrmNote extends Model { use BelongsToTenant; use HasUuids; protected $table='nx_crm_notes'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['pinned'=>'boolean'];} public function author():BelongsTo{return $this->belongsTo(User::class,'author_id');} }
