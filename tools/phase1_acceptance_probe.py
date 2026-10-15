#!/usr/bin/env python3
"""Read-only, fail-closed Nexora Phase-1 acceptance *evidence aid*.

This script NEVER declares TARGET_VERIFIED, key retirement, independent review,
or final release acceptance. It only records the checks actually executed.
No secret env values, cookies, HTML bodies, or activation tokens are logged.
"""
from __future__ import annotations

import argparse
import datetime
import json
import os
import pathlib
import shutil
import ssl
import subprocess
import sys
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
    ctx = ssl.create_default_context(cafile=ca_file)
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


def probe_repo(repo_path: pathlib.Path, allow_native_unit: bool = False):
    repo = repo_path.resolve()
    required = ["artisan", "bootstrap/app.php", "composer.json", "composer.lock",
                "scripts/source-guard.php", "scripts/refresh-critical-source-manifest.php",
                "tests/Unit/HistoricalKeyExposureAuditTest.php", "vendor/autoload.php",
                "vendor/bin/phpunit"]
    missing = [f for f in required if not (repo / f).is_file()]
    php = shutil.which("php")
    composer = shutil.which("composer")
    result = {
        "repository_full_checkout": all((repo / f).is_file() for f in required[:7]),
        "vendor_dependencies_present": all((repo / f).is_file() for f in required[7:]),
        "php_cli_available": php is not None,
        "composer_cli_available": composer is not None,
        "missing_prerequisites": missing,
        "source_guard": "NOT_RUN", "manifest_check": "NOT_RUN",
        "native_phpunit": "NOT_RUN",
    }
    if not result["repository_full_checkout"] or php is None:
        result["reason"] = "FULL_CHECKOUT_OR_PHP_MISSING"
        return result
    # Keep process runner read-only; never install dependencies or modify runtime.
    # Source Guard is source-only, manifest check is explicit --check (never --write).
    safe_env = dict(os.environ)
    for name in ["APP_KEY", "APP_PREVIOUS_KEYS", "DB_PASSWORD", "NEXORA_ACTIVATION_TOKEN"]:
        safe_env.pop(name, None)
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
    if not result["vendor_dependencies_present"]:
        result["native_phpunit"] = "BLOCKED_DEPENDENCIES"
    elif not allow_native_unit:
        result["native_phpunit"] = "SKIPPED_OPT_IN_REQUIRED"
    # Symlinked/broken .env and installed markers still represent a potentially
    # real deployment; do not run PHPUnit with a disposable-checkout opt-in.
    elif any((repo / p).exists() or (repo / p).is_symlink() for p in [
        ".env", "storage/app/nexora/environment/.env",
        "storage/app/nexora/environment/active",
        "storage/app/nexora/installed.lock", "bootstrap/cache/config.php",
    ]):
        result["native_phpunit"] = "BLOCKED_EXISTING_RUNTIME_ENVIRONMENT"
    elif os.environ.get("NEXORA_DISPOSABLE_TEST_CHECKOUT") != "1":
        result["native_phpunit"] = "SKIPPED_DISPOSABLE_CHECKOUT_OPT_IN_REQUIRED"
    else:
        try:
            p = subprocess.run([php, "vendor/bin/phpunit", "tests/Unit/HistoricalKeyExposureAuditTest.php"],
                               cwd=repo, env={**safe_env, "APP_ENV": "testing"},
                               capture_output=True, text=True, timeout=120)
            result["native_phpunit"] = "PASS" if p.returncode == 0 else "FAIL"
            result["native_phpunit_exit_code"] = p.returncode
        except (OSError, subprocess.TimeoutExpired):
            result["native_phpunit"] = "ERROR"
    return result


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
    cases = []
    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, executable_path=executable,
                                        args=["--no-sandbox"])
            for width in [320, 390, 768, 1440]:
                context = browser.new_context(viewport={"width": width, "height": 900},
                                              ignore_https_errors=False)
                page = context.new_page()
                # Force real browser TLS validation. Refuse redirects for acceptance.
                response = page.goto(origin + "/login", wait_until="domcontentloaded", timeout=15000)
                redirected = bool(response and response.request.redirected_from)
                case = {"viewport": width,
                        "state": "PASS" if response and response.status == 200
                        and page.url == origin + "/login" and not redirected else "FAIL",
                        "http_status": response.status if response else None,
                        "redirect_chain_detected": redirected}
                if case["state"] == "PASS":
                    screenshot_dir.mkdir(parents=True, exist_ok=True)
                    page.screenshot(path=str(screenshot_dir / f"login-{width}.png"),
                                    full_page=True)
                    case["screenshot_written"] = True
                cases.append(case)
                context.close()
            browser.close()
        return {"state": "PASS" if all(c["state"] == "PASS" for c in cases) else "FAIL",
                "cases": cases, "scope": "unauthenticated login screenshots only"}
    except Exception:
        return {"state": "FAIL", "reason": "BROWSER_TLS_NAVIGATION_OR_RUNTIME_ERROR", "cases": cases}


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--repo", help="Local Nexora source checkout (read-only checks)")
    ap.add_argument("--origin", help="Actual operator-approved HTTPS origin")
    ap.add_argument("--ca-file", help="Optional trusted CA for private HTTPS testing")
    ap.add_argument("--browser", action="store_true", help="Read-only login browser screenshots")
    ap.add_argument("--run-native-unit", action="store_true", help="Opt in to unit tests on disposable checkout only")
    ap.add_argument("--out", required=True, help="Output JSON path outside target checkout")
    args = ap.parse_args()
    try:
        origin = safe_origin(args.origin) if args.origin else None
    except ValueError as e:
        ap.error(str(e))
    dest = pathlib.Path(args.out).resolve()
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
    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_text(json.dumps(report, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({"result": "EVIDENCE_RECORDED_NOT_ACCEPTANCE",
                      "repo": report["repo"], "login_https": report["login_https"],
                      "browser": report["browser"], "report": str(dest)}, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
