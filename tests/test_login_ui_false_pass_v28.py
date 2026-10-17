"""Real Chromium: prevent unrelated, inert, or invisible elements from passing login QA."""
import pathlib
import shutil
import sys
import unittest

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe


class BrowserLoginSemanticBoundary(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        from playwright.sync_api import sync_playwright
        cls.binary = shutil.which('chromium') or shutil.which('google-chrome')
        if not cls.binary:
            raise unittest.SkipTest('Chromium missing')
        cls.context = sync_playwright().start()
        cls.browser = cls.context.chromium.launch(headless=True,
                                                  executable_path=cls.binary,
                                                  args=['--no-sandbox'],
                                                  env=probe.isolated_process_env())

    @classmethod
    def tearDownClass(cls):
        cls.browser.close()
        cls.context.stop()

    def check_html(self, html):
        page = self.browser.new_page(viewport={'width': 390, 'height': 900})
        try:
            page.set_content(html)
            return probe.inspect_login_ui(page)
        finally:
            page.close()

    def test_disabled_fieldset_submit_is_not_clickable(self):
        ui = self.check_html('<form><input type="email"><input type="password">'
                             '<fieldset disabled><button type="submit">Login</button></fieldset></form>')
        self.assertFalse(ui['submit_visible'], ui)

    def test_disabled_fieldset_identity_is_not_usable(self):
        ui = self.check_html('<form><fieldset disabled><input type="email"></fieldset>'
                             '<input type="password"><button type="submit">Login</button></form>')
        self.assertFalse(ui['identity_visible'], ui)

    def test_hidden_parent_opacity_does_not_count_as_visible(self):
        ui = self.check_html('<form><input type="email"><div style="opacity:0">'
                             '<input type="password"></div><button type="submit">Login</button></form>')
        self.assertFalse(ui['password_visible'], ui)

    def test_unrelated_standalone_controls_are_not_login_form(self):
        ui = self.check_html('<div><input type="email"></div>'
                             '<input type="password"><aside><button type="submit">Other</button></aside>')
        self.assertFalse(ui['password_visible'] and ui['identity_visible'] and ui['submit_visible'], ui)

    def test_valid_login_form_still_passes(self):
        ui = self.check_html('<form><input type="email"><input type="password">'
                             '<button type="submit">Login</button></form>')
        self.assertEqual(ui, {'password_visible': True, 'identity_visible': True,
                              'submit_visible': True, 'horizontal_overflow': False})

if __name__ == '__main__':
    unittest.main()
