<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class EnterpriseOrganizationMember extends Model { use HasUuids; protected $table='nx_enterprise_organization_members'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts(): array{return ['joined_at'=>'datetime','last_active_at'=>'datetime'];} public function organization():BelongsTo{return $this->belongsTo(EnterpriseOrganization::class,'organization_id');} public function user():BelongsTo{return $this->belongsTo(User::class);} }
