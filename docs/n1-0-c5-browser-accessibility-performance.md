# N1.0-C5 — Browser, Accessibility, RTL and Performance

C5 certifies the presentation/runtime boundary after exact-source C2 has passed. It does not install dependencies, run the five-database matrix, rehearse upgrades/restores, or certify multi-node HA.

## Required browser matrix

Chrome, Edge and Firefox each run at 360 px, 768 px and 1440 px in both LTR/RTL and light/dark modes: 36 required PASS rows. Evidence also records real browser versions, OS values and a real assistive-technology observation.

## Accessibility checks

Keyboard navigation, focus visibility, skip link, modal focus trap, command-palette keyboard behavior, screen-reader labels, reduced motion, 200% zoom, forced-colors and no page-level horizontal overflow are mandatory.

## Performance evidence

C5 requires a real target URL. HTTP smoke verifies status, request IDs, cache/security headers and route latency ceilings. The production Vite build must pass asset budgets with no source maps or local-path leakage. Web-vitals evidence requires repeated observed runs for `/` and `/login` within the project LCP/INP/CLS/TTFB ceilings in `config/nexora-browser-certification.php`.

## Operator flow

1. `php scripts/n1-c5-evidence-prepare.php --operator="NAME"`
2. Run the 36-row browser matrix and accessibility checks; replace FAIL only after observation.
3. Record repeated Web Vitals runs.
4. `scripts\\n1-c5-browser-performance-certify.bat --base-url=https://TARGET --evidence=<kit-dir>`

C5 PASS is sealed only when source SHA, C2 evidence, reviewed dependency locks, browser evidence, Web Vitals, HTTP evidence and build-assets evidence all match.
