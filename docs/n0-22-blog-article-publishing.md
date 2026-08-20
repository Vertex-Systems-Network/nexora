# N0.22 — Blog & Article Publishing

N0.22 turns the existing Document/Writer/Editorial/SEO/Theme/Studio foundations into a first-party publishing experience without creating parallel content systems.

## Core model

`Article` and `Blog post` are registered Document Engine types. Body content remains the canonical structured Document tree and revisions/autosaves remain owned by Document/Editorial services.

Publishing-specific data is stored separately:

- `nx_author_profiles`
- `nx_taxonomy_terms`
- `nx_document_terms`
- `nx_content_series`
- `nx_content_series_items`
- `nx_article_metadata`
- `nx_document_authors`

This keeps publishing metadata independent from the body schema and makes future external packages able to reuse the same services.

## Public URLs

- `/blog`
- `/blog/{slug}`
- `/articles/{slug}`
- `/blog/category/{slug}`
- `/blog/topic/{slug}`
- `/blog/tag/{slug}`
- `/blog/series/{slug}`
- `/authors/{slug}`

Document SEO entries remain the canonical URL source for article/blog detail pages.

## Scheduling

`php artisan nexora:publishing:run` publishes due draft content. It is registered with Laravel Scheduler every minute and uses overlap protection. Production environments still need the normal Laravel scheduler trigger.

Scheduled publication increments the document lock version and appends an immutable revision snapshot.

## Security / architecture

- No raw HTML/JS is introduced by Blog publishing.
- Blog settings do not replace Writer or Studio.
- Blog settings do not own canonical/schema/sitemap policy; they extend SEO Core.
- Author profile and taxonomy data is escaped by public renderers.
- Publishing actions use dedicated permissions and runtime capabilities.
