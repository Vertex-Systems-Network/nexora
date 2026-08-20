<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CrmContact extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table='nx_crm_contacts'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['metadata'=>'array']; }
    public function organization(): BelongsTo { return $this->belongsTo(CrmOrganization::class,'organization_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class,'owner_id'); }
    public function leads(): HasMany { return $this->hasMany(CrmLead::class,'contact_id'); }
    public function opportunities(): HasMany { return $this->hasMany(CrmOpportunity::class,'contact_id'); }
    public function commerceLinks(): HasMany { return $this->hasMany(CrmCommerceLink::class,'contact_id'); }
}
