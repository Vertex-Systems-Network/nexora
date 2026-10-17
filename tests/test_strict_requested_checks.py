"""Regression: a generated report must not masquerade as a successful check."""
import contextlib
import io
import json
import pathlib
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


class StrictRequestedChecksTests(unittest.TestCase):
    def run_cli(self, *args):
        with tempfile.TemporaryDirectory() as td:
            output = pathlib.Path(td) / 'evidence.json'
            command = [sys.executable, str(pathlib.Path(probe.__file__)),
                       '--out', str(output), '--strict-requested-checks', *args]
            proc = subprocess.run(command, capture_output=True, text=True, timeout=25)
            report = json.loads(output.read_text())
            self.assertFalse(report['phase1_closed'])
            self.assertFalse(report['target_verified'])
            self.assertFalse(report['independent_security_approved'])
            self.assertEqual(report['requested_check_gate']['scope'],
                             'requested_technical_checks_only_not_phase1_acceptance')
            return proc, report

    def test_strict_without_checks_fails(self):
        proc, data = self.run_cli()
        self.assertEqual(proc.returncode, 2)
        self.assertEqual(data['requested_check_gate']['failed'], ['NO_CHECKS_REQUESTED'])

    def test_missing_checkout_fails_even_if_report_is_written(self):
        with tempfile.TemporaryDirectory() as empty:
            proc, data = self.run_cli('--repo', empty)
        self.assertEqual(proc.returncode, 2)
        self.assertIn('source_guard', data['requested_check_gate']['failed'])
        self.assertIn('critical_manifest', data['requested_check_gate']['failed'])

    def test_native_requested_without_checkout_fails(self):
        proc, data = self.run_cli('--run-native-unit')
        self.assertEqual(proc.returncode, 2)
        self.assertIn('native_phpunit', data['requested_check_gate']['failed'])

    def test_browser_requested_without_origin_fails(self):
        proc, data = self.run_cli('--browser')
        self.assertEqual(proc.returncode, 2)
        self.assertIn('browser_login', data['requested_check_gate']['failed'])

    def test_explicit_https_origin_cannot_be_false_green_on_connection_failure(self):
        # Port 1 on loopback has no test server. Browser is not requested.
        proc, data = self.run_cli('--origin', 'https://127.0.0.1:1')
        self.assertEqual(proc.returncode, 2)
        self.assertIn('https_login', data['requested_check_gate']['failed'])

    def test_requested_checks_success_never_claims_final_acceptance(self):
        fake = {
            'repo': {'source_guard': 'PASS', 'manifest_check': 'PASS', 'native_phpunit': 'PASS'},
            'login_https': {'state': 'PASS'}, 'browser': {'state': 'PASS'},
        }
        check = probe.evaluate_requested_checks(fake, repo_requested=True,
                origin_requested=True, native_requested=True, browser_requested=True)
        self.assertEqual(check['state'], 'PASS')
        self.assertEqual(len(check['checks']), 5)
        self.assertEqual(check['failed'], [])
        self.assertIn('not_phase1_acceptance', check['scope'])

    def test_native_request_must_not_pass_on_skipped_opt_in(self):
        repo = {'source_guard': 'PASS', 'manifest_check': 'PASS',
                'native_phpunit': 'SKIPPED_OPT_IN_REQUIRED'}
        check = probe.evaluate_requested_checks({'repo': repo}, repo_requested=True,
                origin_requested=False, native_requested=True, browser_requested=False)
        self.assertEqual(check['state'], 'FAIL')
        self.assertEqual(check['failed'], ['native_phpunit'])

    def test_no_strict_flag_preserves_readonly_report_generator_contract(self):
        with tempfile.TemporaryDirectory() as td:
            output = pathlib.Path(td) / 'report.json'
            proc = subprocess.run([sys.executable, str(pathlib.Path(probe.__file__)),
                                  '--out', str(output)], capture_output=True, text=True, timeout=20)
            self.assertEqual(proc.returncode, 0)
            self.assertNotIn('requested_check_gate', json.loads(output.read_text()))

if __name__ == '__main__':
    unittest.main()
