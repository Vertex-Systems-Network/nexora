"""Regression tests: HTTP 200 must not be mistaken for a rendered login UI."""
import pathlib
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


class DirectResponse:
    status = 200
    request = type('Request', (), {'redirected_from': None})()


class LoginPage:
    def __init__(self, contract):
        self.contract = contract
        self.url = 'https://approved.example/login'
        self.screenshots = []
        self.evaluations = 0
    def goto(self, *a, **kw):
        return DirectResponse()
    def evaluate(self, *a, **kw):
        self.evaluations += 1
        return self.contract
    def locator(self, selector):
        return 'mask:' + selector
    def screenshot(self, **kwargs):
        self.screenshots.append(kwargs)
        return b'PNG_MOCK_MASKED'


class Context:
    def __init__(self, page): self.page = page
    def route(self, pattern, handler):
        self.route_pattern = pattern
        self.route_handler = handler
    def add_init_script(self, script):
        self.init_script = script
    def route_web_socket(self, pattern, handler):
        self.websocket_route = (pattern, handler)
    def new_page(self): return self.page
    def close(self): pass


class Browser:
    def __init__(self, page): self.page = page
    def new_context(self, **kw): return Context(self.page)
    def close(self): pass


class Playwright:
    def __init__(self, page): self.page = page
    def launch(self, **kw): return Browser(self.page)
    @property
    def chromium(self): return self
    def __enter__(self): return self
    def __exit__(self, *_): pass


VALID = {'password_visible': True, 'identity_visible': True,
         'submit_visible': True, 'horizontal_overflow': False}


class ActualLoginUIContract(unittest.TestCase):
    def run_browser(self, contract):
        page = LoginPage(contract)
        with tempfile.TemporaryDirectory() as td:
            with patch('playwright.sync_api.sync_playwright', return_value=Playwright(page)):
                outcome = probe.browser_smoke('https://approved.example', pathlib.Path(td))
        return outcome, page

    def test_http_200_without_visible_login_ui_fails_closed(self):
        result, page = self.run_browser(dict.fromkeys(VALID, False))
        self.assertEqual(result['state'], 'FAIL', result)
        self.assertEqual(len(page.screenshots), 0)
        self.assertEqual(page.evaluations, 4)

    def test_missing_password_field_rejected(self):
        res, page = self.run_browser({**VALID, 'password_visible': False})
        self.assertEqual(res['state'], 'FAIL', res)
        self.assertEqual(len(page.screenshots), 0)

    def test_missing_identity_field_rejected(self):
        res, page = self.run_browser({**VALID, 'identity_visible': False})
        self.assertEqual(res['state'], 'FAIL', res)
        self.assertEqual(len(page.screenshots), 0)

    def test_missing_submit_control_rejected(self):
        res, page = self.run_browser({**VALID, 'submit_visible': False})
        self.assertEqual(res['state'], 'FAIL', res)
        self.assertEqual(len(page.screenshots), 0)

    def test_horizontal_overflow_rejected(self):
        res, page = self.run_browser({**VALID, 'horizontal_overflow': True})
        self.assertEqual(res['state'], 'FAIL', res)
        self.assertEqual(len(page.screenshots), 0)

    def test_visible_login_fields_without_overflow_pass_and_mask_inputs(self):
        res, page = self.run_browser(VALID)
        self.assertEqual(res['state'], 'PASS', res)
        self.assertEqual(len(page.screenshots), 4)
        self.assertTrue(all(isinstance(kw.get('mask'), list) and kw['mask']
                            for kw in page.screenshots),
                        'Playwright expects a list of locators for screenshot masking')
        self.assertTrue(all(case.get('ui_contract') == 'PASS' for case in res['cases']))

    def test_invalid_browser_evaluation_fails_closed(self):
        res, page = self.run_browser(None)
        self.assertEqual(res['state'], 'FAIL', res)
        self.assertEqual(len(page.screenshots), 0)

    def test_contract_flags_invisible_or_disabled_controls_in_real_chromium(self):
        from playwright.sync_api import sync_playwright
        import shutil
        binary = shutil.which('chromium') or shutil.which('google-chrome')
        if not binary:
            self.skipTest('Chromium not installed')
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, executable_path=binary, args=['--no-sandbox'])
            try:
                page = browser.new_page(viewport={'width': 320, 'height': 900})
                page.set_content('<form><input type="email"><input type="password" style="display:none"><button type="submit">Go</button></form>')
                self.assertFalse(probe.inspect_login_ui(page)['password_visible'])
                page.set_content('<form><input type="email"><input type="password"><button disabled type="submit">Go</button></form>')
                self.assertFalse(probe.inspect_login_ui(page)['submit_visible'])
                page.set_content('<form style="width:1800px"><input type="email"><input type="password"><button type="submit">Go</button></form>')
                self.assertTrue(probe.inspect_login_ui(page)['horizontal_overflow'])
            finally:
                browser.close()

    def test_real_chromium_login_contract_at_four_viewports(self):
        from playwright.sync_api import sync_playwright
        import shutil
        binary = shutil.which('chromium') or shutil.which('google-chrome')
        if not binary:
            self.skipTest('Chromium not installed')
        html = '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><form><input type="email" autocomplete="username"><input type="password"><button type="submit">Sign in</button></form></body></html>'
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, executable_path=binary, args=['--no-sandbox'])
            try:
                for width in (320,390,768,1440):
                    page = browser.new_page(viewport={'width': width, 'height': 900})
                    page.set_content(html)
                    with self.subTest(viewport=width):
                        self.assertEqual(probe.inspect_login_ui(page), VALID)
                    page.close()
            finally:
                browser.close()

    def test_contract_function_runs_in_real_chromium(self):
        # This is an actual Chromium DOM/render assertion, not a mocked page.
        from playwright.sync_api import sync_playwright
        import shutil
        binary = shutil.which('chromium') or shutil.which('google-chrome')
        if not binary:
            self.skipTest('Chromium not installed')
        markup = '<!doctype html><html><body><form><label>Email<input type="email" name="email"></label><label>Password<input type="password"></label><button type="submit">Sign in</button></form></body></html>'
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, executable_path=binary, args=['--no-sandbox'])
            try:
                page = browser.new_page(viewport={'width': 390, 'height': 900})
                page.set_content(markup)
                actual = probe.inspect_login_ui(page)
                self.assertEqual(actual, VALID)
                page.set_content('<!doctype html><title>Empty login</title>')
                self.assertFalse(probe.inspect_login_ui(page)['password_visible'])
            finally:
                browser.close()


if __name__ == '__main__': unittest.main()
