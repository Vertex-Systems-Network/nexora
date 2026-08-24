# Nexora Performance Budget Template

Use this template when a stage, page class, API, Theme, Extension/App/Integration/Studio-Pack or release requires explicit performance limits.

Budgets are policy, not universal constants. The active plan must define the target environment/test profile and justify thresholds. Core Web Vitals thresholds follow the current web standard; package/server budgets are Nexora product policy and must be versioned.

## Identity

- Budget ID:
- Owner unit ID:
- Parent stage:
- Applies to: platform / page class / Admin page / API / Theme / Extension / App / Integration / Studio-Pack / component / user flow
- Version:
- Effective release:
- Test profile IDs:
- Baseline evidence:

## Field / RUM budgets

- LCP p75:
- INP p75:
- CLS p75:
- FCP p75:
- TTFB p75:
- Segments required: mobile / desktop / locale / region / connection class
- Minimum sample policy:

## Synthetic browser budgets

- LCP:
- TBT or current equivalent blocking diagnostic:
- CLS:
- FCP:
- Speed/visual completion metric:
- Main-thread time:
- Long-task count/time:
- JS parse/compile/execute:
- Total transfer bytes:
- JS bytes:
- CSS bytes:
- image bytes:
- font bytes:
- request count:
- third-party bytes/time:
- DOM/node ceiling:

## Backend / Admin budgets

- total server response/wall time:
- application boot/middleware budget:
- controller/service budget:
- template/SSR/render budget:
- database total time:
- database query count:
- duplicate/N+1 policy:
- cache hit/miss policy:
- outbound HTTP budget:
- filesystem/object-storage budget:
- peak memory:
- CPU/sampled execution budget:

## Package-attribution budgets

- package-attributed backend wall time:
- package-attributed DB time/query count:
- package-attributed external calls:
- package JS/CSS/asset bytes:
- package request count:
- package main-thread/long-task budget:
- package DOM/component contribution:
- per-hook/event/slot budget where applicable:

## Code-quality budgets

- static/type errors allowed:
- lint error policy:
- complexity threshold:
- duplication threshold:
- dead/unused code policy:
- bundle/chunk threshold:
- dependency duplication/weight policy:
- test requirement:

## Regression policy

- Comparison baseline:
- Allowed absolute regression:
- Allowed percentage regression:
- Repeated-run count/statistical rule:
- Warn threshold:
- Fail threshold:
- Release-blocking dimensions:
- Authorized override permission:
- Override audit requirements:

## Evidence requirements

- source/build identity recorded;
- Theme/package versions recorded;
- environment/runner/browser/device/network/CPU identity recorded;
- cold/warm cache mode recorded;
- raw result retained according to policy;
- score/grade formula version recorded if displayed;
- field and lab evidence kept separate;
- backend trace redaction verified;
- known variability documented.

## Acceptance

- [ ] Budget owner approved.
- [ ] Test profile reproducible.
- [ ] Baseline captured.
- [ ] CI/release policy mapped where applicable.
- [ ] Marketplace/package policy mapped where applicable.
- [ ] Alert/monitor policy mapped where applicable.
- [ ] Rollback/override behavior documented.