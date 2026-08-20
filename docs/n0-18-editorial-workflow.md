# N0.18 — Editorial Workflow, Revision Compare/Restore & Conflict-safe Autosave

N0.18 turns Nexora Writer from an explicit-save block editor into the first editorial publishing workflow layer.

## Editorial workflow

Built-in states are deliberately human readable and separate from publication visibility:

- Idea
- Draft
- In review
- Changes requested
- Approved
- Scheduled
- Published
- Archived

Transitions are controlled by `EditorialWorkflowRegistry`. The current document can only move to its current state or an allowed adjacent workflow state; arbitrary state jumps are rejected server-side.

Documents can now have an assigned writer, reviewer and review due date. Publication status (`draft`, `published`, `archived`) remains a separate concept from editorial workflow so later scheduling/distribution systems can operate without overloading one state field.

## Review comments

Editors can append review comments to the current immutable revision context. Comments have open/resolved state, author, resolver and timestamps. Resolving a comment is audited. N0.18 provides document-level review comments; inline block/range annotations remain a later collaboration enhancement.

## Conflict-safe autosave

Existing documents autosave structured writer content after a short idle delay without creating a permanent revision. Each autosave carries:

- document lock version
- base revision number
- title / slug / excerpt
- structured document content
- editorial workflow state

The server rejects stale autosaves with HTTP 409 when the permanent document lock/revision has advanced. The Writer then pauses autosave and asks the user to reload rather than overwriting newer work.

Autosaved work can be recovered after a reload/crash when it still targets the current permanent lock/revision. A successful manual revision clears that user's autosave.

## Optimistic document locking

Permanent edits now carry `lock_version`. A stale edit cannot silently overwrite a newer saved revision. Every successful permanent save/restore increments the lock version.

## Revision compare

The revision history screen provides:

- immutable timeline
- revision author/time
- field-level title/excerpt/publication/editorial changes
- semantic block comparison by stable block ID
- added / changed / removed block counts

Nexora compares structured blocks rather than raw generated HTML.

## Revision restore

Restoring an old revision never rewrites or deletes revision history. Nexora applies the selected snapshot to the document and records the restored state as a brand-new revision. Restore is protected by the current document lock version and clears obsolete autosave drafts.

## Database additions

N0.18 adds a new forward-only migration containing:

- editorial assignment/reviewer fields on `nx_documents`
- workflow state, due date and optimistic lock version
- `nx_document_autosaves`
- `nx_document_review_comments`
- publication/editorial snapshot fields on `nx_document_revisions`

No previous migration was edited.

## Security / authorization

New human permissions:

- `documents.revisions.view`
- `documents.revisions.restore`
- `documents.review`

New runtime capabilities:

- `content.editorial.review`
- `content.autosave.write`

All editorial endpoints retain the existing authenticated/verified/admin boundary and permission middleware.
