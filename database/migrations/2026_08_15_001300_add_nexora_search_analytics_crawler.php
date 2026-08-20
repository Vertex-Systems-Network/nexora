<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_search_index', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 48);
            $table->unsignedBigInteger('resource_id');
            $table->string('locale', 12)->default('en');
            $table->string('status', 32)->default('draft');
            $table->string('title', 500);
            $table->text('excerpt')->nullable();
            $table->longText('body_text')->nullable();
            $table->text('keywords')->nullable();
            $table->string('url_path', 1000)->nullable();
            $table->string('content_hash', 64);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();
            $table->unique(['resource_type', 'resource_id', 'locale'], 'nx_search_resource_locale_unique');
            $table->index(['status', 'published_at'], 'nx_search_status_published_idx');
            $table->index('indexed_at', 'nx_search_indexed_idx');
        });

        Schema::create('nx_search_query_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 24)->default('public');
            $table->string('query', 190);
            $table->string('normalized_query', 190);
            $table->unsignedInteger('results_count')->default(0);
            $table->string('locale', 12)->default('en');
            $table->string('visitor_hash', 64)->nullable();
            $table->string('session_hash', 64)->nullable();
            $table->timestamp('searched_at');
            $table->timestamps();
            $table->index(['scope', 'searched_at'], 'nx_search_logs_scope_time_idx');
            $table->index(['normalized_query', 'searched_at'], 'nx_search_logs_query_time_idx');
        });

        Schema::create('nx_analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 48);
            $table->string('resource_type', 48)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('path', 1000)->nullable();
            $table->string('locale', 12)->default('en');
            $table->string('visitor_hash', 64)->nullable();
            $table->string('session_hash', 64)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->string('utm_source', 190)->nullable();
            $table->string('utm_medium', 190)->nullable();
            $table->string('utm_campaign', 190)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['event_type', 'occurred_at'], 'nx_analytics_event_time_idx');
            $table->index(['resource_type', 'resource_id', 'occurred_at'], 'nx_analytics_resource_time_idx');
            $table->index(['visitor_hash', 'occurred_at'], 'nx_analytics_visitor_time_idx');
        });

        Schema::create('nx_analytics_daily_metrics', function (Blueprint $table): void {
            $table->id();
            $table->date('metric_date');
            $table->string('resource_type', 48)->default('site');
            $table->unsignedBigInteger('resource_id')->default(0);
            $table->unsignedBigInteger('page_views')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->unsignedBigInteger('searches')->default(0);
            $table->unsignedBigInteger('search_zero_results')->default(0);
            $table->unsignedBigInteger('referrals')->default(0);
            $table->unsignedBigInteger('engaged_views')->default(0);
            $table->timestamps();
            $table->unique(['metric_date', 'resource_type', 'resource_id'], 'nx_analytics_daily_unique');
            $table->index(['resource_type', 'metric_date'], 'nx_analytics_daily_type_date_idx');
        });

        Schema::create('nx_crawl_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 24)->default('queued');
            $table->string('base_url', 1000);
            $table->unsignedInteger('requested_limit')->default(250);
            $table->unsignedInteger('discovered_urls')->default(0);
            $table->unsignedInteger('crawled_urls')->default(0);
            $table->unsignedInteger('failed_urls')->default(0);
            $table->unsignedInteger('issues_count')->default(0);
            $table->unsignedInteger('high_issues_count')->default(0);
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'nx_crawl_runs_status_created_idx');
        });

        Schema::create('nx_crawl_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crawl_run_id')->constrained('nx_crawl_runs')->cascadeOnDelete();
            $table->string('url', 1500);
            $table->string('url_hash', 64);
            $table->string('path', 1000)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('content_type', 190)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('title', 500)->nullable();
            $table->string('meta_description', 1000)->nullable();
            $table->string('canonical_url', 1500)->nullable();
            $table->string('robots', 500)->nullable();
            $table->unsignedSmallInteger('h1_count')->default(0);
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('internal_links_count')->default(0);
            $table->unsignedInteger('external_links_count')->default(0);
            $table->boolean('has_schema')->default(false);
            $table->string('content_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['crawl_run_id', 'url_hash'], 'nx_crawl_page_run_url_unique');
            $table->index(['crawl_run_id', 'status_code'], 'nx_crawl_page_status_idx');
        });

        Schema::create('nx_crawl_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crawl_run_id')->constrained('nx_crawl_runs')->cascadeOnDelete();
            $table->foreignId('crawl_page_id')->nullable()->constrained('nx_crawl_pages')->cascadeOnDelete();
            $table->string('severity', 16);
            $table->string('code', 80);
            $table->string('category', 48)->default('technical');
            $table->string('title', 500);
            $table->text('description');
            $table->string('url', 1500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['crawl_run_id', 'severity'], 'nx_crawl_issue_severity_idx');
            $table->index(['code', 'created_at'], 'nx_crawl_issue_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_crawl_issues');
        Schema::dropIfExists('nx_crawl_pages');
        Schema::dropIfExists('nx_crawl_runs');
        Schema::dropIfExists('nx_analytics_daily_metrics');
        Schema::dropIfExists('nx_analytics_events');
        Schema::dropIfExists('nx_search_query_logs');
        Schema::dropIfExists('nx_search_index');
    }
};
