<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-runtime-resume-input.php';

final class TargetRuntimeResumeInputTest extends TestCase
{
    private string $fixture;
    private string $base;
    private const RUN = '20261016-150000-a1b2c3';

    protected function setUp(): void
    {
        $this->fixture = sys_get_temp_dir().'/nexora-resume-file-'.bin2hex(random_bytes(6));
        $this->base = $this->fixture.'/storage/app/nexora/target-runtime';
        mkdir($this->base.'/'.self::RUN, 0700, true);
        file_put_contents($this->base.'/latest.json', json_encode(['run_id'=>self::RUN], JSON_THROW_ON_ERROR));
        file_put_contents($this->base.'/'.self::RUN.'/summary.json', json_encode([
            'run_id'=>self::RUN,'status'=>'blocked','steps'=>[]
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        if (!isset($this->fixture) || !is_dir($this->fixture)) return;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->fixture, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $entry) {
            $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($this->fixture);
    }

    #[Test]
    public function correctly_scoped_latest_pointer_is_read(): void
    {
        self::assertSame(self::RUN, \nexoraTargetRuntimeReadResumeJson($this->base, 'latest.json', 65536)['run_id']);
    }

    #[Test]
    public function correctly_scoped_prior_summary_is_read(): void
    {
        $summary = \nexoraTargetRuntimeReadResumeJson($this->base, self::RUN.'/summary.json', 16*1024*1024);
        self::assertSame(self::RUN, $summary['run_id']);
    }

    #[Test]
    public function path_traversal_and_unknown_files_are_rejected(): void
    {
        foreach (['../other.json', '/etc/passwd', 'latest.json/../summary.json', self::RUN.'/../../x.json', 'config.json'] as $name) {
            self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, $name, 65536));
        }
    }

    #[Test]
    public function oversized_latest_pointer_is_rejected_without_decoding(): void
    {
        file_put_contents($this->base.'/latest.json', '{}'.str_repeat(' ', 65536));
        self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, 'latest.json', 65536));
    }

    #[Test]
    public function oversized_summary_is_rejected_before_json_decode(): void
    {
        $file = fopen($this->base.'/'.self::RUN.'/summary.json','c+b');
        ftruncate($file, 16*1024*1024 + 1);
        fclose($file);
        self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, self::RUN.'/summary.json', 16*1024*1024));
    }

    #[Test]
    public function malformed_json_or_non_object_is_rejected(): void
    {
        foreach (['not json', '[]', 'null', '"scalar"'] as $value) {
            file_put_contents($this->base.'/latest.json', $value);
            self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, 'latest.json', 65536));
        }
    }

    #[Test]
    public function symlinked_latest_pointer_is_rejected(): void
    {
        if (!function_exists('symlink')) self::markTestSkipped('Symlinks unavailable.');
        unlink($this->base.'/latest.json');
        if (!@symlink($this->base.'/'.self::RUN.'/summary.json', $this->base.'/latest.json')) self::markTestSkipped('Symlinks restricted.');
        self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, 'latest.json', 65536));
    }

    #[Test]
    public function linked_run_directory_is_rejected(): void
    {
        if (!function_exists('symlink')) self::markTestSkipped('Symlinks unavailable.');
        $outside = $this->fixture.'/outside';
        rename($this->base.'/'.self::RUN, $outside);
        if (!@symlink($outside, $this->base.'/'.self::RUN)) self::markTestSkipped('Symlinks restricted.');
        self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, self::RUN.'/summary.json', 65536));
    }

    #[Test]
    public function linked_storage_parent_is_rejected(): void
    {
        if (!function_exists('symlink')) self::markTestSkipped('Symlinks unavailable.');
        rename($this->fixture.'/storage/app', $this->fixture.'/real-app');
        if (!@symlink($this->fixture.'/real-app', $this->fixture.'/storage/app')) self::markTestSkipped('Symlinks restricted.');
        self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, 'latest.json', 65536));
    }

    #[Test]
    public function missing_and_unreadable_receipts_are_rejected(): void
    {
        unlink($this->base.'/latest.json');
        self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, 'latest.json', 65536));
        self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, 'latest.json', 0));
        self::assertNull(\nexoraTargetRuntimeReadResumeJson($this->base, self::RUN.'/summary.json', 16*1024*1024+1));
    }
}
