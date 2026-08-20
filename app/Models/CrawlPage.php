<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CrawlPage extends Model
{
    protected $table = 'nx_crawl_pages';
    protected $guarded = [];
    protected function casts(): array { return ['metadata'=>'array','has_schema'=>'boolean']; }
    public function run(): BelongsTo { return $this->belongsTo(CrawlRun::class, 'crawl_run_id'); }
    public function issues(): HasMany { return $this->hasMany(CrawlIssue::class, 'crawl_page_id'); }
}
