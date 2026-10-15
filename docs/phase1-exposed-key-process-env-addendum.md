# Phase 1 / Issue #72 — CLI process-environment audit addendum

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
