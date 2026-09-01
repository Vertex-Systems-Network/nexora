# Nexora Capability Matrix — Phase 4 Performance & Code Quality Addendum

This addendum records the performance/code-quality requirements accepted after the Phase 3 market/security audit. It is canonical together with the base capability matrix and Phase 3 addendum.

## 1. Existing performance work preserved

| Capability | Existing plan state | Destination | Stage |
|---|---|---|---|
| Frontend cache/CDN/image pipeline | LEGACY_PLANNED | optimized delivery runtime | `FRONTEND-RUNTIME-200` |
| Rendering budgets | LEGACY_PLANNED | enforceable platform/page budgets | `FRONTEND-RUNTIME-200` + `PERFORMANCE-FOUNDATION-200` |
| Core Web Vitals certification | LEGACY_PLANNED | final exact-release production quality gate | `PERF-CWV-CERT-100` |
| General logs/metrics/traces operations center | LEGACY_PLANNED | broad operational observability | `OBSERVABILITY-200` |
| Search/SEO crawler page-speed observations | FOUNDATION | retain as SEO evidence, not full profiler | existing N0.26 / `SEO-SEARCH-CLOSURE-001` |

## 2. Frontend performance foundation

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Lab browser performance model | GAP / external tooling possible | provider-neutral synthetic result model | `PERFORMANCE-FOUNDATION-200` |
| Mobile/desktop profiles | GAP | reproducible test profiles | `PERFORMANCE-FOUNDATION-200` |
| Network/CPU/viewport profiles | GAP | reproducible throttled scenarios | `PERFORMANCE-FOUNDATION-200` |
| Cold/warm cache comparison | GAP | explicit cache-mode evidence | `PERFORMANCE-FOUNDATION-200` |
| Resource timing/ownership | GAP | request/bytes/domain/type + source ownership | `PERFORMANCE-FOUNDATION-200` |
| Main-thread/JS execution profiling | GAP | parse/compile/execute/long-task evidence | `PERFORMANCE-FOUNDATION-200` |
| Filmstrip/video support | GAP | runner capability with canonical attachment model | `PERFORMANCE-INTELLIGENCE-200` |
| Waterfall | GAP | interactive request timeline and headers/cache evidence | `PERFORMANCE-INTELLIGENCE-200` |
| Page composition | GAP | bytes/requests by type/domain/package | `PERFORMANCE-INTELLIGENCE-200` |

## 3. Real-user field performance

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| LCP p75 | GAP as first-party RUM | privacy-safe field aggregate | `PERFORMANCE-FOUNDATION-200` |
| INP p75 | GAP as first-party RUM | privacy-safe field aggregate | `PERFORMANCE-FOUNDATION-200` |
| CLS p75 | GAP as first-party RUM | privacy-safe field aggregate | `PERFORMANCE-FOUNDATION-200` |
| FCP/TTFB supporting field metrics | GAP | segmented evidence | `PERFORMANCE-FOUNDATION-200` |
| External field provider adapters | GAP | optional CrUX/other adapters | `PERFORMANCE-INTELLIGENCE-200` |
| Field history/segmentation | GAP | controlled device/region/connection trends without fingerprinting | `PERFORMANCE-INTELLIGENCE-200` |
| Consent integration | planned | RUM obeys site privacy/consent policy | `PRIVACY-CONSENT-100` + performance stages |

## 4. Backend/Admin performance

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Server request trace | GAP | route-to-response span tree | `PERFORMANCE-FOUNDATION-200` |
| Middleware/auth/authorization timings | GAP | attributed server timing | `PERFORMANCE-FOUNDATION-200` |
| Controller/service timing | GAP | attributed execution spans | `PERFORMANCE-FOUNDATION-200` |
| Template/SSR/render timing | GAP | render evidence | `PERFORMANCE-FOUNDATION-200` |
| DB total time/query count | partial diagnostics likely | canonical per-request evidence | `PERFORMANCE-FOUNDATION-200` |
| Duplicate/N+1 query detection | GAP | evidence-backed finding | `PERFORMANCE-FOUNDATION-200` + `CODE-QUALITY-200` |
| Cache hit/miss/cost | GAP | traceable cache contribution | `PERFORMANCE-FOUNDATION-200` |
| Outbound HTTP/service cost | partial runtime controls | traceable time/count | `PERFORMANCE-FOUNDATION-200` |
| Filesystem/object-storage cost | GAP | traceable time/count | `PERFORMANCE-FOUNDATION-200` |
| CPU/wall/peak-memory evidence | GAP | sampled/bounded profiling | `PERFORMANCE-FOUNDATION-200` |
| Admin/Studio frontend + backend correlation | GAP | one trace/report across browser/API/server | `PERFORMANCE-INTELLIGENCE-200` |

## 5. Theme/Extension performance attribution

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Package telemetry identity | GAP | package family/ID/version on relevant spans/assets | `PERFORMANCE-FOUNDATION-200` |
| Hook/event/filter/slot attribution | GAP | inclusive/exclusive cost by registration | `PERFORMANCE-FOUNDATION-200` |
| Theme template/component cost | GAP | render + asset ownership | `PERFORMANCE-FOUNDATION-200` |
| Package DB/cache/network attribution | GAP | package-correlated resource cost | `PERFORMANCE-FOUNDATION-200` |
| Package JS/CSS/request contribution | GAP | browser/network ownership | `PERFORMANCE-FOUNDATION-200` |
| Enable/disable comparison | GAP | controlled package impact experiment | `PERFORMANCE-INTELLIGENCE-200` |
| Package version A/B comparison | GAP | update-regression evidence | `PERFORMANCE-INTELLIGENCE-200` |
| Marketplace performance profile | GAP | reproducible evidence separate from security trust | `PERFORMANCE-INTELLIGENCE-200` + `MARKETPLACE-200` |

## 6. Performance budgets/regression gates

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Page-class budgets | partial concept | versioned enforceable budget | `PERFORMANCE-FOUNDATION-200` |
| Admin/API budgets | GAP | route/API budget policy | `PERFORMANCE-FOUNDATION-200` |
| Theme/package budgets | GAP | versioned package budgets | `PERFORMANCE-FOUNDATION-200` |
| Bundle/asset budgets | partial concept | CI/release enforcement | `PERFORMANCE-FOUNDATION-200` |
| Backend query/time/memory budgets | GAP | CI/staging evidence | `PERFORMANCE-FOUNDATION-200` |
| Baseline comparison | GAP | last-good/release/branch/package baseline | `PERFORMANCE-FOUNDATION-200` |
| Noise/variance policy | GAP | repeated runs/statistical rule before hard fail | `PERFORMANCE-FOUNDATION-200` |
| Release promotion gate | GAP | warn/block/override-with-audit | `RELEASE-WORKFLOW-200` + performance stages |
| Performance budget template | SOURCE_DONE on AI branch | reusable plan artifact | performance planning |

## 7. Code quality system

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Type/static quality | partial existing checks | standardized Core/package result | `CODE-QUALITY-200` |
| Lint quality | partial existing checks | standardized result | `CODE-QUALITY-200` |
| Complexity analysis | GAP | versioned thresholds | `CODE-QUALITY-200` |
| Duplication | GAP | package/Core findings | `CODE-QUALITY-200` |
| Dead/unused code | GAP | actionable findings | `CODE-QUALITY-200` |
| Unused CSS/JS/assets | GAP | build/browser evidence | `CODE-QUALITY-200` |
| Bundle/chunk/dependency weight | partial build evidence | attributable quality finding | `CODE-QUALITY-200` |
| Runtime/static correlation | GAP | source -> span -> page/user-flow evidence | `CODE-QUALITY-200` + `PERFORMANCE-INTELLIGENCE-200` |
| Security verdict separation | POLICY | quality cannot masquerade as Sentinel trust | `CODE-QUALITY-200` |
| Package quality profile | GAP | reproducible Marketplace/developer evidence | `CODE-QUALITY-200` |

## 8. PageSpeed/GTmetrix-class Performance Center

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| One-page performance report | GAP | unified overview with raw evidence | `PERFORMANCE-INTELLIGENCE-200` |
| Web Vitals diagnostics | GAP as product | field + lab diagnostic views | `PERFORMANCE-INTELLIGENCE-200` |
| Waterfall | GAP | request-level analysis | `PERFORMANCE-INTELLIGENCE-200` |
| Filmstrip/video | GAP | visual loading evidence | `PERFORMANCE-INTELLIGENCE-200` |
| Multi-profile testing | GAP | device/network/CPU/cache/browser/location profiles | `PERFORMANCE-INTELLIGENCE-200` |
| Scripted user-flow tests | GAP | login/interactions/navigation scenarios | `PERFORMANCE-INTELLIGENCE-200` |
| Scheduled monitoring | GAP | daily/weekly/custom policy | `PERFORMANCE-INTELLIGENCE-200` |
| History/trends | GAP | deploy/package/version-aware trends | `PERFORMANCE-INTELLIGENCE-200` |
| Alerts | GAP | threshold/regression alerts | `PERFORMANCE-INTELLIGENCE-200` |
| Compare reports | GAP | URL/branch/release/theme/package comparisons | `PERFORMANCE-INTELLIGENCE-200` |
| Transparent performance grade | GAP | versioned formula; raw metrics primary | `PERFORMANCE-INTELLIGENCE-200` |
| External public URL tests | GAP | isolated SSRF-safe runner model | `PERFORMANCE-INTELLIGENCE-200` |
| Distributed locations | FUTURE | managed/self-hosted runner providers | `PERFORMANCE-INTELLIGENCE-200` + `MANAGED-CLOUD-100` |

## 9. AI-assisted performance improvement

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Explain regression | GAP | evidence-grounded AI tool | `AI-KERNEL-100` + `PERFORMANCE-INTELLIGENCE-200` |
| Root-cause ranking | GAP | trace/budget/finding-grounded analysis | `PERFORMANCE-INTELLIGENCE-200` |
| Remediation plan | GAP | typed draft action plan | `PERFORMANCE-INTELLIGENCE-200` + `AI-DX-100` |
| Patch proposal | GAP | draft code changes with tests, never fabricated PASS | `AI-DX-100` |
| Pre/post fix comparison | GAP | evidence-loop verification | `PERFORMANCE-INTELLIGENCE-200` |
| Autonomous production optimization | MUST NOT BE DEFAULT | explicit typed capability + approval required | AI safety policy |

## 10. Final performance certification

`PERF-CWV-CERT-100` remains a separate production gate and must consume:

- defined target/test matrix;
- field/lab Core Web Vitals evidence where applicable;
- frontend delivery budgets;
- backend/database/cache budgets;
- Theme/Extension performance profiles;
- code-quality blocking findings policy;
- release-regression evidence;
- production-like/real-target runs.

The product implementation stages must not mark final certification PASS themselves.