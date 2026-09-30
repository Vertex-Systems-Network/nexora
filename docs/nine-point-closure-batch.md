# Nine-point closure batch — measured plan

Status: BLOCKED / NOT COMPLETE. Date: 2026-09-30.

## Source binding and evidence

- Protected main: 9d1c89eed4fc67d4e1a3798992d36d5a056e3ca5; final source certification run 36692809871 SUCCESS.
- Product draft #1: f6629d010626bb52be16fd9c3257f41b656be748, target main; historical head QA 33566801077 SUCCESS.
- Reconciliation: 746 ahead / 148 behind; complete tree comparison 292 changed existing blobs, 161 additions, 30 removals.
- After fetching full Git history, git merge-tree reports 38 content-conflict records. No conflicts are resolved or runtime behavior verified by this plan.
- Git source access is available. Earlier shallow-history merge failure was corrected by fetching full history; it is not a repository unrelated-history defect.
- Native approval baseline is applied: one approval, stale approval dismissal, latest-push approval, strict governance, resolved threads, no bypass actors.
- PR #71 exposed the installation-local environment bootstrap key. An external provider account is not established as necessary. No secret values are retained here.

## Batch acceptance and execution order

| Point | Work | Required acceptance | Current result |
|---|---|---|---|
| 1 | Security review enforcement (#87) | Eligible independent reviewer; exact-head approval; selective risk/waiver/base-binding requirements verified | Native baseline applied; full issue incomplete |
| 2 | Exposed local key (#72) | Establish affected installation/key lifecycle; accepted invalidation or non-use evidence; preserve encrypted data | Credential identified; lifecycle unresolved |
| 3 | Disposable current-source installation | Real installer commit on bound source/environment; cleanup/recovery controls | No target provisioned |
| 4 | Runtime readiness (#74) | Fresh status=pass, ready=true, runtime_ready=true, receipt_current=true, errors=[] | Not executed |
| 5 | CLI/web identity (#74) | Fresh one-time challenge consumed and verified by exact target | Not executed |
| 6 | Login (#74) | Same proven origin, verified TLS, no redirect substitution, correct authentication behavior | Not executed |
| 7 | Core functional QA | Registered Core QA scope, meaningful positive/negative role/tenant/workflow tests; fresh controlled runner evidence | Formal target gate pending |
| 8 | Draft #1 reconciliation | Resolve every conflict by contract, retain main security/tooling controls and intended product behavior, review exact combined diff and required CI | 38 conflicts measured; unresolved |
| 9 | Final release | Applicable five-engine DB matrix, provider/HA, backup/restore/upgrade rehearsal, browsers/assistive technology/performance/accessibility and signed release/provenance evidence | Not executed |

Execute source reconciliation on an isolated branch/worktree. For each conflict, compare merge-base/main/draft contracts and tests; record chosen behavior. Do not choose one side globally or weaken assertions. Runtime, security and workflow conflicts require independent review. Refresh source manifest bindings only after final code decisions. Preserve historical evidence as historical.

Then execute fresh source certification on the exact combined head. Provision disposable targets through an available authorized execution capability; hosted source QA alone is not Target or Release acceptance. Accept target evidence only through current source-bound readiness/identity/login and relevant certification tools. Resolve key lifecycle before runtime stage promotion. Perform formal Core QA and final release only when their dependencies are genuinely accepted.

## Impact and rollback

This change is planning/documentation only. No runtime code, workflows, permissions, schema, dependency locks or secrets are changed. Existing stage/target/release gates are preserved. No new implementation unit begins in this patch; later source reconciliation must bind existing registered unit IDs and the active plan to its exact write scope before mutation. Rollback is a normal revert of this documentation change.

## Conflict inventory

- .ai/handoff/current.md
- .ai/plans/active.md
- .ai/state.json
- .github/workflows/release-certification.yml
- AGENTS.md
- NEXORA_AI_PROJECT_STATE.md
- app/Console/Commands/Nexora/SourceActivateCommand.php
- app/Http/Middleware/RuntimeNodeHeartbeat.php
- app/Jobs/SendNewsletterDelivery.php
- app/Nexora/Installation/InstallationRunControl.php
- app/Nexora/Installation/SourceActivationIdentity.php
- app/Nexora/Modules/Core/PublishingModule.php
- app/Nexora/Publishing/Services/ArticlePublishingManager.php
- bootstrap/nexora-source-manifest.json
- config/installer.php
- scripts/lib/target-composer.php
- scripts/n1-source-activate.bat
- scripts/performance-build-verify.php
- scripts/target-environment-bootstrap.php
- tests/Architecture/N015DataConnectionsArchitectureTest.php
- tests/Architecture/N027AutomationArchitectureTest.php
- tests/Architecture/N100C5BrowserAccessibilityPerformanceArchitectureTest.php
- tests/Architecture/N100Rc5DatabaseArchitectureTest.php
- tests/Architecture/N100V30DistributedUpgradeArchitectureTest.php
- tests/Architecture/N100V35RuntimeActivationArchitectureTest.php
- tests/Architecture/N100V39ServiceDataPlaneArchitectureTest.php
- tests/Architecture/N100V40HostClockArchitectureTest.php
- tests/Architecture/N100V41ResourceEnvelopeArchitectureTest.php
- tests/Architecture/N100V42PolicyPlaneArchitectureTest.php
- tests/Architecture/N100V56InstallerRuntimeReadinessArchitectureTest.php
- tests/Feature/Certification/InstallerRecoveryCertificationTest.php
- tests/Feature/Certification/SecurityBoundaryCertificationTest.php
- tests/Feature/IdentityAccessFlowTest.php
- tests/Feature/Media/MediaLibraryFlowTest.php
- tests/Feature/SettingsFlowTest.php
- tests/Feature/Themes/ThemeEngineFlowTest.php
- tests/Unit/Cloud/HaReadinessServiceTest.php
- tests/Unit/Security/PasswordStrengthEvaluatorTest.php

## Human/capability boundary

The author cannot supply the independent approval required by protected main. No current target, PHP/Composer/container runtime or provider lifecycle evidence is available in the current execution workspace. Missing capabilities are not PASS or N/A waivers. No blanket 9-point completion, credential invalidation, target verification or release approval is claimed.
