<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class AuthorProfile extends Model
{ use BelongsToTenant;
    protected $table = 'nx_author_profiles';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['social_links' => 'array', 'expertise' => 'array', 'is_public' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'nx_document_authors', 'author_profile_id', 'document_id')
            ->withPivot(['role', 'position'])->withTimestamps();
    }
}
