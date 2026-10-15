"""Fail-closed, synthetic browser identity and proxy-policy regressions."""
import pathlib
import sys
import tempfile
import unittest
from unittest.mock import patch
import urllib.request

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


class FakeResponse:
    status = 200
    class FakeRequest:
        redirected_from = object()
    request = FakeRequest()


class FakePage:
    def __init__(self):
        self.url = 'https://approved.example/login'
        self.screenshots = 0
    def goto(self, *args, **kwargs):
        return FakeResponse()
    def screenshot(self, **kwargs):
        self.screenshots += 1


class FakeContext:
    def __init__(self, page):
        self.page = page
    def new_page(self):
        return self.page
    def close(self):
        pass


class FakeBrowser:
    def __init__(self, page):
        self.page = page
    def new_context(self, **kwargs):
        return FakeContext(self.page)
    def close(self):
        pass


class FakePlaywright:
    def __init__(self, page):
        self.chromium = self
        self.page = page
    def launch(self, **kwargs):
        return FakeBrowser(self.page)
    def __enter__(self):
        return self
    def __exit__(self, *_):
        pass


class BrowserRedirectRegression(unittest.TestCase):
    def test_browser_login_redirect_that_returns_to_same_url_is_not_accepted(self):
        page = FakePage()
        with tempfile.TemporaryDirectory() as td:
            with patch('playwright.sync_api.sync_playwright', return_value=FakePlaywright(page)):
                result = probe.browser_smoke('https://approved.example', pathlib.Path(td))
            self.assertEqual(result['state'], 'FAIL', result)
            self.assertTrue(all(c['redirect_chain_detected'] for c in result['cases']))
            self.assertEqual(page.screenshots, 0, 'redirected content must not be snapshotted')

    def test_tls_request_does_not_inherit_unapproved_process_https_proxy(self):
        captured = []
        def intercept(*handlers):
            captured.extend(handlers)
            raise RuntimeError('capture before network operation')
        with patch.dict('os.environ', {'HTTPS_PROXY':'http://127.0.0.1:8'}):
            with patch('urllib.request.build_opener', side_effect=intercept):
                with self.assertRaisesRegex(RuntimeError, 'capture before network'):
                    probe.tls_login_check('https://approved.example')
        proxies = [h for h in captured if isinstance(h, urllib.request.ProxyHandler)]
        self.assertTrue(proxies, 'must explicitly declare proxy policy')
        self.assertEqual(proxies[0].proxies, {}, 'do not silently route target through environment proxy')

if __name__ == '__main__':
    unittest.main()

class DirectTLSBoundaryRegression(unittest.TestCase):
    def test_direct_tls_helper_rejects_http_before_contact(self):
        import http.server
        import threading
        class Handler(http.server.BaseHTTPRequestHandler):
            calls = 0
            def do_GET(self):
                type(self).calls += 1
                self.send_response(200)
                self.end_headers()
                self.wfile.write(b'ok')
            def log_message(self, *args): pass
        server = http.server.ThreadingHTTPServer(('127.0.0.1', 0), Handler)
        t = threading.Thread(target=server.serve_forever, daemon=True)
        t.start()
        try:
            result = probe.tls_login_check(f'http://127.0.0.1:{server.server_port}', timeout=3)
            self.assertEqual(result['state'], 'FAIL', result)
            self.assertEqual(result['reason'], 'INVALID_ORIGIN', result)
            self.assertEqual(Handler.calls, 0, 'insecure request must not be attempted')
        finally:
            server.shutdown(); server.server_close(); t.join(3)

class AcceptanceRunnerBoundaries(unittest.TestCase):
    def test_browser_refuses_direct_http_even_before_starting_chromium(self):
        with tempfile.TemporaryDirectory() as td:
            result = probe.browser_smoke('http://example.invalid', pathlib.Path(td))
            self.assertEqual(result['state'], 'FAIL')
            self.assertEqual(result['reason'], 'INVALID_ORIGIN')

    def test_normal_direct_https_login_can_still_pass_synthetic_browser_contract(self):
        page = FakePage()
        direct_request = type('DirectRequest', (), {'redirected_from': None})()
        direct_response = type('DirectResponse', (), {'request': direct_request, 'status': 200})()
        with tempfile.TemporaryDirectory() as td:
            with patch.object(page, 'goto', return_value=direct_response), \
                 patch('playwright.sync_api.sync_playwright', return_value=FakePlaywright(page)):
                result = probe.browser_smoke('https://approved.example', pathlib.Path(td))
            self.assertEqual(result['state'], 'PASS', result)
            self.assertEqual(page.screenshots, 4)

    def test_broken_symlink_to_installed_env_blocks_native_unit(self):
        from unittest.mock import MagicMock
        import os
        with tempfile.TemporaryDirectory() as td:
            repo = pathlib.Path(td)
            required = ['artisan', 'bootstrap/app.php', 'composer.json', 'composer.lock',
                        'scripts/source-guard.php', 'scripts/refresh-critical-source-manifest.php',
                        'tests/Unit/HistoricalKeyExposureAuditTest.php', 'vendor/autoload.php',
                        'vendor/bin/phpunit']
            for rel in required:
                f = repo / rel
                f.parent.mkdir(parents=True, exist_ok=True)
                f.write_text('<?php // fixture only\n')
            (repo / '.env').symlink_to(repo / 'nonexistent-runtime-key.env')
            fake = MagicMock(returncode=0)
            with patch.dict(os.environ, {'NEXORA_DISPOSABLE_TEST_CHECKOUT': '1'}), \
                 patch.object(probe.subprocess, 'run', return_value=fake) as executed:
                outcome = probe.probe_repo(repo, allow_native_unit=True)
            self.assertEqual(outcome['native_phpunit'], 'BLOCKED_EXISTING_RUNTIME_ENVIRONMENT', outcome)
            self.assertEqual(executed.call_count, 2, 'only Source Guard and read-only manifest check allowed')

class NativeRuntimeStateGuards(unittest.TestCase):
    def test_active_environment_marker_or_cached_config_blocks_disposable_phpunit(self):
        import os
        from unittest.mock import MagicMock
        with tempfile.TemporaryDirectory() as td:
            root = pathlib.Path(td)
            for rel in ['artisan', 'bootstrap/app.php', 'composer.json', 'composer.lock',
                        'scripts/source-guard.php', 'scripts/refresh-critical-source-manifest.php',
                        'tests/Unit/HistoricalKeyExposureAuditTest.php', 'vendor/autoload.php',
                        'vendor/bin/phpunit']:
                p = root / rel
                p.parent.mkdir(parents=True, exist_ok=True)
                p.write_text('fixture only\n')
            for marker in ['storage/app/nexora/environment/active', 'bootstrap/cache/config.php']:
                p = root / marker
                p.parent.mkdir(parents=True, exist_ok=True)
                p.write_text('fixture\n')
                with patch.dict(os.environ, {'NEXORA_DISPOSABLE_TEST_CHECKOUT':'1'}), \
                     patch.object(probe.subprocess, 'run', return_value=MagicMock(returncode=0)) as calls:
                    result = probe.probe_repo(root, allow_native_unit=True)
                self.assertEqual(result['native_phpunit'], 'BLOCKED_EXISTING_RUNTIME_ENVIRONMENT')
                self.assertEqual(calls.call_count, 2)
                p.unlink()
