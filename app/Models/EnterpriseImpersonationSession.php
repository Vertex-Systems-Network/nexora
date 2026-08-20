<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class EnterpriseImpersonationSession extends Model { use HasUuids;protected $table='nx_enterprise_impersonation_sessions';protected $guarded=[];public $incrementing=false;protected $keyType='string';protected function casts():array{return ['started_at'=>'datetime','ended_at'=>'datetime'];}public function actor():BelongsTo{return $this->belongsTo(User::class,'actor_user_id');}public function target():BelongsTo{return $this->belongsTo(User::class,'target_user_id');} }
