# N0.26 — Search, Content Analytics & SEO Crawler

N0.26 adds Nexora's first-party discovery intelligence layer without introducing a second content store or a synthetic SEO score.

## Search architecture

- `SearchIndexer` creates searchable projections from canonical Documents and Media Library metadata.
- Published public search is restricted to published Documents; Admin global search may surface permitted Documents and Media assets.
- Document text is extracted from the structured block tree, including nested text/list items and image alternative/caption text.
- Document and Media observers keep the projection current; `php artisan nexora:search:reindex` provides deterministic recovery/rebuild.
- Public `/search` is rendered through the active Theme Engine and uses `noindex,follow` so query-result combinations do not become an indexing surface.
- Search-demand logs record normalized query terms and result counts for content-gap analysis.

## Privacy-aware analytics

- Public HTML page views are captured through `RecordPublicAnalytics`.
- Raw visitor IP addresses are never persisted to analytics tables.
- Visitor and session identifiers use HMAC-derived hashes; visitor identity intentionally rotates by day.
- `Sec-GPC: 1` and `DNT: 1` requests are excluded from page-view and search-query analytics.
- Daily aggregation stores page views, daily-unique visitors, search demand, zero-result searches, referrals and server-response engagement observations.
- Analytics is first-party operational/content intelligence; it is not presented as a replacement for consent/legal requirements in jurisdictions where additional consent is required.

## SEO crawler

- The crawler begins from URLs owned by `SitemapService` plus the configured site root.
- It only follows `http/https` links on the configured `APP_URL` host.
- Admin, authentication, installer, media-serving and newsletter utility paths are excluded.
- Each run persists individual page evidence and issues rather than collapsing evidence into a numerical SEO score.
- Current observations include HTTP availability, slow responses, titles, meta descriptions, canonical policy, noindex conflicts, H1 hierarchy, Schema Graph presence, visible-text observations and duplicate titles/canonical targets.
- Admin crawl starts are queue-backed. `php artisan nexora:seo:crawl --limit=250` is available for immediate/CLI execution.

## Operational commands

```bash
php artisan nexora:search:reindex
php artisan nexora:analytics:aggregate
php artisan nexora:analytics:aggregate --date=2026-08-15
php artisan nexora:analytics:prune
php artisan nexora:seo:crawl --limit=250
```

Analytics aggregation runs hourly. Automated crawling is disabled by default and only runs on schedule when `seo.crawler.enabled` is enabled in platform settings.
