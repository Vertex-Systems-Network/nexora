"""Fail-closed evidence harness contracts for untrusted source and runtime symlinks."""
import os
import pathlib
import tempfile
import unittest
from unittest.mock import patch
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1] / 'tools'))
import phase1_acceptance_probe as probe

REQUIRED = [
    'artisan', 'bootstrap/app.php', 'composer.json', 'composer.lock',
    'scripts/source-guard.php', 'scripts/refresh-critical-source-manifest.php',
    'tests/Unit/HistoricalKeyExposureAuditTest.php',
    'vendor/autoload.php', 'vendor/bin/phpunit',
]


class FakeCompleted:
    def __init__(self, args):
        self.returncode = 0
        self.stdout = '[PHP Modules]\nCore\ndom\nmbstring\nzip\npdo_sqlite\n' if '-m' in args else ''


def fake_run(args, **_kwargs):
    return FakeCompleted(args)


def fixture(root: pathlib.Path):
    for name in probe.REQUIRED_SOURCE_ROOTS:
        (root / name).mkdir(parents=True, exist_ok=True)
    for path in REQUIRED:
        dst = root / path
        dst.parent.mkdir(parents=True, exist_ok=True)
        dst.write_text('fixture\n')


class RepoContainment(unittest.TestCase):
    def run_probe(self, repo):
        with patch.object(probe.subprocess, 'run', side_effect=fake_run) as called, \
             patch.object(probe.shutil, 'which', side_effect=lambda cmd: '/usr/bin/php' if cmd=='php' else None), \
             patch.dict(os.environ, {'NEXORA_DISPOSABLE_TEST_CHECKOUT': '1'}):
            result = probe.probe_repo(repo, allow_native_unit=True)
        return result, [c.args[0] for c in called.call_args_list]

    def test_source_guard_symlink_escape_is_never_executed(self):
        with tempfile.TemporaryDirectory() as d:
            base=pathlib.Path(d); repo=base/'repo'; repo.mkdir(); fixture(repo)
            (repo/'scripts/source-guard.php').unlink()
            (repo/'scripts/source-guard.php').symlink_to(base/'outside.php')
            (base/'outside.php').write_text('<?php echo "UNSAFE";')
            result,calls=self.run_probe(repo)
            self.assertEqual(result['source_guard'],'NOT_RUN',result)
            self.assertIn('scripts/source-guard.php',result['unsafe_source_paths'])
            self.assertEqual(calls,[['/usr/bin/php','-m']])

    def test_manifest_script_symlink_directory_escape_is_never_executed(self):
        with tempfile.TemporaryDirectory() as d:
            base=pathlib.Path(d); repo=base/'repo'; repo.mkdir(); fixture(repo)
            outside=base/'outside_scripts'; outside.mkdir()
            for p in ['source-guard.php','refresh-critical-source-manifest.php']:
                (outside/p).write_text('unsafe')
            for p in (repo/'scripts').iterdir(): p.unlink()
            (repo/'scripts').rmdir(); (repo/'scripts').symlink_to(outside,target_is_directory=True)
            result,calls=self.run_probe(repo)
            self.assertEqual(result['manifest_check'],'NOT_RUN',result)
            self.assertIn('scripts/refresh-critical-source-manifest.php',result['unsafe_source_paths'])
            self.assertEqual(calls,[['/usr/bin/php','-m']])

    def test_vendor_phpunit_symlink_escape_never_executed(self):
        with tempfile.TemporaryDirectory() as d:
            base=pathlib.Path(d); repo=base/'repo'; repo.mkdir(); fixture(repo)
            (repo/'vendor/bin/phpunit').unlink()
            (base/'foreign_phpunit').write_text('unsafe')
            (repo/'vendor/bin/phpunit').symlink_to(base/'foreign_phpunit')
            result,calls=self.run_probe(repo)
            self.assertNotEqual(result['native_phpunit'],'PASS',result)
            self.assertIn('vendor/bin/phpunit',result['unsafe_source_paths'])
            self.assertNotIn(['/usr/bin/php','vendor/bin/phpunit','tests/Unit/HistoricalKeyExposureAuditTest.php'],calls)

    def test_broken_symlink_source_fails_closed(self):
        with tempfile.TemporaryDirectory() as d:
            repo=pathlib.Path(d); fixture(repo)
            (repo/'scripts/source-guard.php').unlink()
            (repo/'scripts/source-guard.php').symlink_to(repo/'nonexistent-script.php')
            result,calls=self.run_probe(repo)
            self.assertEqual(result['source_guard'],'NOT_RUN',result)
            self.assertEqual(calls,[['/usr/bin/php','-m']])

    def test_internal_symlink_is_allowed_in_disposable_fixture(self):
        with tempfile.TemporaryDirectory() as d:
            repo=pathlib.Path(d); fixture(repo)
            (repo/'scripts/source-guard.php').unlink()
            (repo/'scripts/safe-guard.php').write_text('test')
            (repo/'scripts/source-guard.php').symlink_to(repo/'scripts/safe-guard.php')
            result,calls=self.run_probe(repo)
            self.assertEqual(result['source_guard'],'PASS',result)
            self.assertEqual(result['native_phpunit'],'PASS',result)
            self.assertNotIn('unsafe_source_paths',result)

    def test_sparse_scaffold_does_not_claim_full_source_checkout(self):
        with tempfile.TemporaryDirectory() as d:
            root=pathlib.Path(d); fixture(root)
            for name in ['app', 'config', 'database', 'resources', 'routes', 'themes', 'public']:
                (root / name).rmdir()
            result,calls=self.run_probe(root)
            self.assertFalse(result['repository_full_checkout'],result)
            self.assertIn('app',result['missing_source_roots'])
            self.assertIn('themes',result['missing_source_roots'])
            self.assertEqual(result['source_guard'],'NOT_RUN')
            self.assertEqual(result['native_phpunit'],'NOT_RUN')
            self.assertEqual(calls,[['/usr/bin/php','-m']])

    def test_external_symlinked_source_root_never_allows_native_scripts(self):
        with tempfile.TemporaryDirectory() as d:
            base=pathlib.Path(d); root=base/'repo'; root.mkdir(); fixture(root)
            (root/'app').rmdir()
            outside=base/'foreign-app'; outside.mkdir()
            (root/'app').symlink_to(outside,target_is_directory=True)
            result,calls=self.run_probe(root)
            self.assertFalse(result['repository_full_checkout'],result)
            self.assertIn('app',result['missing_source_roots'])
            self.assertEqual(result['source_guard'],'NOT_RUN')
            self.assertEqual(calls,[['/usr/bin/php','-m']])

    def test_full_contained_layout_allows_source_only_preflight(self):
        with tempfile.TemporaryDirectory() as d:
            root=pathlib.Path(d); fixture(root)
            result,calls=self.run_probe(root)
            self.assertTrue(result['repository_full_checkout'])
            self.assertEqual(result['missing_source_roots'],[])
            self.assertEqual(result['source_guard'],'PASS')
            self.assertEqual(result['manifest_check'],'PASS')
            self.assertEqual(result['native_phpunit'],'PASS')
            self.assertEqual(len(calls),4)

    def test_broken_symlinked_runtime_directory_blocks_native(self):
        with tempfile.TemporaryDirectory() as d:
            repo=pathlib.Path(d); fixture(repo)
            (repo/'storage').symlink_to(repo/'absent-storage',target_is_directory=True)
            result,calls=self.run_probe(repo)
            self.assertEqual(result['native_phpunit'],'BLOCKED_EXISTING_RUNTIME_ENVIRONMENT',result)
            self.assertNotIn(['/usr/bin/php','vendor/bin/phpunit','tests/Unit/HistoricalKeyExposureAuditTest.php'],calls)

if __name__ == '__main__': unittest.main()
