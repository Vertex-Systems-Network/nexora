"""A login form must be interactable, not merely present/painted beneath an overlay."""
import pathlib
import shutil
import sys
import unittest

ROOT = pathlib.Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'tools'))
import phase1_acceptance_probe as probe


class BrowserLoginOcclusionTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        binary = shutil.which('chromium') or shutil.which('google-chrome')
        if not binary:
            raise unittest.SkipTest('No Chromium available')
        from playwright.sync_api import sync_playwright
        cls.playwright = sync_playwright().start()
        cls.browser = cls.playwright.chromium.launch(
            headless=True, executable_path=binary, args=['--no-sandbox'],
            env=probe.isolated_process_env())

    @classmethod
    def tearDownClass(cls):
        cls.browser.close()
        cls.playwright.stop()

    def render(self, overlay=''):
        page = self.browser.new_page(viewport={'width': 390, 'height': 760})
        page.set_content('''<html><body>
            <form style="width:320px;padding:15px">
                <input type="email" name="email" style="width:280px;height:34px;display:block"/>
                <input type="password" name="password" style="width:280px;height:34px;display:block"/>
                <button type="submit" style="width:120px;height:35px">Sign in</button>
            </form>''' + overlay + '</body></html>')
        try:
            return probe.inspect_login_ui(page)
        finally:
            page.close()

    def test_unobscured_login_is_accepted(self):
        r = self.render()
        self.assertTrue(r['password_visible'])
        self.assertTrue(r['identity_visible'])
        self.assertTrue(r['submit_visible'])

    def test_opaque_fullscreen_overlay_blocks_login_controls(self):
        r = self.render('<div style="position:fixed;inset:0;background:white;z-index:9999"></div>')
        self.assertFalse(r['password_visible'])
        self.assertFalse(r['identity_visible'])
        self.assertFalse(r['submit_visible'])

    def test_aria_hidden_login_form_is_not_counted(self):
        r = self.render('<style>form { } </style><script>document.querySelector("form").setAttribute("aria-hidden","true")</script>')
        self.assertFalse(r['password_visible'])
        self.assertFalse(r['identity_visible'])
        self.assertFalse(r['submit_visible'])

    def test_pointer_events_none_overlay_does_not_obscure(self):
        r = self.render('<div style="position:fixed;inset:0;background:#eee;opacity:.5;pointer-events:none;z-index:9999"></div>')
        self.assertTrue(r['password_visible'])
        self.assertTrue(r['identity_visible'])
        self.assertTrue(r['submit_visible'])


if __name__ == '__main__':
    unittest.main()
