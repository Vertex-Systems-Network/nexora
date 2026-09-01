# Nexora AI Design Professional Contract

## Goal

AI Design Professional is not a text-to-random-HTML feature. It is a governed design system that converts intent into structured, editable Nexora Studio output while preserving brand rules, responsive behavior, accessibility, content ownership and theme contracts.

## Required input context

The design agent may use:

- site/product brief;
- target audience and conversion goal;
- active theme manifest;
- global design tokens;
- component library and allowed Studio nodes;
- content type schemas and available content;
- navigation requirements;
- locale/direction settings;
- media assets and usage rights metadata;
- SEO page intent;
- accessibility requirements;
- brand constraints;
- explicit user references/instructions.

## Design pipeline

```text
User intent
  ↓
Design brief
  ↓
Information architecture
  ↓
Page/section requirements
  ↓
Design-token proposal/use
  ↓
Component selection
  ↓
Structured visual AST
  ↓
Responsive constraints
  ↓
Dynamic content bindings
  ↓
Accessibility validation
  ↓
Theme/Studio contract validation
  ↓
Preview
  ↓
User approval / policy decision
  ↓
Publishable Studio mutation
```

## AI Design Professional must produce structured artifacts

Every generated design should be representable using validated schemas such as:

- `DesignBrief`;
- `InformationArchitecture`;
- `DesignTokenSet` or token patch;
- `PagePlan`;
- `ComponentTree` / Studio visual AST;
- `ResponsiveRules`;
- `DataBindings`;
- `InteractionDefinitions`;
- `AccessibilityReport`;
- `DesignValidationReport`.

The model may explain design reasoning in prose, but prose is not the executable design format.

## Design tokens

The agent should prefer existing tokens. New tokens require an explicit token proposal.

Token families include:

- color roles;
- typography roles;
- spacing scale;
- sizing/container widths;
- radius;
- border;
- shadows/elevation;
- motion durations/easing;
- responsive breakpoints;
- z-index/layer roles where needed.

Generated components must not scatter arbitrary one-off values when a valid global token exists.

## Component selection

The agent must use registered Studio/component contracts.

A component definition should expose:

- stable component ID/version;
- allowed children/slots;
- properties and value schemas;
- data-binding support;
- responsive capabilities;
- accessibility requirements;
- interaction targets;
- theme/token dependencies;
- extension/package origin;
- deprecation/migration metadata.

## Visual AST

AI-generated pages must remain editable in Studio.

The visual AST should preserve:

- stable node IDs;
- node type/component ID;
- semantic role;
- props;
- layout constraints;
- token references;
- data bindings;
- visibility rules;
- responsive overrides;
- interactions;
- accessibility attributes;
- source/provenance metadata for AI actions.

Raw opaque HTML/JSX/CSS blobs are not the primary generated page model.

## Responsive design

AI must validate at least platform-defined viewport classes and support fluid behavior between them.

Requirements:

- no unbounded overflow;
- sensible content widths;
- responsive typography constraints;
- component stacking/wrapping rules;
- image/media sizing rules;
- touch target validation;
- no hidden essential content purely to make layouts fit.

## Accessibility

AI design cannot be marked complete solely because it looks correct.

Validate:

- semantic structure;
- heading order;
- landmarks;
- form labels/errors;
- image alt policy;
- keyboard interaction;
- focus order/visibility;
- contrast against token combinations;
- target sizes;
- reduced-motion behavior;
- RTL compatibility where enabled.

Automated validation is evidence, not the sole accessibility certification.

## Dynamic design

AI should be able to design against content/query contracts rather than hard-code example content.

Examples:

- archive grids bound to a content type/query;
- taxonomy term headings and descriptions;
- related-content sections;
- navigation menus bound to a menu location;
- commerce product/collection grids;
- CRM/member/customer portal views where allowed.

## Interaction and motion

Motion is structured rather than arbitrary script injection.

An interaction definition declares:

- trigger;
- target node(s);
- state/animation timeline;
- allowed properties;
- duration/easing token;
- breakpoint conditions;
- reduced-motion fallback;
- validation constraints.

## AI design editing modes

- `suggest` — no mutation; returns a proposal.
- `draft` — creates/updates an unpublished draft.
- `patch` — bounded changes to selected nodes/tokens.
- `generate-page` — creates a complete draft page from a validated plan.
- `generate-site` — orchestrates IA/navigation/templates/pages/content placeholders as a multi-step approved plan.

Production publish is a separate capability/policy decision.

## AI design review loop

Before mutation is considered successful:

1. schema validation;
2. theme/component compatibility;
3. data-binding validation;
4. responsive validation;
5. accessibility checks;
6. route/navigation dependency checks;
7. SEO page-intent checks where applicable;
8. preview render;
9. post-render error diagnostics;
10. audit record.

## Professional-design quality model

Quality should be evaluated on explicit dimensions, not a vague single design score:

- hierarchy;
- clarity;
- brand consistency;
- spacing/layout rhythm;
- typography;
- responsive integrity;
- accessibility;
- component consistency;
- content fit;
- conversion/task clarity;
- performance implications;
- editability/maintainability.

## Dependencies

AI Design Professional implementation must not start before the core contracts it manipulates are sufficiently stable:

- `CONTENT-MODEL-200`;
- `QUERY-ENGINE-200`;
- `NAVIGATION-100`;
- `THEME-CONTRACT-200`;
- `SITE-BUILDER-200`;
- `AI-KERNEL-100`.

It may be architected earlier, but production mutations depend on those contracts.
