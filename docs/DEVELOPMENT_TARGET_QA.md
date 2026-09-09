# Nexora Development Target QA

This workflow is for **development usability verification**, not final C1-C6 release certification.

It is intentionally separate from reviewed-lock promotion, release signing and final certification so product/runtime QA can continue before DEV-6.

## 0. Server portability is a core boundary

Nexora is not tied to Laragon or any other local-server vendor. The application/runtime is intended to run from a compatible project directory on Windows, Linux or macOS during development and on compatible production/live-server environments.

Required platform capabilities come from the certified PHP/Composer/Node/npm/database/runtime contracts, not from a specific local-server product or filesystem path. Tool discovery is host/PATH-first. Vendor-specific helpers may exist as **optional adapters** (for example, safe local PHP-extension remediation when a supported local-server layout is detected), but those adapters must never become a prerequisite for Nexora itself.

A concrete Laragon path mentioned in historical rc.93 evidence describes that one preserved installation only; it is not a Nexora deployment requirement.

## 1. Keep historical recovery targets separate

An existing recovery installation may be an older release such as rc.93. Do not overwrite a preserved recovery target with the current development branch merely to clear runtime fingerprints.

For a preserved target whose installed source supports the required recovery capability, verify it with its own version-compatible commands. Historical targets that provably lack a later recovery implementation remain preserved as failure evidence rather than being modified to manufacture a PASS.

Current-source target QA must therefore use a **separate checkout/target**.

## 2. Use a separate development checkout

In a separate checkout of the active development branch, install the branch dependencies normally, then run:

```bat
npm run dev:target-qa
```

Equivalent command:

```bat
php scripts\development-readiness.php --full --tests --evidence
```

This executes:

- all source/product contracts included by Development Readiness;
- Laravel application bootstrap + route registration;
- the full Laravel/PHPUnit suite;
- TypeScript `--noEmit`;
- the raw production Vite build.

It writes a source-bound, detail-minimal evidence file to:

```text
storage/app/nexora/qa/development-readiness.json
```

The evidence records status/exit codes, platform/source identity, PHP/OS identity and whether full/tests mode was requested. Raw command output is deliberately not copied into the durable evidence artifact.

The checkout path itself is not part of the product contract. A runner workspace living under Laragon, XAMPP, Herd, a plain Git workspace, a container mount, a hosting home directory or another compatible location does not make that environment a Nexora dependency.

## 3. Run the disposable database matrix separately

Database portability is verified against disposable databases/files only:

```bat
php scripts\database-target-matrix.php --list
```

Then configure the required `NEXORA_MATRIX_<DRIVER>_*` environment variables and run the engines that actually exist in the target environment, for example:

```bat
php scripts\database-target-matrix.php --drivers=sqlite,mysql,mariadb,pgsql,sqlsrv --evidence
```

Safety boundary:

- network database names must begin with `nexora_matrix_`;
- SQLite filenames must match `nexora_matrix_*.sqlite`;
- selected databases must be empty;
- the matrix does not rewrite the project `.env`;
- the matrix never drops database containers;
- cleanup removes only matrix objects/files.

Matrix evidence is written to:

```text
storage/app/nexora/qa/database-target-matrix.json
```

Only engines with a real PASS result may be described as **TARGET VERIFIED**.

## 4. Browser/product workflow evidence remains real-target work

Static/source contracts do not replace browser/runtime exercise. At minimum verify the current product workflows on the development target:

- Settings and identity/regional settings;
- Media Library reuse;
- Theme scan/install/preview/activate/rollback;
- Extension Sentinel/install/enable/disable/uninstall;
- Studio edit/publish/public render/fallback;
- Documents + Collections;
- Publishing + SEO;
- Forms + Automation event bridge;
- Data Connections;
- responsive/mobile Admin navigation.

Repeat material runtime/browser checks on representative deployment families when release scope requires them. A Windows local-server pass proves that target only; it does not replace Linux/live-server, browser, assistive-technology or final C1-C6 evidence.

## 5. PR finalization rule

A PR remains draft while required source CI or real-target evidence is missing.

Once the PR's required gates are genuinely complete:

1. source CI must be green on the exact head;
2. required real-target runtime/product/database evidence must be PASS;
3. no applicable open blocker issue may remain unresolved for that PR scope;
4. mark the PR **Ready for review**;
5. merge it without waiting for a separate merge confirmation.

Never merge a failing or target-unverified PR.
