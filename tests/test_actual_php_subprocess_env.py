"""Verify source-only PHP subprocess isolation using PHP CLI, not mocks."""
import json
import os
import pathlib
import shutil
import tempfile
import unittest
from unittest.mock import patch
import sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


class ActualPHPSubprocessIsolation(unittest.TestCase):
    @unittest.skipUnless(shutil.which('php'), 'PHP CLI is not installed')
    def test_source_only_php_scripts_cannot_see_operator_service_secrets(self):
        with tempfile.TemporaryDirectory() as td:
            root=pathlib.Path(td)
            checkout=root/'disposable-fixture'
            checkout.mkdir()
            for name in probe.REQUIRED_SOURCE_ROOTS:
                (checkout/name).mkdir(parents=True,exist_ok=True)
            req=["artisan", "bootstrap/app.php", "composer.json", "composer.lock",
                 "scripts/source-guard.php", "scripts/refresh-critical-source-manifest.php",
                 "tests/Unit/HistoricalKeyExposureAuditTest.php"]
            for rel in req:
                dst=checkout/rel
                dst.parent.mkdir(parents=True,exist_ok=True)
                dst.write_text('fixture only\n')
            proof=root/'php-env-check.json'
            # Local disposable stand-in scripts, not actual Nexora source guard.
            payload='<?php file_put_contents('+json.dumps(str(proof))+',json_encode([' \
                    "'DB_HOST'=>getenv('DB_HOST')," \
                    "'APP_PREVIOUS_KEYS'=>getenv('APP_PREVIOUS_KEYS')," \
                    "'STRIPE_SECRET'=>getenv('STRIPE_SECRET')," \
                    "'NEXORA_ACTIVATION_TOKEN'=>getenv('NEXORA_ACTIVATION_TOKEN')" \
                    ']));\n'
            (checkout/'scripts/source-guard.php').write_text(payload)
            (checkout/'scripts/refresh-critical-source-manifest.php').write_text(payload)
            with patch.dict(os.environ, {
                'DB_HOST':'prod.example.org', 'APP_PREVIOUS_KEYS':'secret-history',
                'STRIPE_SECRET':'stripe-private', 'NEXORA_ACTIVATION_TOKEN':'activation-secret',
            }):
                result=probe.probe_repo(checkout)
            self.assertEqual(result['source_guard'],'PASS')
            self.assertEqual(result['manifest_check'],'PASS')
            self.assertEqual(result['native_phpunit'],'BLOCKED_DEPENDENCIES')
            actual=json.loads(proof.read_text())
            self.assertTrue(all(value is False for value in actual.values()),actual)

if __name__ == '__main__': unittest.main()
