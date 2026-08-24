# Phase 7 Capability Matrix — AI-Native Development Orchestration

Planning addendum only. This does not claim the orchestration runtime is implemented.

## Audit finding

Nexora already had strong pre-planning, quality, security, performance, Flow and evidence-separation principles. The remaining high-value gap was the **development-agent execution layer itself**: who may write, from which exact base/policy, with which tools, how concurrent agents coordinate, who may approve, and what can count as evidence.

`AI-GOV-AUTOMATION-100` is expanded to govern this layer through `.ai/governance/ai-development-orchestration.md` and `.ai/registry/ai-development-units.json`.

| Capability | Existing direction | Phase 7 maturity target |
|---|---|---|
| Pre-planned work | Strong | Preserve; bind every substantial AI run to exact stage/unit/plan digest |
| Instruction hierarchy | Implicit via AGENTS/governance | Explicit trust boundary; issue/log/source/web text is untrusted data, not authority |
| Run identity | No first-class run manifest | Stable run ID + base SHA + policy/plan digests + role/model/tool metadata |
| Context freshness | Re-read HEAD/state | Machine stale detection when HEAD/plan/policy changes |
| Concurrent agents | One active stage policy | Scope/path leases, isolated branches/worktrees, optimistic SHA writes, task DAG |
| Scope creep | Registry/plan update required | Automatic scope-delta gate before new permission/dependency/migration/network/trust work |
| Dev-agent privileges | General safety principles | Explicit least-privilege dev tool capability sandbox |
| Governance self-edit | Not fully protected | Protected control-plane path + anti-self-weakening policy |
| Branch protection | Security plan says protect main | Operationally require PR/reviews/required checks/CODEOWNERS/no-force-push and verify settings |
| Test integrity | Tests required | Protect test oracle against deleted/skipped/weakened assertions; independent critical tests |
| Evidence integrity | Source vs target separated | Evidence authority/envelope/immutability + machine attestations + exact-source provenance |
| Review independence | Distinct review pass for critical | Bind reviewer + exact SHA; stale reviews invalidated; same-run self-approval rejected |
| Retry loops | General retries/reliability | Development attempt budget/circuit breaker + concise hypothesis/action log |
| Dependency additions | Supply-chain/security plans | Dedicated AI dependency intake before convenience installs/upgrades |
| Multi-agent child authority | Not explicit | Child capability subset, merge coordinator, combined-head integration verification |
| Waivers/N/A | Explicit N/A policy | Stable scoped expiring waivers with independent approval for material risk |
| Model/tool drift | Product AI prompt/model versioning | Development run captures available model/tool/policy version and triggers representative evals |
| AI-dev red team | AI eval/security plans | Prompt injection, fake evidence, self-weakening, races, scope escalation, secrets/network abuse |
| Thought/logging privacy | Handoff/state only | Record decisions/actions/evidence, never require private chain-of-thought |
| Promotion | Stage/DoD/evidence | Exact-head promotion contract with non-stale review/evidence/provenance |

## Phase 7 development units

- `SYS-AI-DEV-ORCHESTRATION`
- `SYS-AI-INSTRUCTION-TRUST`
- `SYS-AI-RUN-MANIFEST`
- `SYS-AI-SCOPE-LEASE`
- `SYS-AI-EXECUTION-SANDBOX`
- `SEC-AI-GOVERNANCE-SELF-PROTECTION`
- `SYS-AI-TEST-INTEGRITY`
- `SYS-EVIDENCE-ATTESTATION`
- `SYS-AI-REVIEW-INDEPENDENCE`
- `SYS-AI-ATTEMPT-CIRCUIT`
- `SYS-AI-MULTIAGENT-COORDINATION`
- `SEC-AI-WAIVER-GOVERNANCE`
- `SYS-AI-DEPENDENCY-INTAKE`
- `SEC-AI-DEV-REDTEAM`

## Reference alignment

The planning target aligns with the direction of:

- NIST SSDF outcome/risk-based secure development and continuous improvement;
- NIST AI/GenAI secure-development profile concepts;
- OWASP Agentic AI lifecycle threat modeling, tool/authority/prompt/data risk;
- SLSA source/build provenance and artifact verification;
- existing Nexora zero-skip, fail-closed, source-vs-target and public-contract rules.

External standards are guidance inputs, not Nexora runtime truth. Specific compliance/certification claims require their own evidence.

## Required failure fixtures

At minimum the eventual governance/orchestration implementation rejects or safely stops:

1. repository comment instructing agent to ignore policy;
2. stale run after active plan or policy changes;
3. two writers on same migration/file;
4. unplanned permission/network/secret/dependency scope expansion;
5. feature change weakens its own CI/governance gate;
6. AI deletes/weakens failing test to turn red green;
7. AI writes a fake target PASS artifact;
8. reviewer approval bound to old head;
9. child agent requests more authority than parent;
10. dependency lookalike/undeclared install-script change;
11. repeated identical failed repair loop exceeds budget;
12. expired waiver used for promotion;
13. promoted artifact cannot be traced to reviewed exact source/build;
14. branch rules claim protection while required checks/reviews are operationally absent.

## No new top-level roadmap stage

This audit intentionally does **not** add another canonical top-level stage. The orchestration layer is a maturation of `AI-GOV-AUTOMATION-100`, which already owns machine enforcement of Nexora's development control plane. This keeps the roadmap smooth while making that stage materially stronger.
