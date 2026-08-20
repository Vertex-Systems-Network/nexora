<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Models\Document;
use App\Models\Role;
use App\Models\Theme;
use App\Models\ThemeVersion;
use App\Models\User;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ThemeEngineFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
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
        $response = $this->actingAs($admin)->postJson('/admin/appearance/themes/versions/'.$version->id.'/preview');
        $response->assertOk()->assertJsonStructure(['url', 'expires_in_minutes']);
        self::assertSame($before, app(ThemeManagerContract::class)->active()?->id);
    }
}
