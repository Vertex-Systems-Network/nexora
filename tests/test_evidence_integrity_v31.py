"""Security regressions for v31 offline, opt-in acceptance evidence integrity."""
from __future__ import annotations

import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

TOOL = Path(__file__).resolve().parents[1] / 'tools/phase1_evidence_integrity.py'
spec = importlib.util.spec_from_file_location('phase1_evidence_integrity', TOOL)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class EvidenceManifestIntegrityTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.base = Path(self.tmp.name)
        self.root = self.base / 'evidence'
        self.root.mkdir()
        self.manifest = self.base / 'manifest.json'
        (self.root / 'qa').mkdir()
        (self.root / 'qa' / 'smoke.json').write_text('{"state":"BLOCKED"}', encoding='utf8')
        (self.root / 'qa' / 'login.png').write_bytes(b'\x89PNG\r\nprivate-test')

    def seed(self):
        return module.seal(self.root, ['qa/smoke.json', 'qa/login.png'], self.manifest)

    def test_manifest_matches_evidence_and_anchor(self):
        result = self.seed()
        self.assertEqual(result['state'], 'SEALED')
        self.assertEqual(module.verify(self.root, self.manifest, result['manifest_sha256'])['state'], 'PASS')
        self.assertEqual(self.manifest.stat().st_mode & 0o777, 0o600)
        obj = json.loads(self.manifest.read_text())
        self.assertEqual([x['path'] for x in obj['files']], ['qa/login.png', 'qa/smoke.json'])
        self.assertNotIn('BLOCKED', self.manifest.read_text())

    def test_modified_evidence_is_detected(self):
        result = self.seed()
        (self.root / 'qa' / 'smoke.json').write_text('{"state":"PASS"}', encoding='utf8')
        with self.assertRaisesRegex(module.EvidenceError, 'integrity FAILED'):
            module.verify(self.root, self.manifest, result['manifest_sha256'])

    def test_modified_manifest_detected_by_separate_anchor(self):
        result = self.seed()
        obj = json.loads(self.manifest.read_text())
        obj['scope'] = 'independent_security_review_approved'
        self.manifest.write_text(json.dumps(obj))
        with self.assertRaisesRegex(module.EvidenceError, 'independently retained anchor'):
            module.verify(self.root, self.manifest, result['manifest_sha256'])

    def test_missing_evidence_is_detected(self):
        result = self.seed()
        (self.root / 'qa' / 'login.png').unlink()
        with self.assertRaisesRegex(module.EvidenceError, 'integrity FAILED'):
            module.verify(self.root, self.manifest, result['manifest_sha256'])

    def test_manifest_cannot_be_written_into_evidence_root(self):
        with self.assertRaises(module.EvidenceError):
            module.seal(self.root, ['qa/smoke.json'], self.root / 'qa' / 'manifest.json')

    def test_relative_traversal_rejected(self):
        with self.assertRaises(module.EvidenceError):
            module.seal(self.root, ['../stolen.json'], self.manifest)
        with self.assertRaises(module.EvidenceError):
            module.seal(self.root, ['/etc/hosts'], self.manifest)
        with self.assertRaises(module.EvidenceError):
            module.seal(self.root, ['qa//smoke.json'], self.manifest)

    def test_symlinked_evidence_file_rejected(self):
        outside = self.base / 'outside.txt'
        outside.write_text('do-not-read')
        (self.root / 'qa' / 'other.txt').symlink_to(outside)
        with self.assertRaises(module.EvidenceError):
            module.seal(self.root, ['qa/other.txt'], self.manifest)

    def test_symlinked_parent_dir_rejected(self):
        outside = self.base / 'outside'
        outside.mkdir()
        (outside / 'other.json').write_text('private')
        (self.root / 'redirect').symlink_to(outside, target_is_directory=True)
        with self.assertRaises(module.EvidenceError):
            module.seal(self.root, ['redirect/other.json'], self.manifest)

    def test_existing_manifest_symlink_rejected_without_touching_victim(self):
        victim = self.base / 'victim.txt'
        victim.write_text('must-survive')
        self.manifest.symlink_to(victim)
        with self.assertRaises(module.EvidenceError):
            self.seed()
        self.assertEqual(victim.read_text(), 'must-survive')

    def test_duplicate_file_selection_rejected(self):
        with self.assertRaises(module.EvidenceError):
            module.seal(self.root, ['qa/smoke.json', 'qa/smoke.json'], self.manifest)

    def test_environment_secret_paths_rejected(self):
        for name in ['.env', '.env.production', 'keys/production.key', 'config/credentials.json']:
            with self.subTest(name=name):
                with self.assertRaises(module.EvidenceError):
                    module.seal(self.root, [name], self.manifest)

    @unittest.skipUnless(hasattr(os, 'mkfifo'), 'POSIX named pipes only')
    def test_named_pipe_cannot_block_manifest_job(self):
        os.mkfifo(self.root / 'qa' / 'pipe.txt')
        with self.assertRaises(module.EvidenceError):
            module.seal(self.root, ['qa/pipe.txt'], self.manifest)

    def test_large_evidence_refused_before_read(self):
        huge = self.root / 'qa' / 'huge.json'
        with huge.open('wb') as stream:
            stream.truncate(module.MAX_EVIDENCE_BYTES + 1)
        with self.assertRaisesRegex(module.EvidenceError, 'safe manifest limit'):
            module.seal(self.root, ['qa/huge.json'], self.manifest)

    def test_malformed_anchor_fails_closed(self):
        self.seed()
        with self.assertRaises(module.EvidenceError):
            module.verify(self.root, self.manifest, 'bad')

    def test_command_line_nonzero_on_tamper(self):
        seal = subprocess.run([sys.executable, str(TOOL), 'seal', '--root', str(self.root),
                               '--manifest', str(self.manifest), '--file', 'qa/smoke.json'],
                              capture_output=True, text=True)
        self.assertEqual(seal.returncode, 0, seal.stderr)
        digest = json.loads(seal.stdout)['manifest_sha256']
        (self.root / 'qa' / 'smoke.json').write_text('{"tampered":true}')
        check = subprocess.run([sys.executable, str(TOOL), 'verify', '--root', str(self.root),
                                '--manifest', str(self.manifest), '--expected-sha256', digest],
                               capture_output=True, text=True)
        self.assertEqual(check.returncode, 2)
        self.assertEqual(json.loads(check.stdout)['state'], 'FAIL')
        self.assertNotIn('tampered', check.stdout)


if __name__ == '__main__':
    unittest.main()
