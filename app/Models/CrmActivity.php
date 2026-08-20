<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class CrmActivity extends Model { use BelongsToTenant; use HasUuids; protected $table='nx_crm_activities'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['metadata'=>'array','occurred_at'=>'datetime','due_at'=>'datetime','completed_at'=>'datetime'];} public function owner():BelongsTo{return $this->belongsTo(User::class,'owner_id');} public function creator():BelongsTo{return $this->belongsTo(User::class,'created_by');} }
