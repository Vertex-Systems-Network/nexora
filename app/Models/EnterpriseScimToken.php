<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class EnterpriseScimToken extends Model { use HasUuids;protected $table='nx_enterprise_scim_tokens';protected $guarded=[];public $incrementing=false;protected $keyType='string';protected $hidden=['token_hash'];protected function casts():array{return ['enabled'=>'boolean','last_used_at'=>'datetime','expires_at'=>'datetime','revoked_at'=>'datetime'];}public function organization():BelongsTo{return $this->belongsTo(EnterpriseOrganization::class,'organization_id');} }
