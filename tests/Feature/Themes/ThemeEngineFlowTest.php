<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Models\Document;
use App\Models\Role;
use App\Models\SecurityScan;
use App\Models\Theme;
use App\Models\ThemeVersion;
use App\Models\User;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

final class ThemeEngineFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/nexora/themes/acme.e2e'));
        File::deleteDirectory(public_path('nexora-themes/acme.e2e'));
        parent::tearDown();
    }

    public function test_builtin_fallback_theme_renders_public_home_and_published_document(): void
    {
        $theme = Theme::query()->where('identifier', 'nexora.base')->firstOrFail();
        self::assertSame('active', $theme->status);

        $this->get('/')->assertOk()->assertSee('Nexora', false);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $document = Document::factory()->create([
            'author_id' => $user->id,
            'last_edited_by' => $user->id,
            'status' => 'published',
            'published_at' => now(),
            'title' => 'Theme Engine Public Document',
            'slug' => 'theme-engine-public-document',
            'content' => ['version' => 1, 'blocks' => [['id' => 'a', 'type' => 'paragraph', 'version' => 1, 'data' => ['text' => 'Safe structured content'], 'children' => []]]],
        ]);

        $this->get('/content/'.$document->slug)->assertOk()->assertSee('Theme Engine Public Document')->assertSee('Safe structured content');
    }

    public function test_admin_can_create_preview_token_without_changing_active_theme(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $version = ThemeVersion::query()->whereHas('theme', fn ($query) => $query->where('identifier', 'nexora.base'))->firstOrFail();

        $before = app(ThemeManagerContract::class)->active()?->id;
        $this->withHeader(
            'X-Nexora-Deployment-Generation',
            app(RuntimeDeploymentIdentity::class)->generation(),
        );
        $response = $this->actingAs($admin)->postJson('/admin/appearance/themes/versions/'.$version->id.'/preview');
        $response->assertOk()->assertJsonStructure(['url', 'expires_in_minutes']);
        self::assertSame($before, app(ThemeManagerContract::class)->active()?->id);
    }

    public function test_uploaded_safe_theme_is_scanned_installed_previewed_activated_and_rolled_back(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $zipPath = $this->themeZip();

        try {
            $this->actingAs($admin)->post('/admin/appearance/themes/install', [
                'package' => new UploadedFile($zipPath, 'acme-e2e-theme.zip', 'application/zip', null, true),
            ])->assertSessionHasNoErrors();

            $theme = Theme::query()->where('identifier', 'acme.e2e')->firstOrFail();
            $version = $theme->versions()->where('version', '1.0.0')->firstOrFail();

            self::assertSame('inactive', $theme->status);
            self::assertSame('nexora-safe-html', $version->engine);
            self::assertFileExists($version->install_path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'home.html');
            $this->assertDatabaseHas('nx_security_scans', [
                'source_name' => 'acme-e2e-theme.zip',
                'decision' => 'allow',
                'status' => 'completed',
            ]);

            $before = app(ThemeManagerContract::class)->active()?->id;
            $this->withHeader(
                'X-Nexora-Deployment-Generation',
                app(RuntimeDeploymentIdentity::class)->generation(),
            );
            $preview = $this->actingAs($admin)->postJson('/admin/appearance/themes/versions/'.$version->id.'/preview');
            $preview->assertOk()->assertJsonStructure(['url', 'expires_in_minutes']);
            self::assertSame($before, app(ThemeManagerContract::class)->active()?->id);

            $this->actingAs($admin)
                ->post('/admin/appearance/themes/versions/'.$version->id.'/activate')
                ->assertSessionHasNoErrors();

            self::assertSame($version->id, app(ThemeManagerContract::class)->active()?->id);
            $this->get('/')->assertOk()->assertSee('Acme E2E Theme Shell');

            $this->actingAs($admin)
                ->post('/admin/appearance/themes/rollback')
                ->assertSessionHasNoErrors();

            self::assertSame('nexora.base', app(ThemeManagerContract::class)->active()?->theme?->identifier);
        } finally {
            @unlink($zipPath);
        }
    }

    public function test_pre_scanned_theme_can_be_promoted_after_sentinel_approval(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $zipPath = $this->themeZip();

        try {
            $this->actingAs($admin)->post('/admin/security/sentinel', [
                'package' => new UploadedFile($zipPath, 'acme-marketplace-theme.zip', 'application/zip', null, true),
            ])->assertSessionHasNoErrors();

            $scan = SecurityScan::query()->where('source_name', 'acme-marketplace-theme.zip')->latest('created_at')->firstOrFail();
            self::assertSame('completed', $scan->status);
            self::assertSame('allow', $scan->decision);
            self::assertSame('theme', $scan->manifest['type'] ?? null);
            self::assertFalse(Theme::query()->where('identifier', 'acme.e2e')->exists());

            $this->actingAs($admin)
                ->post('/admin/appearance/themes/install', ['scan_id' => $scan->id])
                ->assertRedirect('/admin/appearance/themes')
                ->assertSessionHasNoErrors();

            $theme = Theme::query()->where('identifier', 'acme.e2e')->firstOrFail();
            $version = $theme->versions()->where('version', '1.0.0')->firstOrFail();
            self::assertSame('nexora-safe-html', $version->engine);
            self::assertSame($scan->id, $version->source_scan_id);
            self::assertSame('installed', $scan->quarantinePackage()->value('status'));
        } finally {
            @unlink($zipPath);
        }
    }

    private function themeZip(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-theme-e2e-'.bin2hex(random_bytes(4)).'.zip';
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $packageManifest = [
            'schema' => '1.0',
            'id' => 'acme.e2e',
            'name' => 'Acme E2E Theme',
            'type' => 'theme',
            'version' => '1.0.0',
            'capabilities' => [],
            'requires' => ['nexora' => '*'],
        ];
        $themeManifest = [
            'id' => 'acme.e2e',
            'name' => 'Acme E2E Theme',
            'version' => '1.0.0',
            'engine' => 'nexora-safe-html',
            'description' => 'End-to-end Theme Engine acceptance fixture.',
            'templates' => [
                'home' => 'templates/home.html',
                'document' => 'templates/document.html',
            ],
            'stylesheet' => 'assets/theme.css',
            'design_tokens' => [
                'brand.primary' => [
                    'label' => 'Brand primary',
                    'type' => 'color',
                    'default' => '#7C3AED',
                ],
            ],
        ];
        $home = <<<'HTML'
<!doctype html>
<html><head>{{ nx_head }}{{ nx_theme_assets }}{{ nx_schema }}</head><body><main>Acme E2E Theme Shell {{ nx_content }}</main></body></html>
HTML;
        $document = <<<'HTML'
<!doctype html>
<html><head>{{ nx_head }}{{ nx_theme_assets }}{{ nx_schema }}</head><body><article>Acme E2E Document Shell {{ nx_content }}</article></body></html>
HTML;

        self::assertTrue($zip->addFromString('nexora.json', json_encode($packageManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)));
        self::assertTrue($zip->addFromString('theme.json', json_encode($themeManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)));
        self::assertTrue($zip->addFromString('templates/home.html', $home));
        self::assertTrue($zip->addFromString('templates/document.html', $document));
        self::assertTrue($zip->addFromString('assets/theme.css', 'body{font-family:system-ui,sans-serif}'));
        self::assertTrue($zip->close());

        return $path;
    }
}
