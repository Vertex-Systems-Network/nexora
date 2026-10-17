"""Real PHP execution proof: failed source integrity cannot launch PHPUnit."""
import os
import pathlib
import shutil
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


@unittest.skipUnless(shutil.which('php'), 'PHP CLI is unavailable')
class ActualPHPIntegrityGate(unittest.TestCase):
    def test_real_php_failing_guard_or_manifest_never_starts_phpunit(self):
        for fail_name in ('source-guard.php', 'refresh-critical-source-manifest.php'):
            with self.subTest(fail_name=fail_name), tempfile.TemporaryDirectory() as td:
                root = pathlib.Path(td) / 'checkout'
                root.mkdir()
                for rel in probe.REQUIRED_SOURCE_ROOTS:
                    (root / rel).mkdir(parents=True, exist_ok=True)
                required = ('artisan', 'bootstrap/app.php', 'composer.json', 'composer.lock',
                            'scripts/source-guard.php', 'scripts/refresh-critical-source-manifest.php',
                            'tests/Unit/HistoricalKeyExposureAuditTest.php',
                            'vendor/autoload.php', 'vendor/bin/phpunit')
                for rel in required:
                    path=root/rel; path.parent.mkdir(parents=True, exist_ok=True)
                    path.write_text('<?php exit(0);\n')
                failing = root / 'scripts' / fail_name
                failing.write_text('<?php exit(17);\n')
                marker = pathlib.Path(td) / 'phpunit-ran'
                (root / 'vendor/bin/phpunit').write_text(
                    '<?php file_put_contents(' + repr(str(marker)) + ', "UNIT EXECUTED"); exit(0);\n'
                )
                # Only the capability-detection result is mocked; other PHP
                # invocations are actual local processes against fixtures.
                with patch.object(probe, 'php_extension_inventory', return_value={'status':'PASS','missing':[]}), \
                     patch.dict(os.environ, {'NEXORA_DISPOSABLE_TEST_CHECKOUT': '1'}):
                    result = probe.probe_repo(root, allow_native_unit=True)
                self.assertEqual(result['native_phpunit'], 'BLOCKED_INTEGRITY_CHECKS', result)
                self.assertFalse(marker.exists(), 'PHPUnit fixture executed after failed check')
                self.assertIn('FAIL', (result['source_guard'], result['manifest_check']))

if __name__ == '__main__': unittest.main()
