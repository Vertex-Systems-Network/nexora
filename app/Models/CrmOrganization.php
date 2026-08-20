<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CrmOrganization extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table='nx_crm_organizations'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['address'=>'array','metadata'=>'array']; }
    public function owner(): BelongsTo { return $this->belongsTo(User::class,'owner_id'); }
    public function contacts(): HasMany { return $this->hasMany(CrmContact::class,'organization_id'); }
    public function leads(): HasMany { return $this->hasMany(CrmLead::class,'organization_id'); }
    public function opportunities(): HasMany { return $this->hasMany(CrmOpportunity::class,'organization_id'); }
    public function commerceLinks(): HasMany { return $this->hasMany(CrmCommerceLink::class,'organization_id'); }
}
