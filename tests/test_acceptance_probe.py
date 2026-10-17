import importlib.util
import http.server
import pathlib
import ssl
import subprocess
import tempfile
import threading
import unittest

SCRIPT = pathlib.Path(__file__).resolve().parents[1] / 'tools' / 'phase1_acceptance_probe.py'
spec = importlib.util.spec_from_file_location('probe', SCRIPT)
probe = importlib.util.module_from_spec(spec)
spec.loader.exec_module(probe)


class Handler(http.server.BaseHTTPRequestHandler):
    requests_seen = []
    response = 200

    def do_GET(self):
        self.requests_seen.append(self.path)
        if self.path == '/login':
            self.send_response(self.response)
            if self.response == 302:
                self.send_header('Location', '/trap')
            self.end_headers()
            self.wfile.write(b'ok')
        else:
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b'trap-reached')

    def log_message(self, *args):
        pass


class ProbeContracts(unittest.TestCase):
    def test_origin_accepts_https_without_credentials(self):
        self.assertEqual(probe.safe_origin('https://site.example:8443/'), 'https://site.example:8443')

    def test_origin_rejects_unsafe_forms(self):
        for value in ['http://site.example', 'https://user:pass@site.example',
                      'https://site.example/install', 'https://site.example?token=secret',
                      'https://site.example#here', 'https://site.example:0',
                      'https://site.example:65536', 'https://']:
            with self.subTest(value=value), self.assertRaises(ValueError):
                probe.safe_origin(value)

    def test_incomplete_repo_remains_blocked(self):
        with tempfile.TemporaryDirectory() as td:
            result = probe.probe_repo(pathlib.Path(td))
            self.assertFalse(result['repository_full_checkout'])
            self.assertEqual(result['source_guard'], 'NOT_RUN')
            self.assertEqual(result['native_phpunit'], 'NOT_RUN')

    def test_https_strict_and_no_redirect(self):
        with tempfile.TemporaryDirectory() as td:
            key = pathlib.Path(td) / 'key.pem'
            cert = pathlib.Path(td) / 'cert.pem'
            subprocess.run(['openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes',
                            '-keyout', str(key), '-out', str(cert), '-days', '1',
                            '-subj', '/CN=localhost', '-addext', 'subjectAltName=DNS:localhost'],
                           check=True, capture_output=True)
            server = http.server.ThreadingHTTPServer(('127.0.0.1', 0), Handler)
            ctx = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
            ctx.load_cert_chain(str(cert), str(key))
            server.socket = ctx.wrap_socket(server.socket, server_side=True)
            thread = threading.Thread(target=server.serve_forever, daemon=True)
            thread.start()
            try:
                origin = f'https://localhost:{server.server_port}'
                Handler.requests_seen = []
                Handler.response = 200
                good = probe.tls_login_check(origin, str(cert), 3)
                self.assertEqual(good['state'], 'PASS')
                self.assertEqual(Handler.requests_seen, ['/login'])

                Handler.response = 302
                Handler.requests_seen = []
                redirect = probe.tls_login_check(origin, str(cert), 3)
                self.assertEqual(redirect['state'], 'FAIL')
                self.assertEqual(redirect['reason'], 'REDIRECT_REFUSED')
                self.assertEqual(Handler.requests_seen, ['/login'])

                Handler.response = 503
                fail = probe.tls_login_check(origin, str(cert), 3)
                self.assertEqual(fail['state'], 'FAIL')
                self.assertEqual(fail['http_status'], 503)

                Handler.response = 200
                untrusted = probe.tls_login_check(origin, None, 3)
                self.assertEqual(untrusted['state'], 'FAIL')
                self.assertEqual(untrusted['reason'], 'TLS_OR_CONNECTION_FAILURE')
            finally:
                server.shutdown()
                server.server_close()
                thread.join(timeout=3)


if __name__ == '__main__':
    unittest.main()

class ReportSafetyTests(unittest.TestCase):
    def test_report_never_asserts_final_acceptance(self):
        import json
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td) / 'repo'
            repo.mkdir()
            dest = pathlib.Path(td) / 'evidence.json'
            p = subprocess.run(['python', str(SCRIPT), '--repo', str(repo), '--out', str(dest)],
                               capture_output=True, text=True, check=True)
            report = json.loads(dest.read_text())
            self.assertEqual(report['tool'], 'phase1_acceptance_probe')
            for field in ('non_use_proved', 'historical_key_retired', 'target_verified',
                          'independent_security_approved', 'phase1_closed'):
                self.assertFalse(report[field])
            self.assertEqual(report['login_https']['state'], 'NOT_RUN_NO_APPROVED_ORIGIN')
            self.assertNotIn('APP_KEY', p.stdout)

    def test_output_inside_target_checkout_is_rejected(self):
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td) / 'repo'
            repo.mkdir()
            p = subprocess.run(['python', str(SCRIPT), '--repo', str(repo), '--out',
                                str(repo / 'evidence.json')], capture_output=True, text=True)
            self.assertNotEqual(p.returncode, 0)
            self.assertFalse((repo / 'evidence.json').exists())
