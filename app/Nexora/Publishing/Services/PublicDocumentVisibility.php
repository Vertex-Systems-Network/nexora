<?php

declare(strict_types=1);

namespace App\Nexora\Publishing\Services;

use App\Models\MembershipAccessPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class PublicDocumentVisibility
{
    /** @return list<int> */
    public function protectedDocumentIds(): array
    {
        return MembershipAccessPolicy::query()
            ->where('resource_type', 'document')
            ->where('active', true)
            ->pluck('resource_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** @template T of Builder|BelongsToMany
     *  @param T $query
     *  @return T
     */
    public function apply(Builder|BelongsToMany $query): Builder|BelongsToMany
    {
        $protected = $this->protectedDocumentIds();
        if ($protected !== []) {
            $query->whereNotIn('nx_documents.id', $protected);
        }
        return $query;
    }
}
