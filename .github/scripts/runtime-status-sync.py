import json
import re
from pathlib import Path

HEAD = '68d6aa5d8610e05acf7c51bed503ae09bd4311cd'
MAIN = 'e19d6fa818a7eebaf293d34bd87cff79ebc90ade'
LOCK_COMPOSER = '1e00ab9e4b63991260e20ae28f7c2f3e092da75425e27a474731c3ad8b86a198'
LOCK_NPM = '09c913a87f16b13c47020b2bf36aaf9068dbe50948402c9fdcd1bfd644090c75'


def replace_section(text: str, start: str, end: str, body: str) -> str:
    s = text.index(start)
    e = text.index(end, s)
    return text[:s] + body.rstrip() + '\n\n' + text[e:]


# README: human-readable live branch mirror only.
path = Path('README.md')
text = path.read_text(encoding='utf-8')
text, n = re.subn(
    r'> \*\*Canonical current status \(2026-09-10\):\*\*[^\n]*',
    '> **Canonical current status (2026-09-10):** protected `main@'+MAIN+'` remains the accepted baseline. Active Draft PR #30 now contains the governed Issue #52 dependency closure and the integrated Issue #46 bounded-child runtime fix, but those changes are not protected-main acceptance. `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` remains **BLOCKED** until fresh real-target readiness/current-receipt, exact target↔web identity, and authoritative `/login` evidence pass on the same target.',
    text,
    count=1,
)
assert n == 1
text = replace_section(text, '## Current runtime closure', '## Current dependency closure', '''## Current runtime closure

- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY`; target status remains **BLOCKED**.
- **PR #30:** Draft/unmerged, current integrated source head before this status sync `68d6aa5d8610e05acf7c51bed503ae09bd4311cd`.
- **Issue #46 / PR #51:** bounded child-execution source fix is integrated into #30. Exact #51 head `2e0736e4da1d5a89b6979e9171c672a1c0c32745` passed release certification #885 / `34424303763`; #51 changed exactly the three bounded runtime/workflow paths and merged as `68d6aa5d8610e05acf7c51bed503ae09bd4311cd`.
- **Child execution boundary:** finite 120-second default deadline, 256 KiB per-stream output cap, lower-only certification overrides, deterministic soft→hard termination, bounded/redacted failure evidence, and behavioral cleanup/lock-reuse verification are source-integrated.
- **Review boundary:** exact-head AI reviews are recorded with honest provenance; independent-human approval is not claimed. Any final #30 review must bind the final post-sync head.
- **Real-target evidence:** fresh final readiness/current receipt, exact target↔web identity, and authoritative `/login` evidence remain required.
- **CORE-QA-001:** MUST NOT start until runtime closure is target-verified.
- **PR #1:** remains a separate Draft final carrier and is not release-complete.''')
text = replace_section(text, '## Current dependency closure', '## Status boundary', f'''## Current dependency closure

- **Issue #52:** CLOSED / completed after the original frontend dependency failure was eliminated on a fresh #51 exact-head rerun.
- **Governed candidate v5:** run `34420984941` SUCCESS, artifact `10130890580`.
- **AI lock review:** reviewer `AI:GPT-5.6-Sol@ChatGPT`; no human review or independent approval is claimed. The one-time repository-admin authorization is retained in `.ai/plans/issue-52-ai-lock-review-exception.md`.
- **Exact reviewed locks:** `composer.lock` SHA-256 `{LOCK_COMPOSER}`; `package-lock.json` SHA-256 `{LOCK_NPM}`.
- **Promotion:** corrected fail-closed promotion run `34421381192` SUCCESS, dossier artifact `10131020021`; strict locks, provenance, supply-chain, reviewed-attestation verification and frontend replay passed.
- **Integration:** PR #55 exact-head certification #883 / `34423528195` SUCCESS, zero unresolved threads, then guarded integration into #30. Reviewed lock bytes remain unchanged after protected-main reconciliation.
- **Runtime rerun:** PR #51 certification #885 / `34424303763` SUCCESS using the committed reviewed dependency closure.''')
text = replace_section(text, '## Current next sequence', '## Core stack', '''## Current next sequence

1. Finish this status-only reconciliation and require fresh exact-head #30 release certification plus a fresh exact-head review on the resulting head.
2. Keep PR #30 Draft/unmerged while target evidence is missing.
3. On the exact Windows/Laragon rc.93 target, obtain fresh `post-install-status --assert-ready` evidence requiring `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, `errors=[]`.
4. Prove the configured `app.url` web process belongs to that exact target through the fresh one-time CLI↔web acknowledgement and local `--require-web-ack` verification.
5. Obtain authoritative `/login` evidence on that same proven origin with TLS verification enabled and redirects disabled.
6. Only after the source/review/target gates all pass may PR #30 merge and `RUNTIME-CLOSURE-001` become `TARGET_VERIFIED`.
7. Start `CORE-QA-001` only after genuine target verification; continue broader release work through PR #1 without collapsing Source, Target and Release states.''')
path.write_text(text, encoding='utf-8')

# Canonical branch state: update factual source-work evidence but keep target BLOCKED.
path = Path('.ai/state.json')
state = json.loads(path.read_text(encoding='utf-8'))
state['base_sha'] = MAIN
ev = state['runtime_closure_evidence']
ev['reviewed_dependency_attestation'] = (
    'present on active PR #30 source branch via governed AI review: '
    f'composer.lock={LOCK_COMPOSER}; package-lock.json={LOCK_NPM}; '
    'Issue #52 closed after PR #51 exact-head certification #885 passed. '
    'This does not assert the installed rc.93 target has consumed/resealed the new source dependency evidence.'
)
ev['orchestrator_source_work'] = (
    'PR #30 active Draft source carrier now integrates governed dependency closure (#55) and bounded child execution (#51). '
    f'Integrated source head before this status sync: {HEAD}. '
    'Dependency candidate/promotion and #51 exact-head CI are green; final post-sync #30 exact-head CI/review remains required. '
    'Real-target final readiness, exact target-to-web identity and /login evidence remain pending.'
)
state['blocker']['category'] = 'runtime-target'
state['blocker']['summary'] = (
    'The source-side dependency determinism blocker and bounded child-execution blocker are integrated on Draft PR #30. '
    'RUNTIME-CLOSURE-001 remains BLOCKED until fresh final rc.93 target readiness/current receipt, exact CLI-to-web identity, '
    'and authoritative /login evidence pass on the same proven origin; final #30 exact-head CI/review must also bind the post-sync head.'
)
state['blocker']['guardrail'] = (
    'Do not advance from source/CI, AI prose, app.url ownership, a bare /login 200, or historical repair evidence alone. '
    'Do not weaken TLS, redirects, child bounds, lock review, or target identity controls. Keep Source, Target and Release evidence separate.'
)
state['next_actions'] = [
    'Require fresh exact-head release certification and review on PR #30 after this status-only synchronization.',
    'On the real Windows/Laragon rc.93 target, obtain fresh post-install-status --assert-ready with status=pass, ready=true, runtime_ready=true, receipt_current=true, errors=[].',
    'Require the configured app.url web process to consume a fresh target-local one-time source/runtime challenge and verify the same acknowledgement through the exact target CLI.',
    'Obtain authoritative /login HTTP/browser evidence on that same exact proven origin with TLS verification enabled and redirects disabled.',
    'Only then merge PR #30, update protected-main canonical state, and advance to CORE-QA-001.',
]
state['updated_at'] = '2026-09-10T01:16:00Z'
path.write_text(json.dumps(state, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')

# Handoff: preserve historical target evidence; update current source integration facts.
path = Path('.ai/handoff/current.md')
text = path.read_text(encoding='utf-8')
needle = '- Current source-work carrier: PR #30, `feat/runtime-recovery-orchestrator`'
assert needle in text
text = text.replace(needle, needle + f'\n- Fresh protected-main baseline reconciled into carrier: `{MAIN}`\n- Integrated #30 source head before this status sync: `{HEAD}`\n- Issue #52 dependency closure: integrated; Issue #52 closed after #51 exact-head rerun passed\n- Issue #46 bounded-child source fix: integrated into #30 via PR #51', 1)
old = 'The dependency runtime fingerprint is compatible. `reviewed dependency-lock attestation = missing` remains a separate release/dependency-governance item and must not be confused with the closed four-plane identity mismatch.'
new = f'The historical rc.93 dependency runtime fingerprint was compatible. The active #30 source branch now carries the governed reviewed lock pair (`composer.lock={LOCK_COMPOSER}`, `package-lock.json={LOCK_NPM}`) and Issue #52 is closed. That source-branch review evidence does not by itself prove the installed rc.93 target has consumed or resealed the new source dependency state, so target evidence remains separate.'
assert old in text
text = text.replace(old, new, 1)
old = '→ apply-mode single-writer target lock\n→ deep compatibility'
new = '→ apply-mode single-writer target lock\n→ every child bounded by finite deadline + per-stream output cap with deterministic termination/cleanup\n→ deep compatibility'
assert old in text
text = text.replace(old, new, 1)
old = 'The ninth hardening reuses existing Nexora trust primitives (`SourceActivationHandshake`, `/install/source-status`, `nexora:source:status --require-web-ack`). It adds no public endpoint, dependency, external destination, permission, migration, or product capability.'
new = old + '\n\n10. **Unbounded child execution could hold the target lock indefinitely or exhaust capture resources.** Issue #46 / PR #51 adds a finite 120-second default deadline, 256 KiB per-stream caps, lower-only test overrides, file-backed capture, deterministic termination/cleanup, bounded redaction, and behavioral timeout/output/lock-reuse verification. Exact #51 head `2e0736e4da1d5a89b6979e9171c672a1c0c32745` passed release certification #885 / `34424303763` and is integrated into #30.'
assert old in text
text = text.replace(old, new, 1)
old = 'No migrations, dependency versions, product modules, business features or roadmap stages are added.'
new = 'No migrations, product modules, business features or roadmap stages are added. The separately governed Issue #52 dependency closure is now intentionally integrated into this source carrier with exact reviewed lock bytes; it does not widen runtime feature scope.'
assert old in text
text = text.replace(old, new, 1)
replacement = '''## Independent review status

- Exact-head AI review exists for the accepted Issue #52 dependency closure and for PR #51 bounded-child source fix, with reviewer provenance recorded honestly.
- No human review or independent-human approval is claimed for those AI reviews.
- Because #51 integration and this status synchronization move PR #30's head, every older #30 exact-head review is stale.
- Before any PR #30 merge, perform a fresh review bound to the final exact head. If repository policy requires an independent actor/runtime beyond this AI context, that requirement remains a merge gate; absence of findings is not implicit approval.
- Source review never substitutes for fresh real-target readiness, exact CLI↔web identity, or `/login` evidence.
'''
text = replace_section(text, '## Independent review status', '## Current source-work acceptance requirements', replacement)
path.write_text(text, encoding='utf-8')

# Active plan: precise source-integration checkpoint; historical target evidence stays intact.
path = Path('.ai/plans/active.md')
text = path.read_text(encoding='utf-8')
old = '- Current canonical source baseline for this pass: `main@dffb238e655a1c474f4f7ce7e75c6eda004c0c32`'
new = f'- Fresh protected-main baseline reconciled for this pass: `main@{MAIN}`\n- Active Draft source carrier before this status sync: `PR #30 / feat/runtime-recovery-orchestrator@{HEAD}`'
assert old in text
text = text.replace(old, new, 1)
old = '- dependency runtime status PASS; reviewed dependency-lock attestation remains `missing` as a separate release/dependency-governance concern, not a runtime identity mismatch'
new = f'- historical target dependency runtime status PASS; active #30 source now carries governed reviewed locks (`composer.lock={LOCK_COMPOSER}`, `package-lock.json={LOCK_NPM}`), while installed-target dependency sealing remains separate target evidence'
assert old in text
text = text.replace(old, new, 1)
old = 'The durable control improvement is a single Runtime Recovery / Closure Orchestrator that preserves human approval for mutation while automating deterministic verification/reconciliation, serializing apply-mode writers, preserving unique evidence, and proving a fresh target-local CLI→web nonce/source/runtime handshake before `/login` can become authoritative.'
new = 'The durable control improvement is a single Runtime Recovery / Closure Orchestrator that preserves explicit mutation authorization while automating deterministic verification/reconciliation, serializing apply-mode writers, bounding every child deadline/output capture, preserving unique evidence, and proving a fresh target-local CLI→web nonce/source/runtime handshake before `/login` can become authoritative.'
assert old in text
text = text.replace(old, new, 1)
checkpoint = f'''## Source integration checkpoint — 2026-09-10

- Issue #52 dependency determinism closure is integrated into PR #30; Issue #52 is CLOSED after fresh PR #51 rerun.
- Governed candidate v5 run `34420984941` and corrected promotion run `34421381192` are SUCCESS.
- Exact reviewed source locks are `composer.lock={LOCK_COMPOSER}` and `package-lock.json={LOCK_NPM}`.
- PR #55 final certification #883 / `34423528195` passed before guarded integration.
- Protected main `{MAIN}` was reconciled into #30 while preserving its governance/README history and retaining the exact reviewed Vitest/lock intent.
- Issue #46 / PR #51 was synchronized to that current base with exactly three intended changed paths; exact-head release certification #885 / `34424303763` passed.
- PR #51 is integrated into #30 as `{HEAD}`.
- This checkpoint is source-work evidence only. Final post-sync #30 exact-head CI/review plus fresh real-target readiness, target↔web identity and `/login` evidence remain mandatory.
'''
marker = '## Real-target evidence received — 2026-08-25'
assert marker in text
text = text.replace(marker, checkpoint + '\n' + marker, 1)
path.write_text(text, encoding='utf-8')
