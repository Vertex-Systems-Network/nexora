"""Browser acceptance must prevent unexpected requests, not merely fail afterwards."""
import pathlib
import shutil
import sys
import unittest
from unittest.mock import MagicMock
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


class Request:
    def __init__(self, url, navigation=False, redirected=False, method="GET"):
        self.url = url
        self.method = method
        self.navigation = navigation
        self.redirected_from = object() if redirected else None
    def is_navigation_request(self):
        return self.navigation


class Route:
    def __init__(self, req):
        self.request = req
        self.continued = 0
        self.aborted = 0
    def continue_(self):
        self.continued += 1
    def abort(self, error_code='failed'):
        self.aborted += 1


class BrowserOutboundBoundary(unittest.TestCase):
    origin = 'https://approved.example'

    def check(self, req, expected):
        route = Route(req)
        probe.route_approved_browser_request(route, self.origin)
        self.assertEqual(route.continued, int(expected), req.url)
        self.assertEqual(route.aborted, int(not expected), req.url)

    def test_direct_login_and_same_origin_https_resources_allowed(self):
        self.check(Request(self.origin + '/login', navigation=True), True)
        self.check(Request(self.origin + '/css/site.css'), True)
        self.check(Request(self.origin + '/js/app.js?v=1'), True)

    def test_redirected_navigation_is_aborted_before_next_network_request(self):
        self.check(Request(self.origin + '/login', navigation=True, redirected=True), False)
        self.check(Request('https://unapproved.example/collect', navigation=True, redirected=True), False)

    def test_other_navigation_paths_and_cross_origin_assets_blocked(self):
        self.check(Request(self.origin + '/admin', navigation=True), False)
        self.check(Request(self.origin + '/login?next=secret', navigation=True), False)
        self.check(Request('https://tracker.example/collect?token=x'), False)
        self.check(Request('https://approved.example.evil.org/login', navigation=True), False)
        self.check(Request('http://approved.example/resource.js'), False)

    def test_invalid_scheme_or_credentials_not_allowed(self):
        self.check(Request('file:///tmp/local.php', navigation=True), False)
        self.check(Request('https://user:password@approved.example/login', navigation=True), False)

    def test_real_chromium_resource_requests_are_guarded_without_network(self):
        """Actual Chromium issues requests; a virtual adapter fulfils allowed ones.

        No external URL is contacted in this test. Redirect chains are separately
        exercised by the direct route-policy tests above because Chromium's
        navigation to the disposable host is blocked by this environment.
        """
        from playwright.sync_api import sync_playwright
        exe = shutil.which('chromium') or shutil.which('google-chrome')
        if not exe:
            self.skipTest('Chromium not available')
        outcomes = []
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, executable_path=exe, args=['--no-sandbox'])
            try:
                ctx = browser.new_context(service_workers='block')
                def handler(route):
                    req = route.request
                    class VirtualRoute:
                        request = req
                        def continue_(self):
                            outcomes.append((req.url, 'allowed'))
                            route.fulfill(status=200, body=b'GIF89a', content_type='image/gif')
                        def abort(self, code='failed'):
                            outcomes.append((req.url, 'blocked'))
                            route.abort(code)
                    probe.route_approved_browser_request(VirtualRoute(), self.origin)
                ctx.route('**/*', handler)
                page = ctx.new_page()
                page.set_content('<img src="https://approved.example/img.gif">'
                                 '<img src="https://unapproved.example/tracker.gif">'
                                 '<img src="http://approved.example/insecure.gif">')
                page.wait_for_timeout(250)
                self.assertIn((self.origin + '/img.gif', 'allowed'), outcomes)
                self.assertIn(('https://unapproved.example/tracker.gif', 'blocked'), outcomes)
                self.assertIn(('http://approved.example/insecure.gif', 'blocked'), outcomes)
                ctx.close()
            finally:
                browser.close()

if __name__ == '__main__':
    unittest.main()

class BrowserProcessIsolation(unittest.TestCase):
    def test_playwright_browser_process_does_not_inherit_service_secrets(self):
        import tempfile
        from unittest.mock import patch
        from test_browser_redirect_policy import FakePage, FakePlaywright
        fake = FakePlaywright(FakePage())
        from unittest.mock import MagicMock
        spy = MagicMock(wraps=fake.launch)
        with tempfile.TemporaryDirectory() as td:
            with patch.dict('os.environ', {
                'DB_PASSWORD':'sensitive-database-value',
                'AWS_SECRET_ACCESS_KEY':'sensitive-cloud-value',
                'HTTPS_PROXY':'http://private-proxy.invalid:8',
                'NEXORA_ACTIVATION_TOKEN':'sensitive-activation-value',
            }):
                with patch.object(fake, 'launch', spy), \
                     patch('playwright.sync_api.sync_playwright', return_value=fake):
                    probe.browser_smoke('https://approved.example', pathlib.Path(td))
        self.assertTrue(spy.called)
        passed = spy.call_args.kwargs.get('env', {})
        self.assertTrue(passed, 'Explicit allowlisted browser environment required')
        for key in ('DB_PASSWORD','AWS_SECRET_ACCESS_KEY','HTTPS_PROXY','NEXORA_ACTIVATION_TOKEN'):
            self.assertNotIn(key, passed)
