<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EnterpriseOrganization extends Model
{
    use HasUuids;
    protected $table='nx_enterprise_organizations'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['is_default'=>'boolean','metadata'=>'array']; }
    public function owner(): BelongsTo { return $this->belongsTo(User::class,'owner_user_id'); }
    public function members(): HasMany { return $this->hasMany(EnterpriseOrganizationMember::class,'organization_id'); }
    public function domains(): HasMany { return $this->hasMany(EnterpriseDomain::class,'organization_id'); }
    public function ssoProviders(): HasMany { return $this->hasMany(EnterpriseSsoProvider::class,'organization_id'); }
}
