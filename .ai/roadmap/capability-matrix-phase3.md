# Nexora Capability Matrix — Phase 3 Market, Security & AI Development Addendum

This addendum extends `.ai/roadmap/capability-matrix.md`. It is canonical for capabilities accepted after the Phase 2 matrix was created.

## 1. Development governance

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Pre-planned development unit registry | SOURCE_DONE on AI branch | every implementable unit registered before code | `AI-GOV-001` |
| Development-unit JSON schema | SOURCE_DONE on AI branch | machine-validation contract | `AI-GOV-001` |
| Mandatory intake protocol | SOURCE_DONE on AI branch | no hidden/unplanned implementation | `AI-GOV-001` |
| Mandatory active plan template | SOURCE_DONE on AI branch | architecture/security/data/API/AI/rollback plan before code | `AI-GOV-001` |
| CI governance validator | NEW_REQUIRED | reject missing units, invalid dependencies, stale state/handoff, missing plan/DoD | `AI-GOV-AUTOMATION-100` |
| AI-discovered optional feature promotion rule | SOURCE_DONE policy | proposed first; implement only when approved/required by active scope | `AI-GOV-001` |
| Independent review for high-risk AI work | PLANNED policy | separate review evidence + automated checks | `AI-GOV-AUTOMATION-100` / security program |

## 2. Professional release/publishing workflow

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Preview environment/URLs | GAP / partial preview foundations may exist | durable preview of draft content/design | `RELEASE-WORKFLOW-200` |
| Staging environment/state | GAP | explicit staging before production | `RELEASE-WORKFLOW-200` |
| Page/content branching | GAP | isolated branches without production mutation | `RELEASE-WORKFLOW-200` |
| Merge/conflict resolution | GAP | deterministic conflict detection/merge | `RELEASE-WORKFLOW-200` |
| Single-page/selective publishing | GAP | publish selected changes only | `RELEASE-WORKFLOW-200` |
| Multi-page release bundle | GAP | grouped atomic-ish publishing plan | `RELEASE-WORKFLOW-200` |
| Scheduled release | PARTIAL publishing schedules exist | release-level schedules | `RELEASE-WORKFLOW-200` |
| Environment promotion | GAP | preview/staging -> production promotion model | `RELEASE-WORKFLOW-200` |
| Release history/rollback | FOUNDATION in some subsystems | coherent content/design release rollback | `RELEASE-WORKFLOW-200` |

## 3. Templates, patterns and acceleration

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Site starter kits | GAP | one-click dependency-aware starter sites | `TEMPLATE-ECOSYSTEM-100` |
| Page templates | PARTIAL/unknown | reusable versioned page templates | `TEMPLATE-ECOSYSTEM-100` |
| Section/pattern library | GAP | reusable sections/patterns | `TEMPLATE-ECOSYSTEM-100` |
| Component kits/design systems | GAP | reusable tokens/components/variants | `TEMPLATE-ECOSYSTEM-100` |
| Commerce/blog/portfolio kits | GAP | product-specific starter kits without Core hardcoding | `TEMPLATE-ECOSYSTEM-100` |
| Safe derived customization | GAP | local overrides without losing upstream update path | `TEMPLATE-ECOSYSTEM-100` |
| AI starter blueprints | GAP | governed AI-generated starter blueprints | `AI-DESIGN-100` + `TEMPLATE-ECOSYSTEM-100` |

## 4. Privacy and consent

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| GPC/DNT analytics suppression | FOUNDATION | preserved baseline | existing N0.26 |
| Consent manager | GAP | first-party consent decision service | `PRIVACY-CONSENT-100` |
| Cookie/processing categories | GAP | necessary/analytics/marketing/custom categories | `PRIVACY-CONSENT-100` |
| Consent-aware analytics/forms/experiments | GAP | cross-system consent propagation | `PRIVACY-CONSENT-100` |
| Retention policies | PARTIAL subsystem-specific | unified policy hooks | `PRIVACY-CONSENT-100` |
| User data export/delete workflows | GAP | typed auditable workflows | `PRIVACY-CONSENT-100` |
| Regional policy adapters | GAP | configurable policy/consent adapters | `PRIVACY-CONSENT-100` |

## 5. SEO/AEO/AI-readable web

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Canonical SEO metadata/schema/sitemap | FOUNDATION | production closure | existing + `SEO-AI-200` |
| AI-readable structured representation | GAP | safe machine-readable representation for agents/search systems | `SEO-AI-200` |
| Entity/brand knowledge graph consistency | PARTIAL Schema Graph | stronger entity graph/intelligence | `SEO-AI-200` |
| AI crawler policy/audit | GAP | explicit agent/crawler policy and evidence | `SEO-AI-200` |
| Citation/answer visibility monitoring | GAP | evidence-driven AI answer/citation analytics | `SEO-AI-200` |
| AEO recommendations | GAP | structured evidence/remediation, not synthetic vanity score | `SEO-AI-200` |

## 6. External AI-agent interoperability

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| External agent gateway | GAP | provider/protocol-neutral gateway | `AGENT-INTEROP-100` |
| OAuth/scoped agent identity | GAP | user/tenant-bound identity | `AGENT-INTEROP-100` |
| Capability negotiation | GAP | explicit read/draft/execute capability set | `AGENT-INTEROP-100` |
| Typed agent tools | planned AI Kernel | same public Tool Registry as native AI | `AI-KERNEL-100` + `AGENT-INTEROP-100` |
| Risk-based approval | planned | approval before sensitive side effects | `AI-KERNEL-100` |
| Immutable action audit | planned | requested/approved/executed evidence | `AI-KERNEL-100` |
| Protocol adapters | FUTURE | MCP/WebMCP/other adapters without core dependency on one protocol | `AGENT-INTEROP-100` |

## 7. Experimentation and personalization

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| A/B testing | GAP | deterministic experiment assignment | `EXPERIMENTATION-100` |
| Multivariate testing | GAP | controlled variant matrix | `EXPERIMENTATION-100` |
| Goals/conversion metrics | analytics foundation | explicit experiment goals | `EXPERIMENTATION-100` |
| Safe percentage rollout | GAP | bounded rollout + rollback | `EXPERIMENTATION-100` |
| AI-generated variants | GAP | draft variants through design/content contracts | `EXPERIMENTATION-100` + AI stages |
| Winner analysis | GAP | evidence-based result analysis | `EXPERIMENTATION-100` |
| Audience/segment personalization | GAP | privacy-safe deterministic targeting | `PERSONALIZATION-100` |
| Fallback/default experience | GAP | deterministic no-data/no-consent fallback | `PERSONALIZATION-100` |

## 8. Design-system interoperability

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Figma/design import | GAP | structured tokens/components/layout AST import | `DESIGN-IMPORT-100` |
| Token mapping | design token foundation | import/map/validate external tokens | `DESIGN-IMPORT-100` |
| Component mapping | GAP | map external components to Nexora components | `DESIGN-IMPORT-100` |
| Responsive inference | GAP | infer then validate breakpoints/layout | `DESIGN-IMPORT-100` |
| Raw executable markup trust | MUST NOT IMPLEMENT | imported designs remain data/AST, not trusted code | security invariant |

## 9. Capability-bounded full-stack app runtime

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Functions/actions | extension foundation only | bounded app functions through runtime contracts | `APP-RUNTIME-100` |
| Jobs/queues | Core foundation | package/app-safe job registration/execution | `APP-RUNTIME-100` |
| Schedules | automation/runtime foundations | bounded scheduled app actions | `APP-RUNTIME-100` |
| Secret broker | GAP / encrypted secrets exist in domains | scoped non-exportable secret access | `APP-RUNTIME-100` + security |
| Network broker | partial capability vocabulary | egress policy/allowlist/timeouts/SSRF defense | `APP-RUNTIME-100` + security |
| Isolated executable backend | GAP | process/WASM/container strategy for untrusted executable code | `SENTINEL-200` |

## 10. Managed Cloud product

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Cloud/HA runtime primitives | FOUNDATION | keep provider-neutral/self-hostable | existing N0.34 closure |
| One-click managed site provisioning | GAP | optional Nexora Managed Cloud | `MANAGED-CLOUD-100` |
| Domains/automatic SSL | GAP as managed product | managed domain/SSL lifecycle | `MANAGED-CLOUD-100` |
| CDN/edge delivery | PLANNED frontend runtime | managed delivery integration | `MANAGED-CLOUD-100` |
| Automated backup/recovery | foundation | managed schedule/evidence | `MANAGED-CLOUD-100` + DR |
| Staging/deploy history | GAP | integrated release workflow | `MANAGED-CLOUD-100` |
| Monitoring/alerts | PLANNED | managed operations | `OBSERVABILITY-200` |
| Usage/metering/billing | GAP | provider-neutral commercial metering | `MANAGED-CLOUD-100` |
| Autoscaling policy | HA foundation | managed scaling controls | `MANAGED-CLOUD-100` |

## 11. Continuous security additions

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Passkeys/WebAuthn | no implementation evidence found in audit | strong admin authentication | `SECURITY-BASELINE-200` |
| TOTP MFA/recovery codes | no implementation evidence found in audit | MFA fallback/recovery | `SECURITY-BASELINE-200` |
| Sensitive-action re-auth | GAP/partial | required for high-risk actions | `SECURITY-BASELINE-200` |
| Strict CSP | no explicit implementation evidence found in audit | nonce/hash CSP and restricted directives | `SECURITY-BASELINE-200` |
| Trusted Types strategy | GAP | DOM sink hardening where supported | `SECURITY-BASELINE-200` |
| SAST/CodeQL-equivalent | CI GAP | continuous analysis | `SECURITY-BASELINE-200` |
| Composer/npm advisory scan | CI GAP | separate security pipeline | `SECURITY-BASELINE-200` |
| Secret scanning | CI GAP | fail on committed secrets | `SECURITY-BASELINE-200` |
| Dependency review | CI GAP | PR supply-chain review | `SECURITY-BASELINE-200` |
| DAST/fuzzing | GAP | disposable-target DAST + high-risk parser/API fuzzing | security program |
| Branch required checks/reviews | current branch protection evidence insufficient | required PR/check/review policy | `SECURITY-BASELINE-200` |
| Isolated third-party executable runtime | explicitly not claimed today | real sandbox backend | `SENTINEL-200` |
| Emergency package revocation | GAP | kill switch/quarantine/revocation | `SENTINEL-200` |

## 12. AI development safety

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Stable AI startup/handoff | SOURCE_DONE on AI branch | preserve | `AI-GOV-001` |
| Registry-first development | SOURCE_DONE policy | machine enforce | `AI-GOV-AUTOMATION-100` |
| New-feature planning before code | SOURCE_DONE policy | machine enforce | `AI-GOV-AUTOMATION-100` |
| Architecture/security/data/API/AI impact plan | SOURCE_DONE template | required per active unit | governance |
| Threat model for high/critical units | SOURCE_DONE policy/template | required evidence | security program |
| Independent review for critical AI code | POLICY | separate review/evidence gate | `AI-GOV-AUTOMATION-100` |
| AI-generated code treated as untrusted contributor | POLICY | tests/review/target evidence before claims | governance/security |
| No unrestricted shell/DB/filesystem/secrets/network for product AI | ARCHITECTURE POLICY | typed least-privilege tools only | `AI-KERNEL-100` |

## Addendum rule

If a new market/security/AI capability is accepted after this document, register it first and either update this addendum or the base capability matrix before implementation. The canonical stage graph and development-unit registry remain the execution authorities.
