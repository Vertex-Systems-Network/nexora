<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Permission extends Model
{
    protected $table = 'nx_permissions';

    protected $fillable = ['name', 'slug', 'description', 'group'];

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'nx_role_permissions')->withTimestamps();
    }
}
