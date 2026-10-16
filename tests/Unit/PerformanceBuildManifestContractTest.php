<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/performance-build-manifest-contracts.php';

final class PerformanceBuildManifestContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/nexora-production-build-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/public/build/assets', 0700, true);
        file_put_contents($this->root.'/public/build/manifest.json', '{}');
        file_put_contents($this->root.'/public/build/assets/app-aaaaaaaa.js', 'console.log(1);');
        file_put_contents($this->root.'/public/build/assets/app-bbbbbbbb.css', 'body{}');
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            $entry->isDir() && !$entry->isLink()
                ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($this->root);
    }

    /** @return array<string,array<string,mixed>> */
    private function manifest(): array
    {
        return [
            'resources/js/app.tsx'=>['file'=>'assets/app-aaaaaaaa.js'],
            'resources/css/app.css'=>['file'=>'assets/app-bbbbbbbb.css'],
        ];
    }

    #[Test]
    public function ordinary_production_manifest_is_valid(): void
    {
        self::assertSame([], \nexoraPerformanceBuildManifestErrors($this->root, $this->manifest()));
    }

    #[Test]
    public function complete_vite_dynamic_imports_and_static_media_are_valid(): void
    {
        $manifest=$this->manifest();
        $manifest['resources/js/app.tsx']['imports']=['resources/css/app.css'];
        $manifest['resources/js/app.tsx']['dynamicImports']=['resources/js/lazy.tsx'];
        $manifest['resources/js/app.tsx']['assets']=['assets/logo.svg'];
        $manifest['resources/js/lazy.tsx']=['file'=>'assets/lazy-dddddddd.js'];
        file_put_contents($this->root.'/public/build/assets/logo.svg','<svg/>');
        file_put_contents($this->root.'/public/build/assets/lazy-dddddddd.js','export default 42;');
        self::assertSame([], \nexoraPerformanceBuildManifestErrors($this->root, $manifest));
    }

    #[Test]
    public function traversal_and_absolute_file_paths_are_rejected(): void
    {
        foreach (['../.env', 'assets/../../.env', '/etc/passwd', 'assets\private.js', './assets/app.js'] as $asset) {
            $manifest=$this->manifest();
            $manifest['resources/js/app.tsx']['file']=$asset;
            self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root, $manifest));
        }
    }

    #[Test]
    public function removed_lazy_chunk_is_not_accepted_as_a_pass_build(): void
    {
        $manifest=$this->manifest();
        $manifest['resources/js/app.tsx']['dynamicImports']=['resources/js/lazy.tsx'];
        $manifest['resources/js/lazy.tsx']=['file'=>'assets/lazy-dddddddd.js'];
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$manifest));
    }

    #[Test]
    public function unknown_static_or_dynamic_import_is_rejected(): void
    {
        foreach (['imports','dynamicImports'] as $field) {
            $manifest=$this->manifest();
            $manifest['resources/js/app.tsx'][$field]=['missing.tsx'];
            self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$manifest));
        }
    }

    #[Test]
    public function malformed_import_asset_and_css_lists_are_rejected(): void
    {
        foreach (['css','assets','imports','dynamicImports'] as $field) {
            $manifest=$this->manifest();
            $manifest['resources/js/app.tsx'][$field]='assets/app-aaaaaaaa.js';
            self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$manifest));
            $manifest['resources/js/app.tsx'][$field]=['wrong-key'=>'value'];
            self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$manifest));
        }
    }

    #[Test]
    public function deleted_static_asset_is_rejected(): void
    {
        $manifest=$this->manifest();
        $manifest['resources/js/app.tsx']['assets']=['assets/deleted.svg'];
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$manifest));
    }

    #[Test]
    public function linked_referenced_file_is_rejected_even_when_target_exists(): void
    {
        if (!function_exists('symlink')) self::markTestSkipped('Symlinks unavailable.');
        unlink($this->root.'/public/build/assets/app-aaaaaaaa.js');
        if (!@symlink('/etc/hosts',$this->root.'/public/build/assets/app-aaaaaaaa.js')) {
            self::markTestSkipped('Symlink creation restricted.');
        }
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$this->manifest()));
    }

    #[Test]
    public function unrelated_symlink_in_build_tree_is_also_rejected(): void
    {
        if (!function_exists('symlink')) self::markTestSkipped('Symlinks unavailable.');
        if (!@symlink('/etc/hosts',$this->root.'/public/build/unused')) {
            self::markTestSkipped('Symlink creation restricted.');
        }
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$this->manifest()));
    }

    #[Test]
    public function linked_build_parent_and_hot_file_are_rejected(): void
    {
        if (!function_exists('symlink')) self::markTestSkipped('Symlinks unavailable.');
        if (!@symlink($this->root.'/missing-hot',$this->root.'/public/hot')) {
            self::markTestSkipped('Symlink creation restricted.');
        }
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$this->manifest()));
        unlink($this->root.'/public/hot');
        rename($this->root.'/public/build',$this->root.'/real-build');
        symlink($this->root.'/real-build',$this->root.'/public/build');
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$this->manifest()));
    }

    #[Test]
    public function oversized_generated_file_is_rejected_before_memory_read(): void
    {
        $file=$this->root.'/public/build/assets/giant.dat';
        $stream=fopen($file,'wb');
        ftruncate($stream,64*1024*1024+1);
        fclose($stream);
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$this->manifest()));
    }

    #[Test]
    public function secret_dotenv_in_unreferenced_build_file_is_rejected(): void
    {
        file_put_contents($this->root.'/public/build/.env', 'FAKE_TEST_VALUE=not-a-secret');
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root, $this->manifest()));
    }

    #[Test]
    public function dotfile_nested_inside_assets_is_rejected(): void
    {
        file_put_contents($this->root.'/public/build/assets/.hidden', 'fixture');
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root, $this->manifest()));
    }

    #[Test]
    public function hidden_directory_is_rejected_even_when_empty(): void
    {
        mkdir($this->root.'/public/build/.git', 0700, true);
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root, $this->manifest()));
    }

    #[Test]
    public function unreferenced_server_script_is_rejected(): void
    {
        file_put_contents($this->root.'/public/build/assets/debug.php', '<?php echo 1;');
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root, $this->manifest()));
    }

    #[Test]
    public function credential_file_and_database_dump_are_not_public_build_assets(): void
    {
        foreach (['private.pem', 'records.sqlite', 'backup.sql', 'config.json'] as $file) {
            $path=$this->root.'/public/build/assets/'.$file;
            file_put_contents($path, 'synthetic test fixture');
            self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root, $this->manifest()));
            unlink($path);
        }
    }

    #[Test]
    public function manifest_cannot_reference_credentials_or_json_configs(): void
    {
        foreach (['assets/config.json', 'assets/debug.php', 'assets/secrets.pem'] as $asset) {
            $path=$this->root.'/public/build/'.$asset;
            file_put_contents($path, 'fixture');
            $manifest=$this->manifest();
            $manifest['resources/js/app.tsx']['assets']=[$asset];
            self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root, $manifest));
            unlink($path);
        }
    }

    #[Test]
    public function manifest_json_is_only_allowed_as_root_manifest_file(): void
    {
        self::assertSame(true, \nexoraPerformanceBuildSafeOutputName('manifest.json'));
        self::assertSame(false, \nexoraPerformanceBuildSafeOutputName('assets/manifest.json'));
        self::assertSame(false, \nexoraPerformanceBuildSafeOutputName('assets/.private/app.js'));
    }

    #[Test]
    public function supported_media_fonts_and_wasm_remain_allowed(): void
    {
        foreach (['assets/logo.svg', 'assets/font.woff2', 'assets/player.wasm', 'assets/icon.webp'] as $asset) {
            self::assertSame(true, \nexoraPerformanceBuildSafeOutputName($asset));
        }
    }

    #[Test]
    public function malformed_manifest_entries_are_rejected(): void
    {
        $manifest=$this->manifest();
        $manifest['resources/js/app.tsx']=['file'=>['assets/app-aaaaaaaa.js']];
        self::assertNotSame([], \nexoraPerformanceBuildManifestErrors($this->root,$manifest));
    }
}
