"""No native PHPUnit invocation may inherit operator database or service credentials."""
import os
import pathlib
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / "tools"))
import phase1_acceptance_probe as probe

REQUIRED = (
    "artisan", "bootstrap/app.php", "composer.json", "composer.lock",
    "scripts/source-guard.php", "scripts/refresh-critical-source-manifest.php",
    "tests/Unit/HistoricalKeyExposureAuditTest.php", "vendor/autoload.php",
    "vendor/bin/phpunit",
)


def fixture(root):
    for name in probe.REQUIRED_SOURCE_ROOTS:
        (root / name).mkdir(parents=True, exist_ok=True)
    for name in REQUIRED:
        path = root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text("fixture\n")


class FakeProcess:
    def __init__(self, stdout=""):
        self.returncode = 0
        self.stdout = stdout


def exercise(repo, env):
    calls = []
    def fake_run(args, **kwargs):
        calls.append((args, kwargs.get("env", {})))
        if args[-1] == "-m":
            return FakeProcess("[PHP Modules]\nCore\ndom\nmbstring\nzip\npdo_sqlite\n")
        return FakeProcess()
    with patch.object(probe.shutil, "which", side_effect=lambda cmd: "/usr/bin/php" if cmd == "php" else None), \
         patch.object(probe.subprocess, "run", side_effect=fake_run), \
         patch.dict(os.environ, env, clear=False):
        result = probe.probe_repo(repo, allow_native_unit=True)
    return result, calls


class NativeEnvironmentIsolation(unittest.TestCase):
    def test_external_database_credentials_and_service_endpoints_never_reach_phpunit(self):
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td)
            fixture(repo)
            result, calls = exercise(repo, {
                "NEXORA_DISPOSABLE_TEST_CHECKOUT":"1",
                "DB_HOST":"prod-db.example.org", "DB_DATABASE":"live", "DB_USERNAME":"prod",
                "DB_PASSWORD":"dont-disclose", "REDIS_HOST":"prod-cache.example.org",
                "AWS_ACCESS_KEY_ID":"external", "AWS_SECRET_ACCESS_KEY":"external-secret",
                "STRIPE_SECRET":"external-stripe-secret", "MAIL_HOST":"mail.example.org",
                "SENTRY_LARAVEL_DSN":"https://secret@remote.example",
                "QUEUE_CONNECTION":"redis", "CACHE_STORE":"redis", "SESSION_DRIVER":"redis"
            })
            self.assertEqual(result["native_phpunit"], "PASS", result)
            cmd, native_env = calls[-1]
            self.assertIn("vendor/bin/phpunit", cmd)
            for key in ["DB_HOST", "DB_USERNAME", "DB_PASSWORD", "REDIS_HOST", "AWS_ACCESS_KEY_ID",
                        "AWS_SECRET_ACCESS_KEY", "STRIPE_SECRET", "MAIL_HOST", "SENTRY_LARAVEL_DSN"]:
                self.assertNotIn(key, native_env, key)
            self.assertEqual(native_env["DB_CONNECTION"], "sqlite")
            self.assertEqual(native_env["DB_DATABASE"], ":memory:")
            self.assertEqual(native_env["CACHE_STORE"], "array")
            self.assertEqual(native_env["SESSION_DRIVER"], "array")
            self.assertEqual(native_env["QUEUE_CONNECTION"], "sync")
            self.assertEqual(native_env["APP_ENV"], "testing")

    def test_existing_testing_dotenv_file_blocks_native_execution(self):
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td)
            fixture(repo)
            (repo / ".env.testing").write_text("DB_HOST=prod.example.org\n")
            result, calls = exercise(repo, {"NEXORA_DISPOSABLE_TEST_CHECKOUT":"1"})
            self.assertEqual(result["native_phpunit"], "BLOCKED_EXISTING_RUNTIME_ENVIRONMENT", result)
            self.assertFalse(any("vendor/bin/phpunit" in c[0] for c in calls))

    def test_existing_custom_env_file_and_broken_symlink_block_native_execution(self):
        for variant in [".env.local", ".env.staging", ".env.production", ".env.testing"]:
            with self.subTest(variant=variant), tempfile.TemporaryDirectory() as td:
                repo = pathlib.Path(td)
                fixture(repo)
                (repo / variant).symlink_to(repo / "missing-secret-file")
                result, calls = exercise(repo, {"NEXORA_DISPOSABLE_TEST_CHECKOUT":"1"})
                self.assertEqual(result["native_phpunit"], "BLOCKED_EXISTING_RUNTIME_ENVIRONMENT", result)
                self.assertFalse(any("vendor/bin/phpunit" in c[0] for c in calls))

    def test_env_example_template_is_not_a_live_runtime_marker(self):
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td)
            fixture(repo)
            (repo / ".env.example").write_text("APP_ENV=local\n")
            result, calls = exercise(repo, {"NEXORA_DISPOSABLE_TEST_CHECKOUT":"1"})
            self.assertEqual(result["native_phpunit"], "PASS", result)
            self.assertTrue(any("vendor/bin/phpunit" in c[0] for c in calls))


    def test_preflight_subprocesses_never_inherit_operator_credentials(self):
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td)
            fixture(repo)
            result, calls = exercise(repo, {
                "NEXORA_DISPOSABLE_TEST_CHECKOUT": "1",
                "APP_KEY": "base64:REAL_DEPLOYMENT_KEY",
                "APP_PREVIOUS_KEYS": "SECRET_HISTORY",
                "DB_PASSWORD": "host-password",
                "DB_HOST": "host-db.example.org",
                "NEXORA_ACTIVATION_TOKEN": "host-activation",
                "HTTPS_PROXY": "http://internal.proxy:8888",
            })
            self.assertEqual(result["native_phpunit"], "PASS", result)
            self.assertEqual(len(calls), 4)
            for _, env in calls:
                self.assertNotIn("DB_PASSWORD", env)
                self.assertNotIn("DB_HOST", env)
                self.assertNotIn("APP_PREVIOUS_KEYS", env)
                self.assertNotIn("NEXORA_ACTIVATION_TOKEN", env)
                self.assertNotIn("HTTPS_PROXY", env)
            self.assertNotEqual(calls[-1][1].get("APP_KEY"), "base64:REAL_DEPLOYMENT_KEY")
            self.assertNotIn("APP_KEY", calls[1][1], "source guard should not receive any application key")
            self.assertNotIn("APP_KEY", calls[2][1], "manifest check should not receive any application key")

    def test_bootstrap_key_runtime_marker_blocks_native_unit_execution(self):
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td)
            fixture(repo)
            secret = repo / "storage/app/nexora/environment/bootstrap.key"
            secret.parent.mkdir(parents=True)
            secret.write_text("placeholder - never a real key")
            result, calls = exercise(repo, {"NEXORA_DISPOSABLE_TEST_CHECKOUT": "1"})
            self.assertEqual(result["native_phpunit"], "BLOCKED_EXISTING_RUNTIME_ENVIRONMENT", result)
            self.assertFalse(any("vendor/bin/phpunit" in cmd for cmd, _ in calls))

    def test_bootstrap_key_broken_symlink_blocks_native_unit_execution(self):
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td)
            fixture(repo)
            secret = repo / "storage/app/nexora/environment/bootstrap.key"
            secret.parent.mkdir(parents=True)
            secret.symlink_to(repo / "missing-secret")
            result, calls = exercise(repo, {"NEXORA_DISPOSABLE_TEST_CHECKOUT": "1"})
            self.assertEqual(result["native_phpunit"], "BLOCKED_EXISTING_RUNTIME_ENVIRONMENT", result)
            self.assertFalse(any("vendor/bin/phpunit" in cmd for cmd, _ in calls))

if __name__ == "__main__":
    unittest.main()
