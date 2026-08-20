# N0.17 — Premium Admin Shell + Nexora Writer Foundation

N0.17 fixes the frontend build regression in the Inertia button-link abstraction, upgrades the Admin shell for daily production use, normalizes human-readable data-service labels, and turns the N0.16 Document Engine into the first usable Nexora Writer surface.

## Build regression

`InertiaLinkProps` exposes a `size` property that conflicts with Nexora's `ButtonSize`. `UntitledButtonLink` now omits both `children` and `size` before declaring the Nexora-specific button props. This prevents TypeScript from reducing `size` to `never`.

## Admin shell

- Persistent desktop sidebar collapse (`nexora.admin.sidebar.collapsed`).
- Collapsed navigation keeps icons and exposes accessible tooltips.
- Icon-only actions use the Nexora `IconButton` / `IconLink` primitives; the primitives now render proper tooltips.
- Light, Dark and System appearance are available directly from the top bar.
- Theme choice is an individual browser preference layered over the platform default appearance.
- Mobile navigation continues to use the full-width navigation model.
- No raw `button`, `input`, `select`, `textarea` or direct Inertia `Link` is allowed outside `@nexora/admin-ui` in Admin React surfaces.

## Human-readable data services

Installer-created data-service placeholders now use catalog labels such as `MongoDB Atlas` and `Amazon DynamoDB` instead of internal keys such as `mongodb_atlas` or `aws_dynamodb`. Old installations containing raw keys are normalized at presentation time without a destructive migration.

## Nexora Writer

The Document form now edits the canonical structured block tree.

Initial supported blocks:

- Paragraph
- Heading (H1-H6)
- Bulleted / numbered list
- Quote with attribution
- Code with language metadata
- Divider

Block actions:

- Add
- Convert type
- Move up/down
- Delete

Writer insights currently provide:

- Word count
- Estimated reading time
- Block count
- Heading count

Every successful document save still creates an immutable revision snapshot. N0.17 intentionally keeps save explicit; autosave, collaborative presence, comments, editorial workflow, revision comparison and restoration belong to subsequent publishing milestones.
