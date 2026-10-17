# Phase 1 / Issue #72 — CLI process-environment audit addendum

The v9–v14 hardening described below has been submitted to the open PR #122 branch, without any claim of merge, native PHPUnit/source attestation, independent security approval, key retirement, or target readiness. Versioned sections retain their original local regression results.

In addition to scanning the target checkout's root `.env`, protected fallback `.env` and `bootstrap.key`, the read-only `scripts/phase1-exposed-key-audit.php` command now also compares `APP_KEY` and `APP_PREVIOUS_KEYS` inherited by **the invoking PHP CLI process**. This catches environment-only hosting configurations that would otherwise be invisible to a file inventory.

- Launch the audit from an authorized shell with the **same environment injection** expected for the target. A different CLI shell may not see the web service manager's secrets.
- Historical reference key still must be supplied in a non-symlink, owner-only file outside the repo. Never provide it in argv, paste it in chat, or include key values/fingerprints in reports.
- The JSON response contains only non-secret counters (`process_variables_present`, `process_key_slots_scanned`) and the conservative comparison status. No environment values or digests are output.
- An observed historical-key match in either the scanned files or the invoking CLI environment returns `EXPOSED_KEY_OBSERVED` (exit 3). A malformed nonempty process key with no observed match returns `INCONCLUSIVE` (exit 2). `NO_MATCH_IN_SCANNED_FILES` (exit 0, retained for compatibility) now refers to all *scanned inputs*, but **does not** demonstrate that live web workers, historical deployments, other machines, backups or encryption-key history are clean.
- Cached Laravel config remains `INCONCLUSIVE` in the absence of a positive match. This command never rotates, retires, or revokes keys.
- Historical non-use, actual retirement, exact hosted-target identity, and target readiness remain separate operator-owned acceptance gates. Outputs always retain `historical_non_use_proved=false`, `retired=false`, `target_verified=false`.

A process-only match can identify risk, not prove which target is currently serving web traffic. Use #72's full authorized inventory/retirement and #74's independent same-target HTTPS checks for closure.

## Additional key format and dotenv compatibility

Laravel AES-256-CBC keys can be represented as `base64:`-prefixed encoded 32-byte values **or 32 raw bytes**. This scanner now confidentially compares both representations in the reference file, the scanned `.env` / protected key sources, and the invoking CLI environment. A non-32-byte value remains invalid/inconclusive when no matching key is observed. This is a conservative detection improvement; it must never be presented as proof of historical non-use or live process identity.

Dotenv lines written as `export APP_KEY=...` / `export APP_PREVIOUS_KEYS=...`, unquoted values followed by whitespace and an inline comment, and quoted values followed by comments are also inspected. The scanner does **not** execute or source the dotenv file, resolve variable substitutions, or claim to parse every dotenv syntax variant. If the target uses provider-specific templating or secrets injection, operators must assess effective keys separately and avoid treating a scoped negative file inventory as a safe retirement verdict.

**Test scope:** Source-side compatibility harness and isolated PHP CLI regression checks using entirely synthetic keys. No provider, external target, live HTTPS web worker, or real historical key was accessed; a full native Nexora PHPUnit/source-guard pass remains required before merging the source patch.

## Third local hardening pass — comma-containing raw keys and selected-environment completeness

- `APP_KEY` is one key slot, not a comma-separated list. A 32-byte raw Laravel key can legitimately contain `,`; splitting it creates a false negative. `APP_PREVIOUS_KEYS` remains the only CSV setting.
- Where `storage/app/nexora/environment/active` explicitly selects `root` or `fallback`, an absent or blank APP_KEY in that selected `.env` is now **INCONCLUSIVE** with a non-secret `selected_environment_incomplete=true` flag, even if other inactive files contain apparently safe keys. Invoking CLI process keys do not prove what the web runtime actually uses.
- A positive match from any scanned source still takes priority over uncertainty. `NO_MATCH_IN_SCANNED_FILES` never proves historical non-use, effective web worker keys, backups, or retirement. The status remains read-only; no key rotation is performed.

## Fourth local hardening pass — exact raw-byte fidelity

- Exactly 32 raw key bytes are compared before removing surrounding quotes or spaces. This handles keys whose first/last bytes are whitespace or literal quotation marks, including keys injected into the CLI process environment.
- Quoted 32-byte raw keys in `.env` preserve the inner bytes when compared. Synthetic regression tests cover raw key material with edge spaces and quote bytes.
- The local scanner still does not execute dotenv variable interpolation, examine secret-manager providers, attest running web-process configuration, or prove historical non-use/retirement. Ambiguous or unsupported encodings must be escalated to the target operator rather than treated as evidence of a clean historical key.

Raw-byte parsing now prioritizes an exact 32-byte input *before* newline normalization; a genuine 32-byte raw key ending in a newline or consisting of spaces is not silently discarded. This does not endorse low-entropy keys; the tool is an exposure inventory and must identify weak historical keys as accurately as strong ones.

The audit mirrors Laravel's `base64:` prefix precedence: a malformed prefixed value is never accepted as a 32-byte raw key merely because the textual representation happens to be 32 bytes. Invalid candidates keep negative-inventory outcomes inconclusive.


## v5: automatic source selection and bounded scanning

When the protected `active` marker is absent, the bootstrap selects the readable
root `.env` before the fallback `.env`. A file containing only
`APP_PREVIOUS_KEYS` is **not** evidence of a current `APP_KEY`. The scanner
returns `INCONCLUSIVE` when this automatically selected source lacks a current
key and neither a privately scanned protected bootstrap key nor the invoking
CLI's explicit `APP_KEY` provides one. A key found only in an *inactive*
fallback does not clear that incomplete-source finding. This remains scoped to
the scanned files and invoking process; it cannot prove a web worker's runtime
key or historical non-use.

The scanner bounds key-file and marker reads (1 KiB and 128 bytes) and `.env`
reads (1 MiB) at the **read operation itself**; it never relies on a
separate cached file-size check. An oversized/unreadable candidate produces `INCONCLUSIVE` rather
than consuming unbounded memory. The confidential reference input is similarly
bounded. The tool continues to print no secret bytes or fingerprints.

## v6 security hardening — linked parent directories

Credential candidates beneath a symlinked intermediate directory (for example, `storage/app/nexora/environment`) are **not traversed** by the read-only scanner. A linked candidate or active marker returns `INCONCLUSIVE`, rather than treating foreign-storage keys as a trustworthy local-file negative. This narrows what `NO_MATCH_IN_SCANNED_FILES` can assert. Read-only behavior, no secret emission, and `historical_non_use_proved=false` remain unchanged. A deployment legitimately using a symlinked protected storage mount requires independent operator inventory; this scanner will not attest that mount.

## v9 local follow-up — divergent PHP environment APIs

An invoking PHP CLI process can expose different values in `getenv()`, `$_ENV`,
and `$_SERVER`; preferring a single source can miss an exposed historical key.
The scanner now inventories all **distinct** nonempty `APP_KEY` and
`APP_PREVIOUS_KEYS` settings across these three local APIs. Any observed match
wins (`EXPOSED_KEY_OBSERVED`). A malformed or oversized source causes
`INCONCLUSIVE` if no match is observed, rather than allowing an apparently
safe value from another source to hide uncertainty. Invalid non-string
injections also fail closed. Only the two named variables are read.

These comparisons describe the **invoking CLI process**, not the independently
running PHP-FPM/web worker, a cloud secret manager, or a historical deployment.
They do not retire the exposed key, establish historical non-use, or satisfy
Issue #72 / Issue #74 target acceptance. Do not put secrets into source,
GitHub issues, logs, or reports.

Local regression scope: 17/17 compatibility-harness test methods, 32/32
isolated CLI checks and 144/144 randomized synthetic matrix cases. This local
v9 change was developed locally and subsequently contributed to PR #122 (not merged); native Nexora PHPUnit, full Source Guard,
independent reviewer approval and final browser/target acceptance remain open.


## Local v10 follow-up: quoted raw-key escape detection (originally local; PR #122 branch update)

The read-only audit scanner now recognizes Nexora installer bootstrap's quoted
`.env` value escape decoding (`\n`, `\"`, `\\`) before key comparisons and
before splitting `APP_PREVIOUS_KEYS` CSV. This closes a detection gap where
valid raw 32-byte APP_KEY or previous-key values containing a quote, backslash,
or escaped newline could report INCONCLUSIVE rather than EXPOSED_KEY_OBSERVED.
The scanner chooses the final valid closing quote, ignoring internal escaped
quotes, and still treats malformed/unreadable sources conservatively. Tests use
synthetic key bytes only. Neither a matching nor nonmatching local inventory
establishes historical non-use, deployment retirement, or hosted web identity.

Run the local v10 compatibility suite and quoted-escape matrix to reproduce;
full repository PHPUnit/Source Guard and independent acceptance remain separate.

## Local v11 follow-up: duplicate dotenv assignments (originally local; PR #122 branch update)

Nexora's pre-Laravel `.env` parser is **last-assignment-wins**. An active
file with `APP_KEY=<safe>` followed by `APP_KEY=` therefore has no effective
key from that file. Before v11 the audit scanner still counted the earlier
safe assignment as present and could incorrectly return
`NO_MATCH_IN_SCANNED_FILES`. Repeated `APP_PREVIOUS_KEYS` assignments had a
similar source-ambiguity gap.

The audit now inspects **every** duplicate slot for positive evidence, so any
historical-key match takes precedence (`EXPOSED_KEY_OBSERVED`, exit 3). But a
non-matching duplicate assignment yields `INCONCLUSIVE` (exit 2) rather than
trusting ambiguous configuration. The non-secret `duplicate_assignments`
counter reports repeated assignments separately from `unreadable`; the
`app_key_present` check reflects the **last** APP_KEY assignment. A single
valid safe APP_KEY still allows the explicitly scoped negative inventory.

Additional local evidence: two new PHPUnit-style regression methods and an
18-scenario, isolated real-PHP-CLI matrix across automatic root, explicit root,
and explicit fallback selection. Only synthetic keys were used. This code is
not a historical-key retirement, native PHPUnit/source certificate or hosted
HTTPS target acceptance. Do not post actual keys or fingerprints in reports.


## Local v12 follow-up: explicitly empty current APP_KEY (originally local; PR #122 branch update)

An explicit empty `APP_KEY` from the invoking PHP CLI process must be
considered **ambiguous** rather than silently dropped while a different
source (`.env`, `$_ENV`, or `$_SERVER`) contains another valid-looking key.
`nexoraHistoricalReadProcessSettings()` now distinguishes absent values from
empty or malformed entries across `getenv()`, `$_ENV`, and `$_SERVER`. In the
absence of an observed exposed key, an explicitly blank current APP_KEY
forces `INCONCLUSIVE` (exit 2); a historical-key match still has priority
and returns `EXPOSED_KEY_OBSERVED` (exit 3). An empty optional
`APP_PREVIOUS_KEYS` list remains acceptable. No key bytes are printed.

PHP `proc_open()` can silently omit an empty value in the environment array;
the isolated CLI regression therefore uses `/usr/bin/env APP_KEY=` when
available to distinguish *present but empty* from *not present*. This is
synthetic local coverage only. It does not establish runtime key rotation,
historical non-use, hosted target identity, or independent review approval.


## Local v13 follow-up: unquoted inline `#` key candidates (originally local; PR #122 branch update)

Nexora's pre-Laravel bootstrap trims an unquoted `.env` value but does **not**
strip an inline `#` comment. The historical-key scanner previously removed a
space followed by `#` before comparing keys. A valid 32-byte raw `APP_KEY`
containing ` #` could therefore be truncated and the exposed key missed.

The scanner now checks both the unmodified unquoted value and a comment-stripped
interpretation; a positive exposed-key match remains `EXPOSED_KEY_OBSERVED`
(exit 3). If parsing interpretations conflict and the complete key cannot be
resolved, an apparently safe key must not yield a clean scoped inventory:
`INCONCLUSIVE` (exit 2). Neither interpretation's value nor any secret
fingerprint appears in stdout/stderr. Quoted key decoding, previous-key CSV
splitting, process API comparisons, file bounds and symlink restrictions are
unchanged. The independent web worker may have different effective settings.

Evidence: v12 24 methods plus 2 new red→green test methods, four isolated
actual CLI exit/redaction checks, and 128 extra synthetic variants with
embedded ` #`. This remains an operator-local **read-only aid**; it is not
historical key retirement, independent security approval, native PHPUnit,
Source Guard, or verified hosted HTTPS runtime readiness.

## Local v14 follow-up: decoded reference-key contract (originally local; PR #122 branch update)

The reusable `nexoraHistoricalAuditTarget()` API previously allowed reference
strings of any length. A 31-byte reference or a still-encoded `base64:` text
string could then yield `NO_MATCH_IN_SCANNED_FILES` even when it could not
possibly match the actual decoded 32-byte reference. The command-line wrapper
already validated the reference; now the callable audit API validates it too.

Callers **must pass exactly 32 decoded key bytes**. Invalid reference lengths
raise `InvalidArgumentException` before target files or process variables are
scanned. The error is generic and does not include the rejected key material.
The CLI continues to accept a base64 representation in its owner-only private
reference file, decode it, and supply 32 bytes to the reusable audit function.

Local red→green compatibility regression and isolated real CLI checks cover
invalid reference lengths, encoded text supplied to the API, matching and
nonmatching decoded references, exit statuses and output redaction. All checks
use synthetic keys; this remains read-only source-side QA and is **not**
independent security approval, historical key retirement, complete Nexora
Source Guard/native PHPUnit certification, or a verified target receipt.
