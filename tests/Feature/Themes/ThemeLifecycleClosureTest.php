<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Models\Role;
use App\Models\SecurityScan;
use App\Models\Theme;
use App\Models\User;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

/**
 * THEME-CLOSURE-001 / SYS-THEME-ENGINE: closes the lifecycle steps ThemeEngineFlowTest does not prove —
 * switching between two uploaded themes, the preview page itself, rendering after rollback, Sentinel
 * blocking an unsafe theme, and authorization on lifecycle actions.
 */
final class ThemeLifecycleClosureTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $zips = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
        $this->withHeader('X-Nexora-Deployment-Generation', app(RuntimeDeploymentIdentity::class)->generation());
    }

    protected function tearDown(): void
    {
        foreach ($this->zips as $zip) {
            @unlink($zip);
        }
        // Installed theme files land on disk; remove them as ThemeEngineFlowTest does.
        foreach (['acme.alpha', 'acme.beta', 'acme.evil', 'acme.member'] as $id) {
            File::deleteDirectory(storage_path('app/nexora/themes/'.$id));
            File::deleteDirectory(public_path('nexora-themes/'.$id));
        }
        parent::tearDown();
    }

    public function test_switching_between_uploaded_themes_renders_each_and_rollback_restores_the_previous_one(): void
    {
        $admin = $this->admin();
        $alpha = $this->install($admin, 'acme.alpha', 'Alpha Shell');
        $beta = $this->install($admin, 'acme.beta', 'Beta Shell');

        $this->actingAs($admin)->post('/admin/appearance/themes/versions/'.$alpha.'/activate')->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('Alpha Shell')->assertDontSee('Beta Shell');

        $this->actingAs($admin)->post('/admin/appearance/themes/versions/'.$beta.'/activate')->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('Beta Shell')->assertDontSee('Alpha Shell');
        self::assertSame('inactive', Theme::query()->where('identifier', 'acme.alpha')->value('status'));

        $this->actingAs($admin)->post('/admin/appearance/themes/rollback')->assertSessionHasNoErrors();
        self::assertSame('acme.alpha', app(ThemeManagerContract::class)->active()?->theme?->identifier);
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('Alpha Shell')->assertDontSee('Beta Shell');
    }

    public function test_preview_renders_the_candidate_while_the_public_site_keeps_the_active_theme(): void
    {
        $admin = $this->admin();
        $alpha = $this->install($admin, 'acme.alpha', 'Alpha Shell');
        $beta = $this->install($admin, 'acme.beta', 'Beta Shell');
        $this->actingAs($admin)->post('/admin/appearance/themes/versions/'.$alpha.'/activate')->assertSessionHasNoErrors();

        $url = (string) $this->actingAs($admin)->postJson('/admin/appearance/themes/versions/'.$beta.'/preview')->assertOk()->json('url');
        self::assertMatchesRegularExpression('#/theme-preview/[a-f0-9]{64}$#', $url);

        $this->actingAs($admin)->get(parse_url($url, PHP_URL_PATH))->assertOk()->assertSee('Beta Shell');
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('Alpha Shell')->assertDontSee('Beta Shell');
        self::assertSame('acme.alpha', app(ThemeManagerContract::class)->active()?->theme?->identifier);
    }

    public function test_sentinel_blocks_a_theme_carrying_executable_php(): void
    {
        $admin = $this->admin();
        $zip = $this->themeZip('acme.evil', 'Evil Shell', ['functions.php' => "<?php system(\$_GET['c'] ?? 'id');\n"]);

        $response = $this->actingAs($admin)->post('/admin/appearance/themes/install', [
            'package' => new UploadedFile($zip, 'acme-evil.zip', 'application/zip', null, true),
        ]);

        $scan = SecurityScan::query()->where('source_name', 'acme-evil.zip')->firstOrFail();
        self::assertSame('block', $scan->decision);
        // The operator is sent to the Sentinel report for this exact scan, with an explanation.
        $response->assertRedirect(route('admin.security.sentinel.show', $scan))->assertSessionHas('warning');
        self::assertFalse(Theme::query()->where('identifier', 'acme.evil')->exists());
        auth()->logout();
        $this->get('/')->assertOk()->assertDontSee('Evil Shell');
    }

    public function test_users_without_theme_permissions_cannot_install_or_activate(): void
    {
        $admin = $this->admin();
        $alpha = $this->install($admin, 'acme.alpha', 'Alpha Shell');
        $member = User::factory()->create(['email_verified_at' => now()]);
        $member->roles()->attach(Role::query()->where('slug', 'user')->value('id'));
        $before = app(ThemeManagerContract::class)->active()?->id;

        $zip = $this->themeZip('acme.member', 'Member Shell');
        $this->actingAs($member)->post('/admin/appearance/themes/install', [
            'package' => new UploadedFile($zip, 'acme-member.zip', 'application/zip', null, true),
        ])->assertForbidden();
        $this->actingAs($member)->post('/admin/appearance/themes/versions/'.$alpha.'/activate')->assertForbidden();
        $this->actingAs($member)->post('/admin/appearance/themes/rollback')->assertForbidden();

        self::assertFalse(Theme::query()->where('identifier', 'acme.member')->exists());
        self::assertSame($before, app(ThemeManagerContract::class)->active()?->id);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        return $admin;
    }

    /** Uploads a safe theme and returns its installed version id. */
    private function install(User $admin, string $id, string $marker): int
    {
        $zip = $this->themeZip($id, $marker);
        $this->actingAs($admin)->post('/admin/appearance/themes/install', [
            'package' => new UploadedFile($zip, $id.'.zip', 'application/zip', null, true),
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        return (int) Theme::query()->where('identifier', $id)->firstOrFail()->versions()->where('version', '1.0.0')->value('id');
    }

    /** @param array<string,string> $extraFiles */
    private function themeZip(string $id, string $marker, array $extraFiles = []): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-theme-closure-'.bin2hex(random_bytes(4)).'.zip';
        $this->zips[] = $path;
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $name = ucfirst(str_replace('acme.', '', $id)).' Theme';
        $zip->addFromString('nexora.json', json_encode([
            'schema' => '1.0', 'id' => $id, 'name' => $name, 'type' => 'theme', 'version' => '1.0.0',
            'capabilities' => [], 'requires' => ['nexora' => '*'],
        ], JSON_THROW_ON_ERROR));
        $zip->addFromString('theme.json', json_encode([
            'id' => $id, 'name' => $name, 'version' => '1.0.0', 'engine' => 'nexora-safe-html',
            'description' => 'Theme lifecycle closure fixture.',
            'templates' => ['home' => 'templates/home.html', 'document' => 'templates/document.html'],
            'stylesheet' => 'assets/theme.css',
        ], JSON_THROW_ON_ERROR));
        $shell = '<!doctype html><html><head>{{ nx_head }}{{ nx_theme_assets }}{{ nx_schema }}</head><body><main>'.$marker.' {{ nx_content }}</main></body></html>';
        $zip->addFromString('templates/home.html', $shell);
        $zip->addFromString('templates/document.html', $shell);
        $zip->addFromString('assets/theme.css', 'body{font-family:system-ui,sans-serif}');
        foreach ($extraFiles as $file => $contents) {
            $zip->addFromString($file, $contents);
        }
        self::assertTrue($zip->close());

        return $path;
    }
}
