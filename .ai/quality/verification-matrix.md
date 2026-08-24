# Nexora Verification Matrix

This matrix defines the minimum evidence class expected before a system closure stage can advance. It is a guard against skipped product workflows.

| System / stage | Source/static evidence | Integration evidence | Real-target/browser evidence | Special evidence |
|---|---|---|---|---|
| RUNTIME-CLOSURE-001 | runtime/source guards | compatibility/readiness commands | required | exact identity-plane output |
| CORE-QA-001 | tests/typecheck as affected | auth/roles/settings/media CRUD | required | direct URL + session persistence |
| ADMIN-UX-CLOSURE-001 | component/type tests | shared Admin shell flows | required | responsive + Light/Dark/System + accessibility baseline |
| THEME-CLOSURE-001 | manifest/security/tests | full theme lifecycle | required | preview/activation/rollback/public render |
| EXTENSION-CLOSURE-001 | manifest/capability/security/tests | full extension lifecycle | required | package family + runtime mode + migration policy |
| STUDIO-CLOSURE-001 | visual-tree/schema/tests | document/theme bindings | required | desktop/tablet/mobile + revision/publish |
| CMS-PUBLISHING-CLOSURE-001 | document/editorial/publishing tests | CRUD/revisions/taxonomy/archive | required | permalink/render/SEO integration |
| MEDIA-DISTRIBUTION-CLOSURE-001 | media/distribution tests | upload/use/newsletter/RSS | required | public-media policy and delivery |
| SEO-SEARCH-CLOSURE-001 | SEO/search/crawler tests | schema/sitemap/index/search | required | actual rendered metadata + crawler evidence |
| AUTOMATION-CLOSURE-001 | trigger/action/webhook tests | retries/idempotency/signatures | target where network/runtime matters | webhook replay/private-network protections |
| MARKETPLACE-CLOSURE-001 | catalog/stager/security tests | stage -> quarantine -> install | required for full closure | publisher/signature/digest evidence |
| COMMERCE-CLOSURE-001 | domain/payment adapter tests | order/invoice/refund/subscription | required | money/tax/idempotency/provider boundaries |
| CRM-MEMBERSHIP-HELPDESK-CLOSURE-001 | domain/authorization tests | end-to-end business workflows | required | entitlement/SLA/history evidence |
| ENTERPRISE-CLOUD-CLOSURE-001 | tenancy/HA/source tests | organization/SSO/runtime integration | required | multi-node/shared-state/failover where applicable |

## Planned 2.0 stages

Each 2.0 stage must add its own row or extend an existing row before implementation is marked `SOURCE_DONE`. An AI agent may not silently treat a generic test suite as sufficient evidence for a new subsystem.

## Evidence naming

Every meaningful handoff should record evidence under these labels:

- `source_checks`
- `integration_checks`
- `target_checks`
- `security_checks`
- `manual_browser_checks`
- `known_gaps`

If a class of evidence was not executed, record it as `NOT_RUN` or `NOT_APPLICABLE`; never omit it in a way that could be mistaken for PASS.