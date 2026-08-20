# N0.16 — UI Library Governance + Document Engine Foundation

## Purpose

N0.16 closes the database-selector identity regression, formalizes Nexora UI-library boundaries, and introduces the universal structured Document Engine that future Blog/Article features plus external Books, CV/Profile, Research, Documentation and custom publishing packages can share.

## Database driver selector fix

The installer previously grouped registry data with a collection operation and then used the grouped collection index as the option value. Grouped collections may reindex entries, so the visible MySQL option could submit `0` while backend validation expected `mysql`.

N0.16 treats the driver definition's own `key` as the only public identity:

```text
DatabaseDriverRegistry
    mysql => { key: mysql, ... }
                    ↓
InstallerController databaseDriverOptions
                    ↓
value = driver.key
                    ↓
x-ui.select
                    ↓
POST db_driver=mysql
```

The browser-side driver map is reconstructed from `driver.key` as well. Display grouping can no longer change submitted identity.

## UI library rule

Feature surfaces may not create their own native interactive controls. Installer features use Blade `x-ui.*` controls and the framework-independent `public/installer/nexora-ui.js` enhancement layer. React/Admin feature pages consume only `@nexora/admin-ui` controls and links.

Raw semantic layout elements such as `form`, `section`, `div`, headings and text remain normal document structure. Native `button`, `input`, `select` and `textarea` elements exist only inside the reusable UI-library implementation where accessibility and browser semantics are centralized.

The source guard rejects raw feature-level interactive controls and direct Inertia `Link` imports in Admin pages.

## Document Engine

The new core module `nexora.documents` provides:

- typed document registry
- typed block registry
- structured JSON document tree rather than an uncontrolled HTML blob
- immutable revision snapshots
- document repository contract
- canonical content validation/normalization
- author/editor relationships
- draft/published/archived state
- extension-safe document and block registration
- permission/capability boundaries
- premium Admin list/create/edit foundation

### Canonical content shape

```json
{
  "version": 1,
  "blocks": [
    {
      "id": "stable-block-id",
      "type": "paragraph",
      "version": 1,
      "data": {},
      "children": []
    }
  ]
}
```

Unknown block types are rejected by `DocumentContentValidator`. Duplicate document-type or block registration is rejected rather than silently overriding another module.

## Storage

`nx_documents` stores current structured state and metadata. `nx_document_revisions` stores revision snapshots. The initial block does not yet provide the full visual Writer/Block editor; that editor will consume this stable engine instead of inventing a second content model.

## Next publishing layers

The planned publishing sequence now has a stable base:

```text
Nexora Documents
      ↓
Nexora Writer / Editorial
      ↓
Blog + Articles
External Books / Manuscripts package
External Profile + CV package
SEO / Schema / Distribution
```
