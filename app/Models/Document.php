<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Document extends Model
{ use BelongsToTenant;
    use HasFactory;
    protected $table = 'nx_documents';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'schema_version' => 'integer',
            'lock_version' => 'integer',
            'review_due_at' => 'datetime',
            'autosaved_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRevision::class, 'document_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function autosaves(): HasMany
    {
        return $this->hasMany(DocumentAutosave::class, 'document_id');
    }

    public function reviewComments(): HasMany
    {
        return $this->hasMany(DocumentReviewComment::class, 'document_id');
    }

    public function seoEntries(): HasMany
    {
        return $this->hasMany(SeoEntry::class, 'resource_id')->where('resource_type', 'document');
    }

    public function taxonomyTerms(): BelongsToMany
    {
        return $this->belongsToMany(TaxonomyTerm::class, 'nx_document_terms', 'document_id', 'term_id')
            ->withPivot(['is_primary', 'position'])->withTimestamps();
    }

    public function authorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(AuthorProfile::class, 'nx_document_authors', 'document_id', 'author_profile_id')
            ->withPivot(['role', 'position'])->withTimestamps()->orderByPivot('position');
    }

    public function articleMetadata(): HasOne
    {
        return $this->hasOne(ArticleMetadata::class, 'document_id');
    }

    public function series(): BelongsToMany
    {
        return $this->belongsToMany(ContentSeries::class, 'nx_content_series_items', 'document_id', 'series_id')
            ->withPivot('position')->withTimestamps();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ContentCollection::class, 'nx_content_collection_documents', 'document_id', 'collection_id')
            ->withPivot(['position', 'data'])->withTimestamps()->orderByPivot('position');
    }

    public function mediaUsages(): HasMany
    {
        return $this->hasMany(MediaUsage::class, 'resource_id')->where('resource_type', 'document');
    }

    public function internalLinkSuggestions(): HasMany
    {
        return $this->hasMany(SeoInternalLinkSuggestion::class, 'source_document_id');
    }
}
