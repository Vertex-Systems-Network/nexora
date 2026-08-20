# N0.25 — Media Library, Newsletter & Distribution

N0.25 implements the first-party media and outbound publishing foundation.

## Media Library

- Images, video, audio and documents with a conservative MIME allow-list.
- SHA-256 payload identity and server-generated storage names.
- Folders, collections, metadata, alt text, captions, focal point and usage records.
- Soft-delete Trash, restore and guarded permanent deletion.
- Optional GD-generated responsive WebP variants at 480/960/1440/1920 widths.
- Controlled public delivery endpoint (`/media/{uuid}/{variant?}`) independent from a storage symlink.
- Writer Image block and Article/Blog hero-image integration.

## Newsletter

- Audience lists and consent-aware subscribers independent from users.
- Campaign draft/scheduled/sending/sent lifecycle.
- Per-recipient delivery records and queue jobs through the configured Laravel mail transport.
- Public unsubscribe confirmation with opaque tokens.
- Scheduled dispatch runner: `php artisan nexora:distribution:run`.

## Distribution adapters

- Stable adapter registry.
- RSS 2.0 adapter exposed at `/feed.xml`.
- Newsletter adapter reports mail transport readiness.
- Future external syndication/social/webhook adapters extend the same registry and capability boundary.
