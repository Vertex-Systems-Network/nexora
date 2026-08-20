<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class CrmTimelineEvent extends Model { use HasUuids; protected $table='nx_crm_timeline_events'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; public $timestamps=false; protected function casts():array{return['payload'=>'array','occurred_at'=>'datetime','created_at'=>'datetime'];} public function actor():BelongsTo{return $this->belongsTo(User::class,'actor_id');} }
