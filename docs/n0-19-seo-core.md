# N0.19 — SEO Core & Discovery Foundation

N0.19 introduces a theme-independent SEO subsystem. SEO data is owned by Nexora Core contracts and rendered by themes later; themes do not persist their own competing canonical, robots or JSON-LD state.

## Core storage

- `nx_seo_entries`: canonical metadata for any stable Nexora resource identity.
- `nx_seo_schema_nodes`: extension-ready JSON-LD graph nodes.
- `nx_seo_internal_link_suggestions`: durable internal-link analysis decisions.

The initial resource adapter is `document`, but the storage model is deliberately generic so future Articles, Commerce entities and external Apps can attach without creating parallel SEO tables.

## Canonical and indexing policy

Each SEO entry can define:

- Search title and description.
- Absolute canonical URL.
- Public URL path fallback.
- Index/noindex.
- Follow/nofollow.
- Advanced robots directives.
- Sitemap inclusion preference.
- Semantic schema type.
- Social-preview metadata foundation.

Nexora does not use a synthetic SEO score. Audits emit specific structural issues with severity, code and remediation context.

## Central Schema Graph

`SchemaGraph` uses stable `@id` nodes and refuses silent type conflicts. Same-type higher-priority providers may refine node properties; a provider cannot silently change a `WebPage` node into an `Organization` node.

`SchemaGraphBuilder` currently emits:

- `WebSite`
- optional `Organization`
- resource `WebPage` / selected subtype
- persisted extension nodes

The theme layer will later receive graph output through `SeoManagerContract` and render one canonical JSON-LD graph.

## Sitemap

`/sitemap.xml` is generated from canonical SEO entries. The initial renderer:

- includes only resources marked for sitemap inclusion;
- excludes noindex resources;
- excludes unpublished Nexora documents;
- prefers absolute canonical URL, then resolves a public URL path against `APP_URL`;
- emits `lastmod` from SEO metadata updates.

Sitemap generation is a discovery aid, not an indexing guarantee.

## Internal links

The initial analyzer is intentionally conservative. It suggests another published document only when that document's title already occurs naturally in the source text. Suggestions can be marked added or dismissed. Automatic block mutation is intentionally deferred until the Writer has a stable inline-link mark model.

## External package rule

Books, CV/Profile, LMS, Booking and Projects are not internal Nexora features. Future external packages may register SEO schema/resource adapters through public contracts without changing SEO Core tables or private implementation code.
