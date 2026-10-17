"""Invalid custom CA roots must produce a private FAIL report, not crash the gate."""
import json
import pathlib
import subprocess
import sys
import tempfile
import unittest

ROOT = pathlib.Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'tools'))
import phase1_acceptance_probe as probe


class TLSCACertConfiguration(unittest.TestCase):
    def test_nonexistent_ca_file_is_fail_closed_result(self):
        with tempfile.TemporaryDirectory() as td:
            result = probe.tls_login_check('https://approved.example', str(pathlib.Path(td)/'missing.pem'))
        self.assertEqual(result['state'], 'FAIL')
        self.assertEqual(result['reason'], 'CA_CONFIGURATION_FAILURE')
        self.assertEqual(result['tls_verification'], 'NOT_PERFORMED')

    def test_corrupted_ca_file_is_fail_closed_result(self):
        with tempfile.TemporaryDirectory() as td:
            ca = pathlib.Path(td)/'bad.pem'
            ca.write_text('not a certificate\n')
            result = probe.tls_login_check('https://approved.example', str(ca))
        self.assertEqual(result['state'], 'FAIL')
        self.assertEqual(result['reason'], 'CA_CONFIGURATION_FAILURE')
        self.assertEqual(result['http_status'], None)

    def test_cli_writes_private_failure_report_on_bad_ca(self):
        with tempfile.TemporaryDirectory() as td:
            root = pathlib.Path(td)
            report = root/'evidence.json'
            proc = subprocess.run([
                sys.executable, str(ROOT / 'tools/phase1_acceptance_probe.py'),
                '--origin', 'https://approved.example', '--ca-file', str(root/'no.pem'),
                '--out', str(report), '--strict-requested-checks',
            ], capture_output=True, text=True, timeout=20)
            self.assertEqual(proc.returncode, 2, proc.stderr)
            self.assertTrue(report.exists())
            data = json.loads(report.read_text())
            self.assertEqual(data['login_https']['reason'], 'CA_CONFIGURATION_FAILURE')
            self.assertEqual(data['requested_check_gate']['state'], 'FAIL')
            self.assertEqual(report.stat().st_mode & 0o077, 0)

if __name__ == '__main__': unittest.main()
