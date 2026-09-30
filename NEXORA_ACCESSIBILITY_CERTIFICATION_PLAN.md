# Nexora Accessibility Certification Plan

> AI/agent execution contract for W3C HTML/CSS, WAVE, WCAG and real browser accessibility closure.
>
> Read this file together with `AGENTS.md`, `NEXORA_AI_PROJECT_STATE.md` and `NEXORA_PROGRESS.md`. This plan does not replace SOURCE DONE vs TARGET VERIFIED semantics.

## 1. Objective

Nexora accessibility closure must combine source regressions, standards validation, automated accessibility analysis and direct human/assistive-technology observation. No single automated tool is allowed to certify the product as accessible.

Final C5 accessibility evidence requires all of the following:

1. Existing executable source a11y regressions remain green.
2. W3C Nu HTML conformance validation reports **zero errors** on every required target route.
3. W3C CSS Validation Service reports **zero CSS errors** on every required target route; warnings are recorded for review.
4. WAVE reports **zero Errors** and **zero Contrast Errors** on every required target route.
5. Every WAVE Alert is reviewed by a human auditor and either confirmed harmless/informational or fixed before certification.
6. Chrome, Edge and Firefox browser matrix is directly observed at mobile/tablet/desktop widths, LTR/RTL and light/dark.
7. Keyboard-only, visible focus, skip link, dialogs/focus containment, reduced motion, 200% zoom, forced-colors and screen-reader labels are directly observed.
8. A real assistive-technology/browser/OS combination is recorded.
9. Required Web Vitals and HTTP/security evidence remain within C5 ceilings.
10. Evidence is current, exact-source bound and certification-session bound.

## 2. Required routes

The initial required standards matrix is configured in `config/nexora-browser-certification.php` and must include at least:

- `/`
- `/login`

Add high-value public/admin routes as authenticated target tooling becomes available. Do not silently remove a failing route to make certification green.

## 3. W3C HTML + CSS validation

Canonical runner:

```bash
php scripts/n1-c5-web-standards-certify.php \
  --base-url=https://TARGET \
  --auditor=NAME \
  --wave-alerts-reviewed
```

The runner uses W3C Nu Html Checker and W3C CSS Validation Service by default. Custom/local validators may be supplied with:

```text
--w3c-validator-url=https://YOUR-VALIDATOR/nu/
--w3c-css-validator-url=https://YOUR-CSS-VALIDATOR/validator
```

HTML project gate:

- HTTP request succeeds.
- Required route is reachable by the checker.
- Nu Html Checker `error` count is exactly `0`.
- HTML warnings are recorded for review.

CSS project gate:

- CSS validator request succeeds.
- W3C CSS validity is `true`.
- CSS error count is exactly `0`.
- CSS warning count is recorded for review.
- The current configured validator profile is `css3`.

Warnings are evidence for review; they must never be silently discarded or misrepresented as accessibility certification.

## 4. WAVE evaluation

Default shared WAVE API requires the environment variable:

```text
WAVE_API_KEY
```

The secret must never be committed, printed into logs or persisted in evidence. A licensed stand-alone/private WAVE API that uses a request key may be selected with:

```text
--wave-api-url=https://YOUR-WAVE-ENDPOINT
--wave-key-env=YOUR_SECRET_ENV_NAME
```

If an explicitly configured licensed stand-alone endpoint does not require a per-request API key, use:

```text
--wave-api-url=https://YOUR-WAVE-ENDPOINT
--wave-no-key
```

`--wave-no-key` is **never** valid for the shared `wave.webaim.org/api/request` service; the runner fails closed if that combination is attempted. Evidence records only the authentication mode and, when applicable, the environment-variable name—never the secret value.

Project gate per required route:

- WAVE API evaluation succeeds.
- Target response is valid.
- WAVE `Errors` count is exactly `0`.
- WAVE `Contrast Errors` count is exactly `0`.
- WAVE Alert count is recorded.
- A human auditor reviews all Alerts before `--wave-alerts-reviewed` is asserted.

Important: project gate PASS is **not** a WAVE approval/certification and must never be described as one. WAVE automated output cannot prove full WCAG conformance.

## 5. Real browser / WCAG review

W3C HTML/CSS and WAVE supplement but do not replace the existing C5 manual matrix. The operator kit remains fail-closed until all browser/accessibility checks are directly observed. At minimum review against WCAG 2.2-relevant behavior for:

- semantic structure and headings;
- text alternatives;
- form labels, descriptions and errors;
- keyboard access and logical focus order;
- visible focus and focus restoration;
- dialogs, overlays and focus traps;
- color contrast and non-color cues;
- zoom/reflow at 200%;
- reduced motion;
- forced-colors/high-contrast behavior;
- screen-reader names, roles, states and relationships;
- responsive navigation and horizontal overflow;
- RTL behavior without semantic/order breakage.

## 6. Evidence files

Generated target evidence:

```text
storage/app/nexora/certification/web-standards-evidence.json
```

Verifier:

```bash
php scripts/n1-c5-web-standards-evidence-verify.php
```

The final C5 evidence manifest must hash-bind the web-standards evidence alongside browser, Web Vitals, HTTP/security and build evidence.

## 7. CI / target boundary

GitHub-hosted PR QA is development-checkout evidence only. It must source-guard that W3C HTML/CSS and WAVE runners/evidence bindings exist, but it must not claim live W3C/WAVE target success because those checks require a reachable exact-source target and WAVE additionally requires either the shared API credential or an explicitly configured licensed stand-alone endpoint.

Real W3C/WAVE execution belongs to C5 target certification on a reachable target URL or approved private/stand-alone evaluators. If the required target, required authentication, browser observation or assistive-technology evidence is unavailable, C5 remains **BLOCKED**, not skipped and not assumed PASS.

## 8. AI rules

Any AI/agent working on accessibility must:

1. Never weaken a W3C/WAVE/browser gate to hide a real defect.
2. Never convert source/unit/jsdom evidence into target/browser evidence.
3. Never call WAVE output an accessibility approval.
4. Preserve API keys/secrets outside source and evidence.
5. Fix root causes, then rerun the exact failing route/tool.
6. Keep PRs draft while required C5 target evidence is missing.
7. Update `NEXORA_PROGRESS.md` and `NEXORA_AI_PROJECT_STATE.md` after meaningful accessibility applies/evidence changes.
8. Keep Issue #2/runtime recovery evidence separate from C5 accessibility evidence.
9. Preserve both W3C HTML and W3C CSS zero-error gates; never drop one to make C5 green.
10. Never use `--wave-no-key` with the shared WAVE API; it is only for an explicitly configured stand-alone endpoint that genuinely does not require a request key.
