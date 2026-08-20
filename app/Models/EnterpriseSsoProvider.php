<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class EnterpriseSsoProvider extends Model { use HasUuids;protected $table='nx_enterprise_sso_providers';protected $guarded=[];public $incrementing=false;protected $keyType='string';protected $hidden=['secret_payload'];protected function casts():array{return ['enabled'=>'boolean','enforce_for_members'=>'boolean','configuration'=>'array','secret_payload'=>'encrypted:array'];}public function organization():BelongsTo{return $this->belongsTo(EnterpriseOrganization::class,'organization_id');} }
