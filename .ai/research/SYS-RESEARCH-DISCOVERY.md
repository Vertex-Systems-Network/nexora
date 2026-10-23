# ResearchBrief — SYS-RESEARCH-DISCOVERY: machine-readable ResearchBrief

## Problem

The repository requires a ResearchBrief (problem, VOC, baseline, CTQs) for substantial work, but only as prose. No check verifies that a brief exists, has the required content, or that stated confidence is backed by evidence, so an AI author can skip research or assert unsupported confidence without detection.

## Users & VOC evidence

- Users: repository owner and AI/human contributors planning new units.
- VOC: owner-authored requirements only — `.ai/quality/engineering-lifecycle.md` ("ResearchBrief/VOC/CTQs" for high/critical change) and `.ai/governance/development-intake.md` (intake item 2). No external user research exists.

## Baseline

UNKNOWN. No registered unit carried a `research` block before this change (0 of 116 units, counted by script at base `23359fd`).

## Alternatives

- Do nothing: prose rules stay unenforced. Rejected.
- New separate CI workflow: more surface to maintain. Rejected in favour of extending `scripts/ai-control-plane-verify.php`, which CI already runs through `tests/Architecture/AiControlPlaneConsistencyTest.php`.
- Require briefs for every unit now: would fail 90+ PLANNED units at once. Rejected; enforce at the ACTIVE transition for high/critical units.

## CTQs

1. Any `research.brief` path exists under `.ai/research/` and contains every required section.
2. `research.confidence` other than `unknown` requires non-empty `research.voc_evidence`.
3. `research.baseline`, when present, is non-empty (`UNKNOWN` allowed).
4. No high/critical unit is `ACTIVE` without `research.brief` or a non-empty `research.exception`.

## Confidence

`low` — the need is stated by the owner in repository policy, but there is no outcome measurement yet.

## Sources

- `.ai/quality/engineering-lifecycle.md`, `.ai/governance/development-intake.md`, `.ai/governance/automation-plan.md` (line on DMADV/ResearchBrief verification).
- `.ai/schemas/development-unit.schema.json` (`research` object).
