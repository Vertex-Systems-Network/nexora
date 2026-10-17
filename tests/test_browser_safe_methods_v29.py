"""Read-only browser acceptance must deny write HTTP methods preflight."""
import pathlib
import shutil
import sys
import unittest

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


class Request:
    def __init__(self, method, url='https://approved.example/api/action', navigation=False):
        self.url = url
        self.method = method
        self.redirected_from = None
        self.navigation = navigation

    def is_navigation_request(self):
        return self.navigation


class Route:
    def __init__(self, request):
        self.request = request
        self.allowed = False
        self.blocked = False

    def continue_(self):
        self.allowed = True

    def abort(self, reason='failed'):
        self.blocked = True


class ReadOnlyMethods(unittest.TestCase):
    def case(self, method, should_allow=False, navigation=False):
        url = 'https://approved.example/login' if navigation else 'https://approved.example/api/action'
        route = Route(Request(method, url=url, navigation=navigation))
        probe.route_approved_browser_request(route, 'https://approved.example')
        self.assertEqual(route.allowed, should_allow, method)
        self.assertEqual(route.blocked, not should_allow, method)

    def test_write_methods_are_denied_even_to_approved_origin(self):
        for method in ('POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE', 'CONNECT'):
            with self.subTest(method=method):
                self.case(method)

    def test_login_navigation_rejects_form_post(self):
        self.case('POST', navigation=True)

    def test_get_assets_and_login_navigation_still_work(self):
        self.case('GET', should_allow=True)
        self.case('GET', should_allow=True, navigation=True)

    def test_malformed_or_unavailable_method_fails_closed(self):
        for method in (None, '', 'get', 'POST\n', ' GET'):
            with self.subTest(method=method):
                self.case(method)

    def test_chromium_post_blocked_before_any_external_network(self):
        from playwright.sync_api import sync_playwright
        exe = shutil.which('chromium') or shutil.which('google-chrome')
        if not exe:
            self.skipTest('Chromium missing')
        outcomes = []
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, executable_path=exe, args=['--no-sandbox'])
            try:
                context = browser.new_context(service_workers='block')
                def callback(route):
                    req = route.request
                    class VirtualRoute:
                        request = req
                        def continue_(self):
                            outcomes.append((req.method, 'ALLOWED'))
                            route.fulfill(status=204, body='')
                        def abort(self, code='failed'):
                            outcomes.append((req.method, 'BLOCKED'))
                            route.abort(code)
                    probe.route_approved_browser_request(VirtualRoute(), 'https://approved.example')
                context.route('**/*', callback)
                page = context.new_page()
                page.set_content('<html><body></body></html>')
                # No real external server is touched: the route fulfils/aborts locally.
                page.evaluate('''async () => {
                    await Promise.allSettled([
                        fetch('https://approved.example/api/action', {method:'POST', mode:'no-cors'}),
                        fetch('https://approved.example/api/action', {method:'GET', mode:'no-cors'})
                    ])
                }''')
                self.assertIn(('POST', 'BLOCKED'), outcomes)
                self.assertIn(('GET', 'ALLOWED'), outcomes)
                context.close()
            finally:
                browser.close()


if __name__ == '__main__':
    unittest.main()
