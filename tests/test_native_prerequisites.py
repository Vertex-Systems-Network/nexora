"""Read-only PHP extension preflight and unit-test fail-closed regressions."""
import pathlib
import tempfile
import unittest
from unittest.mock import patch
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


class FakeProcess:
    def __init__(self, code=0, stdout=''):
        self.returncode = code
        self.stdout = stdout


class NativePrerequisiteTests(unittest.TestCase):
    def test_detect_php_extensions_and_legacy_names_case_insensitive(self):
        with patch.object(probe.subprocess, 'run', return_value=FakeProcess(0, '[PHP Modules]\nCore\nDOM\nmbstring\nZIP\nPDO_SQLITE\n')) as run:
            result = probe.php_extension_inventory('/usr/bin/php')
        self.assertEqual(result['status'], 'PASS')
        self.assertEqual(result['missing'], [])
        self.assertEqual(run.call_args.args[0], ['/usr/bin/php', '-m'])

    def test_sqlite_pdo_driver_required_for_in_memory_test_database(self):
        # All previously checked modules are present, yet :memory: SQLite
        # cannot run without PDO_SQLITE. This was a false PASS in v24.
        with patch.object(probe.subprocess, 'run', return_value=FakeProcess(
                0, '[PHP Modules]\nCore\nPDO\nDOM\nmbstring\nZIP\n')):
            result = probe.php_extension_inventory('/usr/bin/php')
        self.assertEqual(result, {'status': 'INCOMPLETE', 'missing': ['pdo_sqlite']})

    def test_sqlite_driver_is_case_insensitive_when_loaded(self):
        with patch.object(probe.subprocess, 'run', return_value=FakeProcess(
                0, '[PHP Modules]\nCore\nPDO\nDOM\nmbstring\nZIP\nPDO_SQLITE\n')):
            result = probe.php_extension_inventory('/usr/bin/php')
        self.assertEqual(result, {'status': 'PASS', 'missing': []})

    def test_detect_missing_extensions_without_printing_process_output(self):
        with patch.object(probe.subprocess, 'run', return_value=FakeProcess(0, '[PHP Modules]\nCore\nPDO\nopenssl\n')):
            result = probe.php_extension_inventory('/usr/bin/php')
        self.assertEqual(result, {'status': 'INCOMPLETE', 'missing': ['dom', 'mbstring', 'zip', 'pdo_sqlite']})

    def test_error_is_unknown_not_optimistically_clean(self):
        with patch.object(probe.subprocess, 'run', return_value=FakeProcess(1, 'sensitive stderr')):
            self.assertEqual(probe.php_extension_inventory('/usr/bin/php'), {'status': 'UNKNOWN', 'missing': []})

    def test_missing_php_binary_not_known_clean(self):
        self.assertEqual(probe.php_extension_inventory(None), {'status': 'NOT_AVAILABLE', 'missing': []})

    def test_partial_checkout_lists_missing_php_modules(self):
        with tempfile.TemporaryDirectory() as td:
            output = probe.probe_repo(pathlib.Path(td))
        self.assertEqual(output['php_extensions_status'], 'INCOMPLETE')
        self.assertIn('dom', output['missing_php_extensions'])
        self.assertEqual(output['native_phpunit'], 'NOT_RUN')

    def test_complete_checkout_with_missing_php_extensions_never_executes_phpunit(self):
        with tempfile.TemporaryDirectory() as td:
            root = pathlib.Path(td)
            required = ['artisan', 'bootstrap/app.php', 'composer.json', 'composer.lock',
                        'scripts/source-guard.php', 'scripts/refresh-critical-source-manifest.php',
                        'tests/Unit/HistoricalKeyExposureAuditTest.php',
                        'vendor/autoload.php', 'vendor/bin/phpunit']
            for rel in required:
                path = root / rel
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text('fixture only\n')
            for name in probe.REQUIRED_SOURCE_ROOTS:
                (root / name).mkdir(parents=True, exist_ok=True)
            cmds = []
            def fake_run(args, **kwargs):
                cmds.append(args)
                if args == ['/usr/bin/php', '-m']:
                    return FakeProcess(0, 'Core\nopenssl\n')
                return FakeProcess(0, '')
            with patch.object(probe.shutil, 'which', side_effect=lambda x: '/usr/bin/php' if x=='php' else None), \
                 patch.object(probe.subprocess, 'run', side_effect=fake_run), \
                 patch.dict(probe.os.environ, {'NEXORA_DISPOSABLE_TEST_CHECKOUT': '1'}):
                output = probe.probe_repo(root, allow_native_unit=True)
            self.assertEqual(output['source_guard'], 'PASS')
            self.assertEqual(output['manifest_check'], 'PASS')
            self.assertEqual(output['native_phpunit'], 'BLOCKED_PHP_EXTENSIONS')
            self.assertNotIn(['/usr/bin/php', 'vendor/bin/phpunit', 'tests/Unit/HistoricalKeyExposureAuditTest.php'], cmds)


if __name__ == '__main__':
    unittest.main()
