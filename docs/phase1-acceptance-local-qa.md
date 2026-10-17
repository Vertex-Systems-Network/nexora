# Phase 1 local acceptance evidence aid

`tools/phase1_acceptance_probe.py` records only checks that actually ran. It does **not** certify `TARGET_VERIFIED`, historical-key retirement, owner/browser acceptance, an independent security review, or release approval.

## Offline regression suite

Run from the Nexora checkout with Python 3 and, for browser tests, Playwright and Chromium installed:

```sh
python -m unittest discover -s tests -p 'test_*.py' -q
```

The browser tests may require a browser executable. Local mock/fixture PASS results must not be treated as evidence that the real Nexora deployment was checked.

## Native preflight (read-only)

```sh
python tools/phase1_acceptance_probe.py --repo /path/to/disposable/nexora \
  --out /private/evidence/phase1-preflight.json --strict-requested-checks
```

This records checkout/source-readiness and fails closed when native prerequisites are absent. `--run-native-unit` is opt-in and must only be used against a **disposable**, uninstalled checkout after ensuring the repository, PHP extensions and Composer/vendor dependencies are present. Native tests use an isolated process environment; that isolation is **not** a network sandbox.

## Approved live HTTPS verification

```sh
python tools/phase1_acceptance_probe.py --origin https://approved-host.example \
  --out /private/evidence/phase1-target.json --strict-requested-checks
```

For a non-authenticated login UI smoke check, add `--browser`. The browser test enforces strict TLS, refuses redirects, forbids outbound non-GET requests and unknown origins, and masks login inputs in private screenshots. Do not run it against production without operator approval. Real owner acceptance is still separate.

**Safety:** Keep receipts in private directories outside a source checkout. Never supply actual APP_KEY values in command arguments, attach secret files to issues, or publish the evidence folder. Non-zero strict exit means the requested checks were not all verified. No scanner or test can prove historical key non-use on installations it did not inspect; #72 requires owner-supplied scope/evidence, and #74 requires an independently accepted HTTPS target.
