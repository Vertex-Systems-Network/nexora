# Nexora Admin UI Standard

## Architecture

```text
Untitled UI React (licensed source)
        ↓
@nexora/admin-ui
        ↓
Nexora modules/apps/extensions
```

No feature module may import Untitled UI implementation files directly.

## Spacing system

Use a small, consistent token set. Do not invent arbitrary margins/padding per screen.

- `space-1`: 4px
- `space-2`: 8px
- `space-3`: 12px
- `space-4`: 16px
- `space-5`: 20px
- `space-6`: 24px
- `space-8`: 32px
- `space-10`: 40px
- `space-12`: 48px
- `space-16`: 64px

Page structure should follow consistent vertical rhythm:

```text
AdminShell
  PageHeader
  PageToolbar (optional)
  PageFeedback (optional)
  MainContent
```

## Loading hierarchy

Use the smallest loading scope that accurately represents the operation.

1. **Route/page navigation** — thin global progress indicator; existing page should not flash blank.
2. **First data load** — skeleton matching final content geometry.
3. **Table refresh/filter/search** — preserve existing layout; show table-local pending state.
4. **Button action** — button-local spinner + disabled duplicate submission.
5. **Background refresh** — subtle non-blocking indicator; do not replace useful content with a spinner.

Never show a blank white page while data is being fetched.

## Required states

Every data-driven surface must consider:

- loading;
- loaded;
- empty;
- error;
- retrying;
- stale/degraded when applicable;
- unauthorized/forbidden when applicable.

## Page transitions

- Preserve layout shell across navigation.
- Avoid full-page remounts when not necessary.
- Prevent cumulative layout shift from loading states.
- Prefer skeletons and optimistic/local transitions over blocking overlays.

## Tables

Every production table must support the relevant subset of:

- search;
- filtering;
- sorting;
- pagination;
- bulk selection/actions;
- column visibility;
- loading skeleton;
- empty result;
- error + retry;
- responsive fallback;
- keyboard/focus usability.

## Forms

- clear field label/help/error hierarchy;
- server-side validation is authoritative;
- dirty-state protection where data loss is possible;
- explicit saving/saved/error feedback;
- destructive actions separated from normal save actions;
- submit button prevents double submission.

## Accessibility

- semantic landmarks/headings;
- visible focus states;
- keyboard operability;
- appropriate ARIA naming;
- dialog focus trap/return;
- sufficient contrast;
- reduced-motion preference support.
