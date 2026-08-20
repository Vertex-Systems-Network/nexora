<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CrawlRun extends Model
{ use BelongsToTenant;
    protected $table = 'nx_crawl_runs';
    protected $guarded = [];
    protected function casts(): array { return ['summary'=>'array','started_at'=>'datetime','completed_at'=>'datetime']; }
    public function pages(): HasMany { return $this->hasMany(CrawlPage::class, 'crawl_run_id'); }
    public function issues(): HasMany { return $this->hasMany(CrawlIssue::class, 'crawl_run_id'); }
    public function starter(): BelongsTo { return $this->belongsTo(User::class, 'started_by'); }
}
