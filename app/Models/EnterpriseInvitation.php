<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class EnterpriseInvitation extends Model { use HasUuids;protected $table='nx_enterprise_invitations';protected $guarded=[];public $incrementing=false;protected $keyType='string';protected function casts():array{return ['expires_at'=>'datetime','accepted_at'=>'datetime'];}public function organization():BelongsTo{return $this->belongsTo(EnterpriseOrganization::class,'organization_id');}public function inviter():BelongsTo{return $this->belongsTo(User::class,'invited_by');} }
