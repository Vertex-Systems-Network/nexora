# N0.21 — Nexora Studio Visual Builder Foundation

N0.21 introduces the first visual composition layer on top of the Document Engine, Theme Engine and SEO-safe public rendering stack.

## Architecture

Studio does not edit theme package files and it does not store executable markup. It stores a versioned, validated visual tree:

```json
{
  "version": 1,
  "children": [
    {
      "id": "stable-node-id",
      "type": "section",
      "props": {},
      "styles": { "base": {}, "tablet": {}, "mobile": {} },
      "bindings": {},
      "children": []
    }
  ]
}
```

The server validates every element type, property, style key and dynamic binding before persistence.

## Implemented foundations

- Studio module and runtime capabilities.
- Admin → Studio workspace.
- Standalone, document-bound and theme-template canvas scopes.
- Typed element registry.
- Initial element set: Section, Stack, Grid, Heading, Text, Button, Divider, Spacer.
- Desktop / Tablet / Mobile style layers.
- Dynamic binding registry with allow-listed sources.
- Document title/excerpt, SEO title and site-name bindings.
- Visual element library, layers navigator and inspector.
- Palette → canvas drag/drop foundation.
- Selection, nested container composition and responsive preview widths.
- Local undo/redo history.
- Immutable server-side Studio revisions on save.
- Stale lock-version protection.
- Reusable user components from selected Studio elements.
- Published document canvas rendering through the active Theme Engine.
- Safe fallback to the Document Engine renderer when no published Studio canvas exists.
- Studio permissions and capability boundaries.

## Security boundary

Studio never stores arbitrary HTML, CSS declarations or executable JavaScript from the browser. It accepts only registered element types and allow-listed style/property fields. Public rendering escapes data and produces HTML from server-owned renderers.

## Not yet implemented

- Cross-canvas component synchronization.
- Component variants/props.
- Advanced flex/grid constraints.
- Asset/media elements (depends on Media milestone).
- Absolute positioning/freeform design mode.
- Animation/motion timeline.
- Breakpoint manager beyond the initial desktop/tablet/mobile contract.
- Full theme-template publishing and template routing.
- Collaborative multiplayer editing.

These remain later Studio/Media/Automation milestones rather than being faked in N0.21.
