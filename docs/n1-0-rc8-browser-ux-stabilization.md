# N1.0 RC8 — Browser / UX / Accessibility / RTL Stabilization

RC8 makes browser-facing quality an explicit release-candidate contract. It does not claim WCAG 2.2 AA certification from source scans alone; manual assistive-technology and real-browser evidence remains required before stable release.

## Source contracts

The dependency-free `browser-ux-contract` gate verifies the Admin skip link and main landmark, server/client language and direction, visible keyboard focus, reduced-motion support, forced-colors focus fallback, logical RTL utilities, dialog semantics/focus restoration, error/hint associations, icon-only labels/tooltips, DataTable keyboard/sort/loading/pagination semantics, command-palette dialog/live-result behavior, toast live regions, and empty/error state announcements.

Physical `text-left` and shared-surface `left-*` / `right-*` utility regressions are rejected in the Admin layer (tooltip geometry is the intentional exception because its placement API is physical by design).

## Shared UI hardening

- Inputs and textareas generate stable IDs and connect hints/errors with `aria-describedby`; errors also use `aria-errormessage` and an alert live region.
- DataTable scroll regions are keyboard reachable, sortable headers expose `aria-sort`, loading state uses `aria-busy`, and pagination is a named navigation landmark with `aria-current`.
- The command palette is an actual modal dialog with focus trapping/restoration and a polite result-count live region.
- Global layout has a keyboard-only skip link and focusable main target.
- Core layout primitives use logical inline classes (`start/end`, `ps/pe`, `text-start`) so RTL does not depend on duplicated directional CSS.
- Route progress has an RTL animation path; forced-color and reduced-motion users retain usable feedback.

## Browser evidence

`docs/browser-certification-evidence.example.json` defines the operator evidence shape. Copy it to `storage/app/nexora/certification/browser-evidence.json`, record observed results for mobile/tablet/desktop × LTR/RTL × light/dark, and run:

```bat
php scripts\browser-evidence-verify.php
```

Full release certification can make that evidence required by setting:

```bat
set NEXORA_CERT_BROWSER_EVIDENCE=1
```

This evidence gate is intentionally fail-closed when enabled. Example/template values are not accepted as real evidence.
