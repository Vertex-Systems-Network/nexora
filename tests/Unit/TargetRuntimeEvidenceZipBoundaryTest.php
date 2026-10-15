<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use ZipArchive;

final class TargetRuntimeEvidenceZipBoundaryTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(ZipArchive::class)) {
            self::markTestSkipped('Evidence ZIP boundaries require ext-zip.');
        }
        $this->temporaryDirectory = sys_get_temp_dir().'/nexora-zip-bound-'.bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory.'/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($this->temporaryDirectory);
        parent::tearDown();
    }

    /** @param callable(ZipArchive):void $populate */
    private function evidenceArchive(callable $populate): string
    {
        $path = $this->temporaryDirectory.'/evidence.zip';
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $populate($zip);
        self::assertTrue($zip->close());
        return $path;
    }

    private function verify(string $path): Process
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2).'/scripts/target-runtime-evidence-verify.php',
            '--input='.$path,
        ]);
        $process->setTimeout(30);
        $process->run();
        return $process;
    }

    #[Test]
    public function oversized_expanded_entry_is_rejected_before_untrusted_extraction(): void
    {
        $archive = $this->evidenceArchive(static function (ZipArchive $zip): void {
            self::assertTrue($zip->addFromString('large.log', str_repeat('X', 16 * 1024 * 1024 + 1)));
        });
        $process = $this->verify($archive);
        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString('expanded-size limit exceeded', $process->getErrorOutput());
    }

    #[Test]
    public function excessive_entry_count_is_rejected_before_untrusted_extraction(): void
    {
        $archive = $this->evidenceArchive(static function (ZipArchive $zip): void {
            for ($index = 0; $index < 1025; $index++) {
                self::assertTrue($zip->addFromString("files/{$index}.log", ''));
            }
        });
        $process = $this->verify($archive);
        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString('too many entries', $process->getErrorOutput());
    }

    #[Test]
    public function oversized_combined_expanded_archive_is_rejected(): void
    {
        // Reuse the same on-disk source file across entries, avoiding a huge
        // in-memory test fixture while verifying the aggregate ZIP limit.
        $source = $this->temporaryDirectory.'/large-source.log';
        self::assertSame(16 * 1024 * 1024, file_put_contents($source, str_repeat('Q', 16 * 1024 * 1024)));
        $archive = $this->evidenceArchive(static function (ZipArchive $zip) use ($source): void {
            for ($index = 0; $index < 17; $index++) {
                self::assertTrue($zip->addFile($source, "steps/{$index}.log"));
            }
        });
        $process = $this->verify($archive);
        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString('expanded-size limit exceeded', $process->getErrorOutput());
    }

    #[Test]
    public function duplicate_normalized_paths_are_rejected(): void
    {
        $archive = $this->evidenceArchive(static function (ZipArchive $zip): void {
            self::assertTrue($zip->addFromString('steps/output.log', 'one'));
            self::assertTrue($zip->addFromString('steps\\output.log', 'two'));
        });
        $process = $this->verify($archive);
        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString('duplicate normalized paths', $process->getErrorOutput());
    }

    #[Test]
    public function small_archive_passes_bounds_and_reaches_required_evidence_validation(): void
    {
        $archive = $this->evidenceArchive(static function (ZipArchive $zip): void {
            self::assertTrue($zip->addFromString('notes.txt', 'synthetic data only'));
        });
        $process = $this->verify($archive);
        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString('Evidence missing summary.json', $process->getErrorOutput());
        self::assertStringNotContainsString('limit exceeded', $process->getErrorOutput());
    }
}
