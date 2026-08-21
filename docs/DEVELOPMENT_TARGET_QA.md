# Nexora Development Target QA

This workflow is for **development usability verification**, not final C1-C6 release certification.

It is intentionally separate from reviewed-lock promotion, release signing and final certification so product/runtime QA can continue before DEV-6.

## 1. Keep the recovery target separate

The existing Laragon recovery installation may still be an older release such as rc.93. Do not overwrite it with the current development branch merely to clear runtime fingerprints.

Verify the installed recovery target first:

```bat
php artisan nexora:runtime:compatibility-status --deep
php artisan nexora:runtime:post-install-status --assert-ready
```

Then exercise `/login` and `/admin` on that installed target.

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

## 5. PR finalization rule

A PR remains draft while required source CI or real-target evidence is missing.

Once the PR's required gates are genuinely complete:

1. source CI must be green on the exact head;
2. required real-target runtime/product/database evidence must be PASS;
3. no applicable open blocker issue may remain unresolved for that PR scope;
4. mark the PR **Ready for review**;
5. merge it without waiting for a separate merge confirmation.

Never merge a failing or target-unverified PR.
