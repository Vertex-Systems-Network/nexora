# Nexora Performance & Code Quality Intelligence Platform

## Purpose

Nexora needs a first-class performance system, not only a final Core Web Vitals check. The platform must explain frontend speed, Admin/backend page load, server execution, database/cache/network cost, Theme/Extension impact and code-quality regressions with evidence that can be acted on before release.

This system complements rather than replaces existing roadmap work:

- `FRONTEND-RUNTIME-200` owns delivery optimization primitives such as cache/CDN/image/rendering runtime.
- `PERFORMANCE-FOUNDATION-200` owns instrumentation, attribution, budgets and regression primitives.
- `CODE-QUALITY-200` owns maintainability/runtime-cost static analysis for Core and installable packages.
- `PERFORMANCE-INTELLIGENCE-200` owns the PageSpeed/GTmetrix-class user-facing analysis/monitoring product.
- `OBSERVABILITY-200` owns broad production operations telemetry across the platform.
- `PERF-CWV-CERT-100` is the final production certification gate using the evidence created by the systems above.

## Product objective

A Nexora operator or developer should be able to answer:

1. Is this page fast for real users and in controlled lab conditions?
2. What exactly delayed the page?
3. Was the cost browser, network, backend, database, cache, external service, Theme, Extension, Studio component or Core?
4. What changed compared with the last deploy/version/branch?
5. Which code or package caused the regression?
6. What can be fixed, and what measurable improvement is expected?
7. Can this release/package be promoted without violating its performance budget?

## Benchmark capability target

Nexora should cover the useful capability class represented by PageSpeed Insights, Lighthouse, GTmetrix and advanced web-performance tools while adding Nexora-native source attribution.

### Required benchmark-equivalent capabilities

- mobile and desktop lab tests;
- field/RUM Core Web Vitals;
- LCP, INP and CLS with current thresholds plus supporting metrics such as FCP, TTFB and lab blocking/main-thread diagnostics;
- waterfall/resource timing;
- page composition by request type/domain/bytes;
- filmstrip/speed visualization/video where the runner supports it;
- network/CPU/device/viewport profiles;
- cold-cache and warm-cache tests;
- repeat runs and variability/median analysis;
- scheduled monitoring, history and alerts;
- comparison across URLs, deployments, branches, package versions and test profiles;
- actionable opportunities with evidence, not only a single opaque score.

### Nexora differentiation

External performance tools see the browser/network result. Nexora should additionally know the platform source identity behind it:

- Theme and exact Theme version;
- Extension/App/Integration/Studio-Pack identity and version;
- registered hook/event/filter/slot/component;
- server route/controller/service span;
- database query/cache/network span;
- Studio component/template;
- asset ownership;
- release/branch/deployment identity.

The result should be able to say, for example, that an Extension added 180 ms backend time, 4 database queries, 72 KB JavaScript, one long task and 140 ms LCP delay, rather than only saying that the page is slow.

## Architecture

```text
Page / Admin Route / API / User Flow
              |
              v
     Performance Test Orchestrator
      /        |         |        \
     /         |         |         \
Lab Runner   RUM       Backend     Static/Build
Browser      Intake    Profiler    Quality Engine
     \         |         |         /
      \        |         |        /
       Attribution & Correlation Layer
              |
      Evidence / Trace / Result Store
              |
   Budget + Regression Evaluation Engine
              |
    Performance Intelligence Center
              |
  Alerts / Release Gates / Marketplace / AI
```

The system must use provider/adaptor contracts. Lighthouse/Chromium, CrUX or another field provider, remote runners and static-analysis tools are replaceable integrations, not hard-coded product dependencies.

## Stage 1 — PERFORMANCE-FOUNDATION-200

### Objective

Create the performance measurement substrate before advanced reporting/AI. This stage must be production-safe, low-overhead and package-aware.

### Frontend/browser instrumentation

Collect or ingest, where applicable:

- LCP;
- INP;
- CLS;
- FCP;
- TTFB/navigation timing;
- resource timing;
- long tasks/main-thread blocking;
- JS parse/compile/execute time;
- layout/style/paint time where trace data exists;
- DOM/node pressure;
- request count/transfer/decoded size;
- image/font/CSS/JS ownership;
- third-party origin cost;
- cache/CDN status where observable;
- bfcache eligibility/restore behavior where supported;
- errors and failed resources relevant to the measured run.

### Admin/backend page-load instrumentation

Measure both the browser and server parts of Admin/Studio pages:

- navigation/request time;
- Inertia/API request duration;
- frontend render/hydration/update time;
- JS CPU/long tasks;
- API waterfall;
- route/middleware/auth/authorization/controller/service timings;
- template/SSR/render timings where applicable;
- database query count/time/duplicate queries/N+1 indicators;
- cache reads/writes/hit/miss behavior;
- outbound HTTP/service calls;
- filesystem/object-storage operations where instrumentable;
- queue/job dispatch contribution;
- CPU/wall time and peak memory;
- exception/retry contribution.

Deep profiling must be sampled or explicitly activated in production so the profiler itself does not become the performance problem.

### Theme/Extension attribution

Every installable package should have stable telemetry identity. Instrument public registries and execution boundaries so measurements can be attributed to:

- package family;
- package ID/version;
- event/hook/filter/slot;
- registered route/job/action/component;
- frontend asset/chunk;
- template/component render;
- DB/network/cache operations caused within an attributed execution span.

Store inclusive and exclusive time where possible. Nested calls must not double-count total cost.

### Performance budgets

Support budgets by:

- platform;
- public page class;
- Admin page class;
- Theme;
- Extension/App/Integration/Studio-Pack;
- template/component;
- API endpoint;
- user-flow scenario;
- release train.

Budget dimensions should include at least:

- p75 field LCP/INP/CLS;
- lab LCP/TBT or equivalent blocking diagnostics/CLS;
- TTFB/server response;
- total transfer size;
- JS/CSS/image/font size;
- request count;
- long-task/main-thread budget;
- backend wall time;
- DB time/query count;
- peak memory;
- package-attributed time/bytes/queries.

Budget thresholds must be versioned and configurable. Raw values and test conditions remain visible even when a grade exists.

### Regression model

A result can be compared with:

- last known good baseline;
- previous release;
- current production;
- branch/staging candidate;
- Theme version A vs B;
- Extension enabled vs disabled;
- package version A vs B;
- cold vs warm cache;
- mobile vs desktop;
- dataset size profile.

Statistically noisy lab metrics should use repeated runs/median or another documented method before a hard release failure.

## Stage 2 — CODE-QUALITY-200

### Objective

Provide package-aware code-quality evidence that complements security SAST and runtime profiling.

Security scanners answer whether code is dangerous. Code Quality answers whether code is maintainable, unnecessarily expensive, duplicated, bloated or likely to cause regressions.

### Core/Theme/Extension quality dimensions

Where language/tooling permits, analyze:

- type/static-analysis errors;
- lint violations;
- cyclomatic/cognitive complexity thresholds;
- large functions/classes/components;
- duplication;
- dead/unreachable/unused code;
- unused imports/assets/styles;
- dependency weight and dependency duplication;
- bundle/chunk size and tree-shaking effectiveness;
- source-map/build anomalies;
- blocking/synchronous operations on hot paths;
- N+1/duplicate-query patterns found by runtime evidence;
- inefficient hook/event registration or repeated work;
- excessive frontend listeners/observers/timers;
- excessive DOM/component output;
- unbounded loops/collections or high-memory transformations when detectable;
- test coverage/critical-path test presence as evidence, without treating coverage percentage alone as quality.

### Runtime + static correlation

Static findings should link to runtime evidence when possible:

```text
source file / package
      -> execution span
      -> page/API/user flow
      -> measurable cost
```

This is a key Nexora differentiator from generic static analyzers.

### Quality policy

- Core has mandatory quality gates.
- First-party packages have mandatory quality gates.
- Marketplace packages receive a reproducible quality/performance profile.
- Third-party packages may be blocked only by explicit policy; a transparent warning/grade must not masquerade as a security decision.
- Security and performance/quality verdicts remain separate dimensions.

## Stage 3 — PERFORMANCE-INTELLIGENCE-200

### Product name

Working product surface: **Nexora Performance Intelligence** / **Performance Center**.

### Report experience

Each test/report should expose:

1. Overview
2. Field Experience / RUM
3. Lab Web Vitals
4. Waterfall
5. Filmstrip/Video
6. Frontend/Main Thread
7. Backend Trace
8. Database/Cache/External Calls
9. Theme & Extensions
10. Assets/Third Parties
11. Code Quality
12. Compare
13. History
14. Opportunities/Recommendations
15. Evidence/Test Configuration

### Synthetic runner profiles

Support reproducible profiles for:

- mobile/desktop;
- viewport/device presets;
- network throttling;
- CPU throttling;
- test location/runner;
- browser engine where supported;
- cold/warm cache;
- authenticated and anonymous flows;
- locale;
- consent profile;
- data-size fixture/profile;
- scripted navigation/user flow.

Lighthouse-style navigation/timespan/snapshot concepts can be supported through provider adapters; Nexora's canonical result schema must remain provider-neutral.

### Field/RUM

Own-site field telemetry should not depend exclusively on an external provider. Store privacy-safe aggregates and support provider adapters for external field datasets where useful.

Field analysis should use appropriate percentiles, especially p75 for Core Web Vitals, and segment by useful dimensions without creating user fingerprinting risk.

### Monitoring/history/alerts

- scheduled test profiles;
- deploy/release-triggered tests;
- history/trend graphs;
- regression detection;
- threshold alerts;
- package update alerts;
- Theme update alerts;
- before/after comparisons;
- environment and test-profile identity;
- retention policies.

### External/public URL testing

Nexora may support PageSpeed/GTmetrix-style analysis of arbitrary public URLs only through a hardened test-runner boundary.

Rules:

- do not turn the application server into an open proxy;
- deny localhost/private/reserved/link-local/metadata networks;
- resolve and revalidate DNS safely;
- re-check redirects;
- bound body size/time/requests;
- isolate browser workers;
- prevent browser runner access to platform secrets/internal networks;
- rate limit/queue tests;
- record target/test configuration.

Managed Cloud can provide geographically distributed runners later. Self-hosted deployments can use local/registered workers under the same runner contract.

### Performance score/grade

Nexora may expose a simple grade for usability, but:

- raw metrics are primary;
- formula/version is public and stored with the report;
- lab and field are not silently mixed;
- frontend, backend, package and code-quality dimensions remain independently visible;
- Core Web Vitals are never redefined by a proprietary score;
- a package quality grade is not a security/trust verdict.

Suggested dimensions:

- User Experience;
- Delivery;
- Main Thread/Execution;
- Server/Database;
- Package Impact;
- Code Quality.

## AI integration

After `AI-KERNEL-100`, Performance Intelligence may expose typed read/draft tools such as:

- explain a regression;
- rank likely root causes;
- estimate impact of candidate fixes;
- propose asset/query/cache/code changes;
- generate a remediation plan;
- draft code changes through AI-DX;
- compare pre/post-fix evidence.

AI must not invent PASS evidence, change production settings, disable packages or publish patches autonomously unless the relevant typed tool, capability and approval policy explicitly allow that side effect.

## Theme/Extension performance lifecycle

Performance is part of package lifecycle, not an afterthought.

### Before activation/update where policy requires

- validate manifest/performance declaration;
- run static/build quality checks;
- execute representative sandbox/staging performance scenarios;
- record baseline package profile;
- warn/block according to explicit site/Marketplace policy.

### After activation/update

- monitor attributed cost;
- detect regressions;
- compare old/new version;
- allow rollback recommendation;
- feed Marketplace compatibility/quality evidence where permitted.

A Theme/Extension cannot claim a performance badge without reproducible test profile + evidence identity.

## Integration with Release Workflow

Release promotion can require selected performance gates:

```text
Draft/Branch
   -> build/static quality
   -> staging synthetic tests
   -> backend/package budgets
   -> optional field/canary evidence
   -> approval
   -> production promotion
```

Performance failure must not silently mutate production. Override, when allowed, requires permission, reason and audit record.

## Integration with Marketplace

Marketplace 2.0 should consume:

- reproducible package test profile;
- supported Nexora version;
- source/build identity;
- frontend asset impact;
- representative backend cost;
- code-quality result;
- performance budget status;
- version-to-version regression history.

This is quality evidence, separate from Sentinel security trust.

## Privacy and sensitive-data rules

Performance telemetry must not leak:

- passwords/tokens/session IDs;
- Authorization headers;
- raw secrets;
- request bodies by default;
- unredacted SQL bindings;
- sensitive URLs/query strings;
- private user content where not required.

RUM follows `PRIVACY-CONSENT-100`; deep server traces use retention/redaction/access controls.

## Definition of Done

This platform is not complete because a Lighthouse score can be displayed. Required closure includes:

- provider-neutral canonical result model;
- reproducible test profiles;
- frontend + Admin + backend instrumentation;
- package attribution;
- code-quality correlation;
- budgets/regression engine;
- history/compare/alerts;
- secure external runner model;
- release/Marketplace integration;
- privacy/redaction;
- APIs/AI read surfaces;
- source tests and real-target evidence;
- demonstrated detection of at least one intentionally introduced frontend, backend, database and package regression.

## Final certification relationship

`PERF-CWV-CERT-100` remains the final release gate. It consumes Performance Intelligence evidence and verifies production budgets/Core Web Vitals across the release's defined target matrix. It must not be collapsed into the product implementation stage.