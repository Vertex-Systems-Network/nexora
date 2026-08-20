<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MembershipAccessPolicy extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table='nx_membership_access_policies'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['required_plan_ids'=>'array','required_entitlements'=>'array','active'=>'boolean','metadata'=>'array']; }
}
