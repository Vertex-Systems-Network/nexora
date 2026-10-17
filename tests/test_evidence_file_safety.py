"""Evidence files must be private, atomic, and resistant to symlink clobbering."""
import os
import pathlib
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = pathlib.Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'tools'))
import phase1_acceptance_probe as probe


class EvidenceFileSafetyTests(unittest.TestCase):
    def test_refuse_output_file_symlink_and_preserve_target(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            sentinel = root / 'important.txt'
            sentinel.write_text('PRESERVE ORIGINAL')
            alias = root / 'report.json'
            alias.symlink_to(sentinel)
            proc = subprocess.run([sys.executable, str(ROOT / 'tools/phase1_acceptance_probe.py'),
                                   '--out', str(alias)], capture_output=True, text=True)
            self.assertNotEqual(proc.returncode, 0)
            self.assertEqual(sentinel.read_text(), 'PRESERVE ORIGINAL')
            self.assertTrue(alias.is_symlink())

    def test_generated_evidence_report_is_private(self):
        with tempfile.TemporaryDirectory() as tmp:
            report = pathlib.Path(tmp) / 'report.json'
            proc = subprocess.run([sys.executable, str(ROOT / 'tools/phase1_acceptance_probe.py'),
                                   '--out', str(report)], capture_output=True, text=True)
            self.assertEqual(proc.returncode, 0, proc.stderr)
            self.assertEqual(report.stat().st_mode & 0o077, 0)

    def test_existing_regular_evidence_report_can_be_replaced_privately(self):
        with tempfile.TemporaryDirectory() as tmp:
            report = pathlib.Path(tmp) / 'report.json'
            report.write_text('STALE')
            report.chmod(0o644)
            proc = subprocess.run([sys.executable, str(ROOT / 'tools/phase1_acceptance_probe.py'),
                                   '--out', str(report)], capture_output=True, text=True)
            self.assertEqual(proc.returncode, 0, proc.stderr)
            self.assertTrue(report.read_text().startswith('{'))
            self.assertEqual(report.stat().st_mode & 0o077, 0)

    def test_refuse_screenshot_directory_symlink_without_browser_side_effects(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            safe = root / 'safe'
            safe.mkdir()
            screenshot_alias = root / 'screenshots'
            screenshot_alias.symlink_to(safe, target_is_directory=True)
            with patch.object(probe.shutil, 'which', return_value='/usr/bin/chromium'):
                result = probe.browser_smoke('https://approved.example', screenshot_alias)
            self.assertEqual(result['state'], 'FAIL')
            self.assertEqual(list(safe.iterdir()), [])

    def test_screenshot_write_rejects_preexisting_symlink(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            screenshots = root / 'screenshots'
            screenshots.mkdir()
            marker = root / 'marker.txt'
            marker.write_text('UNCHANGED')
            (screenshots / 'login-320.png').symlink_to(marker)
            with self.assertRaises((ValueError, OSError)):
                probe.save_masked_login_evidence(b'PNGIMAGE', screenshots, 320)
            self.assertEqual(marker.read_text(), 'UNCHANGED')

    def test_screenshot_generated_file_is_private(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            screenshots = root / 'screenshots'
            result = probe.save_masked_login_evidence(b'PNGIMAGE', screenshots, 390)
            self.assertEqual(result.read_bytes(), b'PNGIMAGE')
            self.assertEqual(result.stat().st_mode & 0o077, 0)
            self.assertEqual(screenshots.stat().st_mode & 0o077, 0)


    def test_reject_world_readable_preexisting_screenshot_directory(self):
        with tempfile.TemporaryDirectory() as tmp:
            destination = pathlib.Path(tmp) / 'screenshots'
            destination.mkdir(mode=0o755)
            destination.chmod(0o755)
            with self.assertRaises(ValueError):
                probe.save_masked_login_evidence(b'PNGIMAGE', destination, 320)
            self.assertEqual(list(destination.iterdir()), [])

    def test_reject_screenshot_symlinked_parent_and_preserve_external(self):
        with tempfile.TemporaryDirectory() as tmp:
            base = pathlib.Path(tmp)
            external = base / 'external'
            external.mkdir(mode=0o700)
            (base / 'alias').symlink_to(external, target_is_directory=True)
            with self.assertRaises(ValueError):
                probe.save_masked_login_evidence(b'PNGIMAGE', base / 'alias' / 'screenshots', 320)
            self.assertEqual(list(external.iterdir()), [])

    def test_reject_report_symlinked_parent_and_preserve_external(self):
        with tempfile.TemporaryDirectory() as tmp:
            base = pathlib.Path(tmp)
            external = base / 'external'
            external.mkdir()
            (base / 'alias').symlink_to(external, target_is_directory=True)
            with self.assertRaises(ValueError):
                probe.write_private_evidence(base / 'alias' / 'report.json', b'{"ok":true}')
            self.assertEqual(list(external.iterdir()), [])

    def test_cli_refuses_report_directory_alias(self):
        with tempfile.TemporaryDirectory() as tmp:
            base = pathlib.Path(tmp)
            external = base / 'external'
            external.mkdir()
            (base / 'alias').symlink_to(external, target_is_directory=True)
            proc = subprocess.run([sys.executable, str(ROOT / 'tools/phase1_acceptance_probe.py'),
                                   '--out', str(base / 'alias' / 'report.json')],
                                  capture_output=True, text=True)
            self.assertNotEqual(proc.returncode, 0)
            self.assertEqual(list(external.iterdir()), [])

    def test_browser_refuses_public_screenshot_directory_before_launch(self):
        with tempfile.TemporaryDirectory() as tmp:
            directory = pathlib.Path(tmp) / 'screenshots'
            directory.mkdir(mode=0o755)
            directory.chmod(0o755)
            with patch.object(probe.shutil, 'which', return_value='/usr/bin/chromium'):
                result = probe.browser_smoke('https://approved.example', directory)
            self.assertEqual(result['reason'], 'UNSAFE_SCREENSHOT_OUTPUT')
            self.assertEqual(list(directory.iterdir()), [])

    def test_browser_refuses_screenshot_parent_symlink_before_launch(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            external = root / 'external'
            external.mkdir(mode=0o700)
            (root / 'alias').symlink_to(external, target_is_directory=True)
            with patch.object(probe.shutil, 'which', return_value='/usr/bin/chromium'):
                result = probe.browser_smoke('https://approved.example', root / 'alias' / 'screenshots')
            self.assertEqual(result['reason'], 'UNSAFE_SCREENSHOT_OUTPUT')
            self.assertEqual(list(external.iterdir()), [])

    def test_existing_private_screenshot_directory_remains_supported(self):
        with tempfile.TemporaryDirectory() as tmp:
            destination = pathlib.Path(tmp) / 'screenshots'
            destination.mkdir(mode=0o700)
            destination.chmod(0o700)
            path = probe.save_masked_login_evidence(b'PNGIMAGE', destination, 320)
            self.assertEqual(path.read_bytes(), b'PNGIMAGE')

    def test_real_chromium_produces_masked_private_png_bytes(self):
        import shutil
        binary = shutil.which('chromium') or shutil.which('google-chrome')
        if not binary:
            self.skipTest('Chromium unavailable')
        from playwright.sync_api import sync_playwright
        with tempfile.TemporaryDirectory() as tmp, sync_playwright() as playwright:
            browser = playwright.chromium.launch(headless=True, executable_path=binary,
                                                 args=['--no-sandbox'],
                                                 env=probe.isolated_process_env())
            try:
                page = browser.new_page()
                page.set_content('<form><input type="email" value="private@example.org">'
                                 '<input type="password" value="NOT_FOR_IMAGE">'
                                 '<button type="submit">Sign in</button></form>')
                png = page.screenshot(mask=[page.locator('input,textarea,[contenteditable="true"]')],
                                      full_page=True)
                result = probe.save_masked_login_evidence(png, pathlib.Path(tmp) / 'screenshots', 390)
                self.assertTrue(result.read_bytes().startswith(b'\x89PNG\r\n\x1a\n'))
                self.assertEqual(result.stat().st_mode & 0o077, 0)
            finally:
                browser.close()


if __name__ == '__main__':
    unittest.main()
