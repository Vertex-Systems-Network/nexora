"""Fail-closed browser QA: WebSocket connections are not regular HTTP routes."""
from __future__ import annotations

import pathlib
import shutil
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / "tools"))
import phase1_acceptance_probe as probe


class WebSocketBoundary(unittest.TestCase):
    def test_websocket_route_never_connects_to_upstream(self):
        class Socket:
            def __init__(self): self.connected = False
            def connect(self, *args, **kwargs):
                self.connected = True
                raise AssertionError('must never connect upstream')
        socket = Socket()
        probe.block_browser_websocket(socket)
        self.assertFalse(socket.connected)

    def test_websocket_routes_installed_before_page_creation_for_each_viewport(self):
        from test_browser_redirect_policy import FakePage, FakePlaywright, FakeContext
        page = FakePage()
        request = type("DirectRequest", (), {"redirected_from": None})()
        response = type("Response", (), {"request": request, "status": 200})()
        calls = []
        original_new_page = FakeContext.new_page
        def spy_new_page(context):
            calls.append(("page", getattr(context, "websocket_route", None)))
            return original_new_page(context)
        def fake_websocket_route(context, pattern, handler):
            context.websocket_route = (pattern, handler)
        with tempfile.TemporaryDirectory() as td:
            with patch.object(FakeContext, "new_page", spy_new_page), \
                 patch.object(FakeContext, "route_web_socket", fake_websocket_route, create=True), \
                 patch.object(page, "goto", return_value=response), \
                 patch("playwright.sync_api.sync_playwright", return_value=FakePlaywright(page)):
                result = probe.browser_smoke("https://approved.example", pathlib.Path(td))
        self.assertEqual(result["state"], "PASS", result)
        self.assertEqual(len(calls), 4)
        for action, route in calls:
            self.assertIsNotNone(route, "WebSocket guard must be registered before page exists")
            self.assertEqual(route[0], "**/*")
            self.assertEqual(route[1], probe.block_browser_websocket)

    def test_real_chromium_denies_websocket_and_worker_constructors(self):
        """Actual Chromium rejects both APIs before network access."""
        exe = shutil.which("chromium") or shutil.which("google-chrome")
        if not exe:
            self.skipTest("Chromium unavailable")
        from playwright.sync_api import sync_playwright
        routed = []
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, executable_path=exe,
                                        args=["--no-sandbox"],
                                        env=probe.isolated_process_env())
            try:
                ctx = browser.new_context(service_workers="block")
                ctx.route_web_socket("**/*", lambda ws: routed.append(ws.url))
                ctx.add_init_script(probe.NO_BACKGROUND_CONNECTIONS_SCRIPT)
                page = ctx.new_page()
                page.goto('about:blank')
                outcomes = page.evaluate("""() => ['WebSocket', 'Worker', 'SharedWorker'].map(name => {
                  try { new globalThis[name]('wss://unapproved.example/stream'); return 'OPEN'; }
                  catch (e) { return e.message.includes('read-only acceptance QA') ? 'BLOCKED' : 'UNEXPECTED'; }
                })""")
                self.assertEqual(outcomes, ["BLOCKED", "BLOCKED", "BLOCKED"])
                self.assertEqual(routed, [], "page must not attempt socket handshake")
                ctx.close()
            finally:
                browser.close()

    def test_real_chromium_websocket_interception_never_opens_upstream(self):
        """A page trying to bypass constructor stubs still reaches a virtual socket only."""
        exe = shutil.which("chromium") or shutil.which("google-chrome")
        if not exe:
            self.skipTest("Chromium unavailable")
        from playwright.sync_api import sync_playwright
        routed = []
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, executable_path=exe,
                                        args=["--no-sandbox"], env=probe.isolated_process_env())
            try:
                ctx = browser.new_context(service_workers="block")
                def intercept(route):
                    routed.append(route.url)
                    probe.block_browser_websocket(route)
                ctx.route_web_socket("**/*", intercept)
                page = ctx.new_page()
                page.goto('about:blank')
                page.evaluate("""() => {
                    window._socket = new WebSocket('wss://unapproved.example/stream');
                }""")
                page.wait_for_timeout(250)
                self.assertEqual(routed, ["wss://unapproved.example/stream"])
                ctx.close()
            finally:
                browser.close()


if __name__ == "__main__":
    unittest.main()
