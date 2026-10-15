"""Native PHPUnit must not execute after a failed integrity or manifest check."""
import pathlib
import os
import tempfile
import unittest
from unittest.mock import patch

import sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / "tools"))
import phase1_acceptance_probe as probe


class Completed:
    def __init__(self, code=0, stdout=""):
        self.returncode = code
        self.stdout = stdout


def build_fixture(root):
    for rel in probe.REQUIRED_SOURCE_ROOTS:
        (root / rel).mkdir(parents=True, exist_ok=True)
    for rel in (
        "artisan", "bootstrap/app.php", "composer.json", "composer.lock",
        "scripts/source-guard.php", "scripts/refresh-critical-source-manifest.php",
        "tests/Unit/HistoricalKeyExposureAuditTest.php", "vendor/autoload.php",
        "vendor/bin/phpunit",
    ):
        p = root / rel
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text("fixture only\n")


def test_case(guard_return=0, manifest_return=0, guard_error=False):
    with tempfile.TemporaryDirectory() as td:
        root=pathlib.Path(td)
        build_fixture(root)
        calls=[]
        def runner(argv, **kwargs):
            calls.append(list(argv))
            if argv[-1]=="-m": return Completed(stdout="Core\ndom\nmbstring\nzip\npdo_sqlite\n")
            if argv[1]=="scripts/source-guard.php":
                if guard_error: raise probe.subprocess.TimeoutExpired(argv, 1)
                return Completed(guard_return)
            if argv[1]=="scripts/refresh-critical-source-manifest.php": return Completed(manifest_return)
            if argv[1]=="vendor/bin/phpunit": return Completed(0)
            raise AssertionError("Unexpected command")
        with patch.object(probe.shutil, "which", side_effect=lambda binary: "/usr/bin/php" if binary=="php" else None), \
             patch.object(probe.subprocess, "run", side_effect=runner), \
             patch.dict(os.environ, {"NEXORA_DISPOSABLE_TEST_CHECKOUT":"1"}):
            result=probe.probe_repo(root, allow_native_unit=True)
        return result, calls


class IntegrityGateTests(unittest.TestCase):
    def test_guard_failure_blocks_phpunit(self):
        result, calls=test_case(guard_return=13)
        self.assertEqual(result["source_guard"], "FAIL")
        self.assertEqual(result["native_phpunit"], "BLOCKED_INTEGRITY_CHECKS")
        self.assertFalse(any("vendor/bin/phpunit" in cmd for cmd in calls))

    def test_manifest_failure_blocks_phpunit(self):
        result, calls=test_case(manifest_return=21)
        self.assertEqual(result["manifest_check"], "FAIL")
        self.assertEqual(result["native_phpunit"], "BLOCKED_INTEGRITY_CHECKS")
        self.assertFalse(any("vendor/bin/phpunit" in cmd for cmd in calls))

    def test_timeout_blocks_phpunit(self):
        result,calls=test_case(guard_error=True)
        self.assertEqual(result["source_guard"], "ERROR")
        self.assertEqual(result["native_phpunit"], "BLOCKED_INTEGRITY_CHECKS")
        self.assertFalse(any("vendor/bin/phpunit" in cmd for cmd in calls))

    def test_both_integrity_checks_pass_runs_only_disposable_phpunit(self):
        result,calls=test_case()
        self.assertEqual(result["source_guard"], "PASS")
        self.assertEqual(result["manifest_check"], "PASS")
        self.assertEqual(result["native_phpunit"], "PASS")
        self.assertEqual(sum("vendor/bin/phpunit" in cmd for cmd in calls),1)

if __name__ == "__main__": unittest.main()
