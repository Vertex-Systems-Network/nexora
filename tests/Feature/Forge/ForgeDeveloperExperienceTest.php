<?php

declare(strict_types=1);

namespace Tests\Feature\Forge;

use App\Nexora\Extensions\Services\ExtensionManifestValidator;
use App\Nexora\Forge\Services\ForgeExtensionScaffolder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ForgeDeveloperExperienceTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (is_dir($path) && ! is_link($path)) {
                File::deleteDirectory($path);
            } elseif (file_exists($path) || is_link($path)) {
                @unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_dry_run_is_zero_write_and_reports_deterministic_plan(): void
    {
        $id = $this->identifier('dry');
        $target = base_path('extensions/'.$id);
        $this->created[] = $target;

        $exit = Artisan::call('nexora:make:extension', [
            'identifier' => $id,
            '--name' => 'Forge Dry Run',
            '--dry-run' => true,
        ]);

        self::assertSame(0, $exit);
        self::assertDirectoryDoesNotExist($target);
        $output = Artisan::output();
        self::assertStringContainsString('Forge dry run', $output);
        self::assertStringContainsString('nexora.json', $output);
        self::assertStringContainsString('Forge only generates source', $output);
    }

    public function test_traversal_and_non_directory_destinations_are_rejected(): void
    {
        self::assertSame(1, Artisan::call('nexora:make:extension', [
            'identifier' => '../../outside',
            '--dry-run' => true,
        ]));

        $id = $this->identifier('file');
        $target = base_path('extensions/'.$id);
        File::ensureDirectoryExists(dirname($target));
        File::put($target, 'not-a-directory');
        $this->created[] = $target;

        self::assertSame(1, Artisan::call('nexora:make:extension', [
            'identifier' => $id,
        ]));
        self::assertStringContainsString('not a directory', Artisan::output());
    }

    public function test_arbitrary_existing_directory_cannot_be_force_overwritten(): void
    {
        $id = $this->identifier('foreign');
        $target = base_path('extensions/'.$id);
        $this->created[] = $target;
        File::ensureDirectoryExists($target);
        File::put($target.'/README.md', 'foreign-content');

        $exit = Artisan::call('nexora:make:extension', [
            'identifier' => $id,
            '--force' => true,
        ]);

        self::assertSame(1, $exit);
        self::assertSame('foreign-content', File::get($target.'/README.md'));
        self::assertFileDoesNotExist($target.'/.nexora-forge.json');
    }

    public function test_forge_owned_force_refresh_is_deterministic_and_preserves_developer_files(): void
    {
        $id = $this->identifier('refresh');
        $target = base_path('extensions/'.$id);
        $this->created[] = $target;

        self::assertSame(0, Artisan::call('nexora:make:extension', [
            'identifier' => $id,
            '--name' => 'Forge Refresh',
            '--type' => 'integration',
        ]));

        $firstManifest = File::get($target.'/nexora.json');
        File::put($target.'/README.md', 'developer-edited-generated-file');
        File::put($target.'/src/Custom.php', '<?php // preserve me');

        self::assertSame(0, Artisan::call('nexora:make:extension', [
            'identifier' => $id,
            '--name' => 'Forge Refresh',
            '--type' => 'integration',
            '--force' => true,
        ]));

        self::assertSame($firstManifest, File::get($target.'/nexora.json'));
        self::assertStringContainsString('Forge source package for Nexora.', File::get($target.'/README.md'));
        self::assertSame('<?php // preserve me', File::get($target.'/src/Custom.php'));

        $marker = json_decode(File::get($target.'/.nexora-forge.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('nexora.forge.scaffold.v1', $marker['schema'] ?? null);
        self::assertSame($id, $marker['identifier'] ?? null);

        $manifest = json_decode(File::get($target.'/nexora.json'), true, 512, JSON_THROW_ON_ERROR);
        $validated = app(ExtensionManifestValidator::class)->validate($manifest);
        self::assertSame($id, $validated->identifier);
        self::assertSame('integration', $validated->type);
    }

    public function test_service_plan_and_create_share_the_same_stable_file_contract(): void
    {
        $id = $this->identifier('contract');
        $target = base_path('extensions/'.$id);
        $this->created[] = $target;
        $forge = app(ForgeExtensionScaffolder::class);

        $plan = $forge->plan($id, 'Forge Contract', 'extension');
        $created = $forge->create($id, 'Forge Contract', 'extension');

        self::assertSame($plan['files'], $created['files']);
        self::assertSame([
            '.nexora-forge.json',
            'README.md',
            'composer.json',
            'database/migrations/.gitkeep',
            'nexora.json',
            'resources/.gitkeep',
            'src/.gitkeep',
            'tests/.gitkeep',
        ], $created['files']);
    }

    private function identifier(string $suffix): string
    {
        return 'nexora-tests.'.$suffix.'-'.strtolower(Str::random(8));
    }
}
