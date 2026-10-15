#!/usr/bin/env python3
"""Read-only, fail-closed Nexora Phase-1 acceptance *evidence aid*.

This script NEVER declares TARGET_VERIFIED, key retirement, independent review,
or final release acceptance. It only records the checks actually executed.
No secret env values, cookies, HTML bodies, or activation tokens are logged.
"""
from __future__ import annotations

import argparse
import base64
import datetime
import json
import os
import pathlib
import secrets
import shutil
import ssl
import subprocess
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request


def safe_origin(url: str) -> str:
    if url != url.strip() or any(ord(char) < 32 for char in url) or "\\" in url:
        raise ValueError("Origin contains unsafe whitespace or control characters")
    value = urllib.parse.urlsplit(url)
    if (value.scheme != "https" or not value.hostname or value.username is not None
            or value.password is not None or value.path not in ("", "/")
            or value.query or value.fragment or value.netloc.endswith("@")
            or len(url) > 2048):
        raise ValueError("Origin must be a credentials-free HTTPS scheme://host[:port] with no path/query/fragment")
    try:
        port = value.port
    except ValueError as err:
        raise ValueError("Invalid origin TCP port") from err
    if port is not None and not 1 <= port <= 65535:
        raise ValueError("Invalid origin TCP port")
    return f"https://{value.netloc}"


class NeverRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def tls_login_check(origin: str, ca_file: str | None = None, timeout: float = 10.0):
    # Enforce HTTPS even for direct helper calls; never label plain HTTP as strict TLS.
    try:
        origin = safe_origin(origin)
    except (TypeError, ValueError):
        return {"state": "FAIL", "http_status": None,
                "tls_verification": "NOT_PERFORMED", "redirects_followed": False,
                "reason": "INVALID_ORIGIN"}
    # Direct connection to the approved origin: never silently inherit HTTPS_PROXY.
    # The verified hostname and CA chain remain mandatory.
    # Invalid operator CA roots are a recorded FAIL, not an uncaught exception:
    # the strict evidence gate must still write a private failure receipt.
    try:
        ctx = ssl.create_default_context(cafile=ca_file)
    except (OSError, ssl.SSLError, ValueError):
        return {"state": "FAIL", "http_status": None,
                "tls_verification": "NOT_PERFORMED", "redirects_followed": False,
                "reason": "CA_CONFIGURATION_FAILURE"}
    opener = urllib.request.build_opener(
        urllib.request.ProxyHandler({}),
        urllib.request.HTTPSHandler(context=ctx), NeverRedirect()
    )
    req = urllib.request.Request(
        origin + "/login",
        headers={"Accept": "text/html", "Cache-Control": "no-cache", "Pragma": "no-cache"},
        method="GET",
    )
    try:
        with opener.open(req, timeout=timeout) as response:
            status = response.status
            # Intentionally do not store/read any response body or cookie.
            return {"state": "PASS" if status == 200 else "FAIL", "http_status": status,
                    "tls_verification": "strict", "redirects_followed": False}
    except urllib.error.HTTPError as err:
        return {"state": "FAIL", "http_status": err.code,
                "tls_verification": "strict", "redirects_followed": False,
                "reason": "REDIRECT_REFUSED" if 300 <= err.code <= 399 else "NON_200"}
    except (urllib.error.URLError, TimeoutError, ssl.SSLError, OSError):
        return {"state": "FAIL", "http_status": None, "tls_verification": "strict",
                "redirects_followed": False, "reason": "TLS_OR_CONNECTION_FAILURE"}



def isolated_process_env() -> dict[str, str]:
    """Allowlist only OS subprocess essentials, not deployment integrations.

    Unit tests must never inherit real DB/Redis/mail/cloud endpoints or secrets.
    This is an environment isolation guard, not a network sandbox.
    """
    portable_keys = (
        "PATH", "HOME", "TMPDIR", "TEMP", "TMP", "SYSTEMROOT", "WINDIR",
        "LANG", "LC_ALL", "LC_CTYPE", "TZ",
    )
    return {name: os.environ[name] for name in portable_keys if name in os.environ}


def disposable_phpunit_env() -> dict[str, str]:
    """Prepare a fresh, explicitly nonpersistent Laravel testing environment."""
    env = isolated_process_env()
    env.update({
        "APP_ENV": "testing",
        "APP_KEY": "base64:" + base64.b64encode(secrets.token_bytes(32)).decode("ascii"),
        "DB_CONNECTION": "sqlite",
        "DB_DATABASE": ":memory:",
        "CACHE_STORE": "array",
        "CACHE_DRIVER": "array",
        "SESSION_DRIVER": "array",
        "QUEUE_CONNECTION": "sync",
        "MAIL_MAILER": "array",
        "BROADCAST_CONNECTION": "log",
    })
    return env


def php_extension_inventory(php: str | None) -> dict:
    """Report PHP CLI extensions without printing php.ini output or environment secrets.

    This is an environment capability check, never a native PHPUnit success claim.
    """
    if not php:
        return {"status": "NOT_AVAILABLE", "missing": []}
    safe_env = isolated_process_env()
    try:
        result = subprocess.run([php, "-m"], env=safe_env, capture_output=True,
                                text=True, timeout=10)
    except (OSError, subprocess.TimeoutExpired):
        return {"status": "UNKNOWN", "missing": []}
    if result.returncode != 0 or not isinstance(result.stdout, str):
        return {"status": "UNKNOWN", "missing": []}
    modules = {line.strip().lower() for line in result.stdout.splitlines()}
    # Disposable PHPUnit explicitly configures Laravel's :memory: SQLite DB.
    # PDO alone cannot open that database: PDO_SQLITE must be loaded too.
    required = ["dom", "mbstring", "zip", "pdo_sqlite"]
    missing = [module for module in required if module not in modules]
    return {"status": "INCOMPLETE" if missing else "PASS", "missing": missing}


def _contained_source_state(repo: pathlib.Path, relative_path: str) -> str:
    """Treat out-of-checkout source targets as unsafe, not as present files.

    Component symlinks that resolve within the checkout remain supported (e.g.
    Composer-created internal binary links); dangling/external links never run.
    """
    path = repo / relative_path
    try:
        resolved = path.resolve()
        if not resolved.is_relative_to(repo):
            return "UNSAFE" if path.exists() or path.is_symlink() else "MISSING"
        return "PRESENT" if path.is_file() else "MISSING"
    except (OSError, RuntimeError, ValueError):
        return "MISSING"


def _native_runtime_markers_present(repo: pathlib.Path) -> bool:
    # Even a broken symlink at a runtime directory is evidence this is not a
    # clean disposable checkout. Avoid following a link into another install.
    for rel in ["storage", "storage/app", "storage/app/nexora",
                "storage/app/nexora/environment", "bootstrap/cache"]:
        if (repo / rel).is_symlink():
            return True
    # Laravel may load .env.testing/.env.staging even when the regular .env is
    # absent. A symlink (including a dangling one) is also a runtime marker.
    template_suffixes = (".example", ".sample", ".dist", ".template")
    if any(not candidate.name.endswith(template_suffixes)
           for candidate in repo.glob(".env.*")):
        return True
    return any((repo / path).exists() or (repo / path).is_symlink() for path in [
        ".env", "storage/app/nexora/environment/.env",
        "storage/app/nexora/environment/active",
        "storage/app/nexora/environment/bootstrap.key",
        "storage/app/nexora/installed.lock", "bootstrap/cache/config.php",
    ])


# Source Guard checks the contents; these roots first prevent confusing a
# handful of scaffolding files with a full integrated Nexora source checkout.
REQUIRED_SOURCE_ROOTS = (
    "app", "bootstrap", "config", "database", "resources",
    "routes", "scripts", "tests", "themes", "public",
)


def probe_repo(repo_path: pathlib.Path, allow_native_unit: bool = False):
    repo = repo_path.resolve()
    required = ["artisan", "bootstrap/app.php", "composer.json", "composer.lock",
                "scripts/source-guard.php", "scripts/refresh-critical-source-manifest.php",
                "tests/Unit/HistoricalKeyExposureAuditTest.php", "vendor/autoload.php",
                "vendor/bin/phpunit"]
    source_states = {f: _contained_source_state(repo, f) for f in required}
    root_states = {
        rel: ((repo / rel).is_dir() and (repo / rel).resolve().is_relative_to(repo))
        for rel in REQUIRED_SOURCE_ROOTS
    }
    missing_roots = [rel for rel, present in root_states.items() if not present]
    missing = [f for f, state in source_states.items() if state != "PRESENT"]
    unsafe = [f for f, state in source_states.items() if state == "UNSAFE"]
    php = shutil.which("php")
    composer = shutil.which("composer")
    ext = php_extension_inventory(php)
    result = {
        "repository_full_checkout": (all(source_states[f] == "PRESENT" for f in required[:7])
                                         and not missing_roots),
        "vendor_dependencies_present": all(source_states[f] == "PRESENT" for f in required[7:]),
        "php_cli_available": php is not None,
        "composer_cli_available": composer is not None,
        "php_extensions_status": ext["status"],
        "missing_php_extensions": ext["missing"],
        "missing_prerequisites": missing,
        "missing_source_roots": missing_roots,
        **({"unsafe_source_paths": unsafe} if unsafe else {}),
        "source_guard": "NOT_RUN", "manifest_check": "NOT_RUN",
        "native_phpunit": "NOT_RUN",
    }
    if not result["repository_full_checkout"] or php is None:
        result["reason"] = "FULL_CHECKOUT_OR_PHP_MISSING"
        return result
    # Keep process runner read-only; never install dependencies or modify runtime.
    # Source Guard is source-only, manifest check is explicit --check (never --write).
    safe_env = isolated_process_env()
    for label, args in [
        ("source_guard", [php, "scripts/source-guard.php", "--source-only"]),
        ("manifest_check", [php, "scripts/refresh-critical-source-manifest.php", "--check"]),
    ]:
        try:
            proc = subprocess.run(args, cwd=repo, env=safe_env, capture_output=True,
                                  text=True, timeout=120)
            result[label] = "PASS" if proc.returncode == 0 else "FAIL"
            result[label + "_exit_code"] = proc.returncode
            # Never include untrusted CLI output; it could include a secret.
        except (OSError, subprocess.TimeoutExpired):
            result[label] = "ERROR"
    # Native execution requires BOTH source-only integrity gates to PASS.
    # A failed, timed-out, or unavailable gate must never be bypassed merely
    # because the disposable-checkout opt-in and PHP dependencies are present.
    if result["source_guard"] != "PASS" or result["manifest_check"] != "PASS":
        result["native_phpunit"] = "BLOCKED_INTEGRITY_CHECKS"
    elif not result["vendor_dependencies_present"]:
        result["native_phpunit"] = "BLOCKED_DEPENDENCIES"
    elif not allow_native_unit:
        result["native_phpunit"] = "SKIPPED_OPT_IN_REQUIRED"
    # Symlinked/broken .env and installed markers still represent a potentially
    # real deployment; do not run PHPUnit with a disposable-checkout opt-in.
    elif _native_runtime_markers_present(repo):
        result["native_phpunit"] = "BLOCKED_EXISTING_RUNTIME_ENVIRONMENT"
    elif ext["status"] != "PASS":
        result["native_phpunit"] = "BLOCKED_PHP_EXTENSIONS"
    elif os.environ.get("NEXORA_DISPOSABLE_TEST_CHECKOUT") != "1":
        result["native_phpunit"] = "SKIPPED_DISPOSABLE_CHECKOUT_OPT_IN_REQUIRED"
    else:
        try:
            p = subprocess.run([php, "vendor/bin/phpunit", "tests/Unit/HistoricalKeyExposureAuditTest.php"],
                               cwd=repo, env=disposable_phpunit_env(),
                               capture_output=True, text=True, timeout=120)
            result["native_phpunit"] = "PASS" if p.returncode == 0 else "FAIL"
            result["native_phpunit_exit_code"] = p.returncode
        except (OSError, subprocess.TimeoutExpired):
            result["native_phpunit"] = "ERROR"
    return result



def reject_symlink_path_components(path: pathlib.Path) -> None:
    """Reject symlinks in the destination or any ancestor *before* resolve().

    A symlinked parent otherwise redirects an apparently safe output filename
    into an unrelated tree, bypassing the final-path-only symlink guard.
    Refuse literal ``..`` components rather than normalizing through a link.
    """
    absolute = path.absolute()
    if ".." in absolute.parts:
        raise ValueError("Evidence destination must not include parent traversal")
    current = pathlib.Path(absolute.anchor)
    for component in absolute.parts[1:]:
        current = current / component
        if current.is_symlink():
            raise ValueError("Evidence destination contains a symlink component")


def write_private_evidence(path: pathlib.Path, payload: bytes) -> pathlib.Path:
    """Atomically save private QA evidence without following an output-file symlink.

    Existing normal reports can be replaced for repeatable CLI workflows.  The
    temporary file is mode 0600 and os.replace replaces *the name*, not the
    contents of an existing file or symlink.  Refuse symlink destinations even
    though the rename itself is atomic, to avoid confusing evidence aliases.
    """
    reject_symlink_path_components(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    reject_symlink_path_components(path)
    fd, temporary = tempfile.mkstemp(prefix=".nexora-evidence-", dir=path.parent)
    try:
        with os.fdopen(fd, "wb") as stream:
            stream.write(payload)
            stream.flush()
            os.fsync(stream.fileno())
        if path.is_symlink():
            raise ValueError("Evidence output destination changed to a symlink")
        os.replace(temporary, path)
    finally:
        if os.path.lexists(temporary):
            os.unlink(temporary)
    return path


def save_masked_login_evidence(png: bytes, screenshot_dir: pathlib.Path,
                               viewport: int) -> pathlib.Path:
    """Save only the caller's already-masked screenshot bytes, never via a link."""
    reject_symlink_path_components(screenshot_dir)
    screenshot_dir.mkdir(mode=0o700, parents=True, exist_ok=True)
    reject_symlink_path_components(screenshot_dir)
    if screenshot_dir.stat().st_mode & 0o077:
        # Do not chmod an existing directory we do not own/control: refuse it.
        raise ValueError("Evidence screenshot directory is not private")
    if not isinstance(png, bytes) or not png:
        raise ValueError("Browser did not supply screenshot bytes")
    return write_private_evidence(screenshot_dir / f"login-{viewport}.png", png)


def inspect_login_ui(page) -> dict[str, bool]:
    """Verify browser-rendered, visible login controls without reading any values.

    Only booleans leave the browser page. This check deliberately does not
    authenticate, submit a form, or inspect cookies, tokens or text content.
    """
    result = page.evaluate(r"""() => {
        // Check effective visibility and fieldset/ancestor disabled state, not
        // only an input's own style/disabled property. An opacity-zero parent
        // or disabled fieldset must not count as a working login control.
        const usable = el => {
            if (!el || el.matches(':disabled') || el.closest('[inert],[aria-disabled="true"]'))
                return false;
            if (el.getClientRects().length === 0) return false;
            for (let node = el; node && node.nodeType === 1; node = node.parentElement) {
                const css = window.getComputedStyle(node);
                if (css.display === 'none' || css.visibility !== 'visible'
                    || Number(css.opacity) === 0 || css.pointerEvents === 'none')
                    return false;
            }
            return true;
        };
        const password = [...document.querySelectorAll('form input[type="password"]')]
            .find(el => usable(el));
        // A standalone input plus unrelated submit button is not a login form.
        const form = password?.closest('form');
        const fields = form ? [...form.querySelectorAll(
            'input[type="email"],input[name="email"],input[name="username"],'
            + 'input[autocomplete="username"],input[type="text"]')] : [];
        const identities = fields.filter(el => usable(el));
        const submitSelector = 'button[type="submit"],input[type="submit"],button:not([type])';
        const submits = form ? [...form.querySelectorAll(submitSelector)] : [];
        return {
            password_visible: !!password,
            identity_visible: identities.length > 0,
            submit_visible: submits.some(el => usable(el)),
            horizontal_overflow: document.documentElement.scrollWidth > window.innerWidth + 2
        };
    }""")
    keys = ('password_visible', 'identity_visible', 'submit_visible', 'horizontal_overflow')
    if not isinstance(result, dict) or any(type(result.get(key)) is not bool for key in keys):
        raise ValueError("Unexpected browser login UI contract result")
    return {key: result[key] for key in keys}



def route_approved_browser_request(route, origin: str) -> None:
    """Allow only read-only GETs to approved HTTPS origin, no redirect hops.

    Same-origin write requests are blocked at request interception, before
    connection. This is deliberately stricter than ordinary browser behavior;
    it is not a guarantee that GET handlers have no application-side effects.
    Operator acceptance remains manual.
    """
    try:
        approved = urllib.parse.urlsplit(safe_origin(origin))
        request = route.request
        candidate = urllib.parse.urlsplit(request.url)
        approved_host = (candidate.scheme == "https"
                         and candidate.hostname == approved.hostname
                         and candidate.port == approved.port
                         and candidate.username is None and candidate.password is None)
        navigation = request.is_navigation_request()
        # Browser evidence is read-only. Same-origin does NOT imply that a
        # POST/PUT/PATCH/DELETE (or even an OPTIONS preflight) is safe: login
        # JavaScript can emit mutations before its screenshot is captured.
        # Only exact GET is permitted; absent/malformed methods fail closed.
        allowed = (request.method == "GET" and approved_host and
                   (not navigation or (
                       request.redirected_from is None
                       and request.url == safe_origin(origin) + "/login")))
    except (ValueError, TypeError, AttributeError):
        allowed = False
    if allowed:
        route.continue_()
    else:
        route.abort("blockedbyclient")


# Read-only evidence must not initiate WebSockets or background workers.  The
# WebSocket route is a second line of defence if page JS replaces the stubs or a
# worker initiates a socket: Playwright exposes a VIRTUAL socket to the page,
# but will not connect upstream unless route.connect() is called.  Do not call
# route.close() synchronously inside Playwright's sync route callback: with the
# installed Playwright/Chromium combination it can deadlock the browser callback.
NO_BACKGROUND_CONNECTIONS_SCRIPT = r"""(() => {
  const denied = name => class { constructor() {
    throw new Error(name + ' disabled during read-only acceptance QA');
  }};
  for (const name of ['WebSocket', 'Worker', 'SharedWorker']) {
    Object.defineProperty(globalThis, name, {
      configurable: false, writable: false, value: denied(name)
    });
  }
})()"""


def block_browser_websocket(route) -> None:
    """Intercept socket without ever calling connect() (no upstream handshake)."""
    return None


def browser_smoke(origin: str, screenshot_dir: pathlib.Path):
    """Non-authenticated browser navigation only; owner acceptance still required."""
    try:
        origin = safe_origin(origin)
    except (TypeError, ValueError):
        return {"state": "FAIL", "reason": "INVALID_ORIGIN", "cases": []}
    try:
        from playwright.sync_api import sync_playwright
    except ImportError:
        return {"state": "BLOCKED", "reason": "PLAYWRIGHT_MISSING"}
    executable = shutil.which("chromium") or shutil.which("google-chrome")
    if not executable:
        return {"state": "BLOCKED", "reason": "CHROMIUM_MISSING"}
    # Do not follow preexisting screenshot aliases, even before launching a browser.
    try:
        reject_symlink_path_components(screenshot_dir)
        if screenshot_dir.exists() and screenshot_dir.stat().st_mode & 0o077:
            raise ValueError("Evidence screenshot directory is not private")
        for width in (320, 390, 768, 1440):
            reject_symlink_path_components(screenshot_dir / f"login-{width}.png")
    except (ValueError, OSError):
        return {"state": "FAIL", "reason": "UNSAFE_SCREENSHOT_OUTPUT", "cases": []}
    cases = []
    try:
        with sync_playwright() as p:
            # Do not pass inherited deployment credentials or proxy settings
            # into the Chromium process while collecting acceptance evidence.
            browser = p.chromium.launch(headless=True, executable_path=executable,
                                        args=["--no-sandbox"], env=isolated_process_env())
            for width in [320, 390, 768, 1440]:
                context = browser.new_context(viewport={"width": width, "height": 900},
                                              ignore_https_errors=False, service_workers="block")
                # Enforce the network boundary before even the first page request.
                # Redirect destinations and unapproved asset hosts are never fetched.
                context.route("**/*", lambda route: route_approved_browser_request(route, origin))
                context.route_web_socket("**/*", block_browser_websocket)
                context.add_init_script(NO_BACKGROUND_CONNECTIONS_SCRIPT)
                page = context.new_page()
                # Force real browser TLS validation. Refuse redirects for acceptance.
                response = page.goto(origin + "/login", wait_until="domcontentloaded", timeout=15000)
                redirected = bool(response and response.request.redirected_from)
                direct_login = bool(response and response.status == 200
                                    and page.url == origin + "/login" and not redirected)
                case = {"viewport": width, "state": "FAIL",
                        "http_status": response.status if response else None,
                        "redirect_chain_detected": redirected,
                        "ui_contract": "NOT_RUN"}
                if direct_login:
                    # HTTP 200 alone is not a UI acceptance signal. A blank page,
                    # unrelated HTML or horizontally overflowing login is a FAIL.
                    ui = inspect_login_ui(page)
                    valid = (ui["password_visible"] and ui["identity_visible"]
                             and ui["submit_visible"] and not ui["horizontal_overflow"])
                    case["ui_contract"] = "PASS" if valid else "FAIL"
                    if valid:
                        case["state"] = "PASS"
                        # Obtain masked pixels in memory, then write atomically
                        # with private permissions (no Playwright path traversal).
                        png = page.screenshot(
                            full_page=True,
                            mask=[page.locator('input,textarea,[contenteditable="true"]')])
                        save_masked_login_evidence(png, screenshot_dir, width)
                        case["screenshot_written"] = True
                cases.append(case)
                context.close()
            browser.close()
        return {"state": "PASS" if all(c["state"] == "PASS" for c in cases) else "FAIL",
                "cases": cases, "scope": "unauthenticated login screenshots only"}
    except Exception:
        return {"state": "FAIL", "reason": "BROWSER_TLS_NAVIGATION_OR_RUNTIME_ERROR", "cases": cases}



def evaluate_requested_checks(report: dict, *, repo_requested: bool, origin_requested: bool,
                              browser_requested: bool, native_requested: bool) -> dict:
    """Return fail-closed *requested-check execution* status, never acceptance.

    A zero exit status in strict mode means ONLY that explicitly requested
    technical checks passed. It is not security-review approval, historical
    key retirement, authorized target verification, or Phase-1 closure.
    """
    checks: dict[str, bool] = {}
    if repo_requested:
        repository = report.get("repo") or {}
        checks["source_guard"] = repository.get("source_guard") == "PASS"
        checks["critical_manifest"] = repository.get("manifest_check") == "PASS"
        if native_requested:
            checks["native_phpunit"] = repository.get("native_phpunit") == "PASS"
    elif native_requested:
        checks["native_phpunit"] = False
    if origin_requested:
        checks["https_login"] = (report.get("login_https") or {}).get("state") == "PASS"
    if browser_requested:
        checks["browser_login"] = (report.get("browser") or {}).get("state") == "PASS"
    failed = [name for name, ok in checks.items() if not ok]
    return {
        "scope": "requested_technical_checks_only_not_phase1_acceptance",
        "state": "PASS" if checks and not failed else "FAIL",
        "checks": checks,
        "failed": failed if checks else ["NO_CHECKS_REQUESTED"],
    }


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--repo", help="Local Nexora source checkout (read-only checks)")
    ap.add_argument("--origin", help="Actual operator-approved HTTPS origin")
    ap.add_argument("--ca-file", help="Optional trusted CA for private HTTPS testing")
    ap.add_argument("--browser", action="store_true", help="Read-only login browser screenshots")
    ap.add_argument("--run-native-unit", action="store_true", help="Opt in to unit tests on disposable checkout only")
    ap.add_argument("--out", required=True, help="Output JSON path outside target checkout")
    ap.add_argument("--strict-requested-checks", action="store_true",
                    help="Exit 2 if any requested technical check fails or no check was requested; never implies final acceptance")
    args = ap.parse_args()
    try:
        origin = safe_origin(args.origin) if args.origin else None
    except ValueError as e:
        ap.error(str(e))
    # resolve() discards the fact that --out itself was an alias. Refuse that
    # explicitly before resolving the target, rather than overwriting its bytes.
    raw_dest = pathlib.Path(args.out).absolute()
    try:
        reject_symlink_path_components(raw_dest)
    except ValueError as e:
        ap.error(str(e))
    dest = raw_dest.resolve()
    if args.repo:
        resolved = pathlib.Path(args.repo).resolve()
        if dest == resolved or resolved in dest.parents:
            ap.error("Evidence report must be written outside the target checkout")
    login_result = tls_login_check(origin, args.ca_file) if origin else {"state": "NOT_RUN_NO_APPROVED_ORIGIN"}
    report = {"schema": 1, "tool": "phase1_acceptance_probe", "created_at":
              datetime.datetime.now(datetime.timezone.utc).isoformat(),
              "read_only_target": True, "real_host_origin_provided": bool(origin),
              "repo": probe_repo(pathlib.Path(args.repo), args.run_native_unit) if args.repo else None,
              "login_https": login_result,
              "browser": (browser_smoke(origin, dest.parent / "screenshots")
                          if origin and args.browser and login_result["state"] == "PASS"
                          else {"state": "NOT_RUN_TLS_PRECHECK_REQUIRED" if origin and args.browser
                                else "NOT_RUN"}),
              "non_use_proved": False, "historical_key_retired": False,
              "target_verified": False, "independent_security_approved": False,
              "phase1_closed": False,
              "remaining_requirements": [
                  "Full integrated repository plus Composer dependencies and native tests",
                  "Independent authorized security reviewer acceptance",
                  "Approved owner browser acceptance for installer and authenticated Admin Health",
                  "Affected-install inventory and accepted historical key non-use or safe retirement",
                  "Fresh exact-target CLI/web challenge and current readiness receipt",
                  "Explicit final operator acceptance before PR merge",
              ]}
    if args.strict_requested_checks:
        report["requested_check_gate"] = evaluate_requested_checks(
            report, repo_requested=bool(args.repo), origin_requested=bool(origin),
            browser_requested=args.browser, native_requested=args.run_native_unit,
        )
    write_private_evidence(dest, (json.dumps(report, indent=2) + "\n").encode("utf-8"))
    print(json.dumps({"result": "EVIDENCE_RECORDED_NOT_ACCEPTANCE",
                      "repo": report["repo"], "login_https": report["login_https"],
                      "browser": report["browser"], "report": str(dest),
                      **({"requested_check_gate": report["requested_check_gate"]}
                         if args.strict_requested_checks else {})}, indent=2))
    return 2 if args.strict_requested_checks and report["requested_check_gate"]["state"] != "PASS" else 0


if __name__ == "__main__":
    raise SystemExit(main())
