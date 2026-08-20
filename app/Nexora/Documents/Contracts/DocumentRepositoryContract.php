<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Contracts;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DocumentRepositoryContract
{
    /** @param array<string,mixed> $filters */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** @param array<string,mixed> $attributes */
    public function create(array $attributes, ?int $actorId = null): Document;

    /** @param array<string,mixed> $attributes */
    public function update(Document $document, array $attributes, ?int $actorId = null): Document;

    public function delete(Document $document): void;
}
