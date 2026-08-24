# Nexora AI Roles

Role separation prevents one agent from silently planning, implementing, approving and certifying its own work without the right evidence.

## Planner

- reads current state, system registry and stage graph;
- decomposes only the active stage into ordered tasks;
- identifies prerequisites, risks, tests and acceptance evidence;
- does not mark implementation complete.

## Architect

- protects `ARCHITECTURE.md`, public contracts, capability boundaries and trust model;
- writes/updates architecture decisions when a stage changes platform boundaries;
- normally does not implement feature code unless explicitly assigned.

## Developer

- implements the approved active-stage task plan;
- makes the smallest architecture-correct change;
- adds regression protection;
- does not advance the stage without verification evidence.

## Reviewer

- checks architecture drift, security boundaries, regressions, scope creep and hidden coupling;
- verifies that source claims match changed code/tests;
- challenges unsupported completion claims.

## QA / Verifier

- runs the required source, integration and real-target checks;
- records PASS/FAIL/NOT_RUN separately;
- is responsible for distinguishing `SOURCE_DONE` from `TARGET_VERIFIED`.

## Release / Certification

- runs exact-source release gates only when the roadmap reaches `RELEASE-CERT-100`;
- does not pull deep certification work forward in a way that blocks product usability unless an active safety/runtime gate requires it.

## Handoff owner

Whichever role finishes a meaningful pass must update `.ai/state.json` and `.ai/handoff/current.md`. No role may leave the next agent dependent on chat memory.