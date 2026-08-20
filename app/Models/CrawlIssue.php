<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CrawlIssue extends Model
{
    protected $table = 'nx_crawl_issues';
    protected $guarded = [];
    protected function casts(): array { return ['metadata'=>'array']; }
    public function run(): BelongsTo { return $this->belongsTo(CrawlRun::class, 'crawl_run_id'); }
    public function page(): BelongsTo { return $this->belongsTo(CrawlPage::class, 'crawl_page_id'); }
}
