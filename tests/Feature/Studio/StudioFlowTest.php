<?php

declare(strict_types=1);

namespace Tests\Feature\Studio;

use App\Models\Document;
use App\Models\Role;
use App\Models\StudioCanvas;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudioFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_create_save_publish_and_render_document_canvas(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $document = Document::factory()->create([
            'author_id' => $admin->id,
            'last_edited_by' => $admin->id,
            'status' => 'published',
            'published_at' => now(),
            'title' => 'Studio Public Title',
            'slug' => 'studio-public-title',
            'content' => [
                'version' => 1,
                'blocks' => [[
                    'id' => 'fallback_block',
                    'type' => 'paragraph',
                    'version' => 1,
                    'data' => ['text' => 'Document engine fallback body'],
                    'children' => [],
                ]],
            ],
        ]);

        $response = $this->actingAs($admin)->post('/admin/studio', [
            'name' => 'Article visual layout',
            'scope' => 'document',
            'document_id' => $document->id,
            'theme_id' => null,
            'template_key' => null,
        ]);
        $canvas = StudioCanvas::query()->firstOrFail();
        $response->assertRedirect("/admin/studio/{$canvas->id}/edit");

        $this->actingAs($admin)->put("/admin/studio/{$canvas->id}", [
            'name' => 'Article visual layout',
            'lock_version' => 1,
            'content' => ['version' => 1, 'children' => [[
                'id' => 'heading_12345678',
                'type' => 'heading',
                'props' => ['text' => 'Static fallback title', 'level' => 2],
                'styles' => [
                    'base' => ['fontSize' => '36px'],
                    'tablet' => ['fontSize' => '30px'],
                    'mobile' => ['fontSize' => '24px'],
                ],
                'bindings' => ['text' => 'document.title'],
                'children' => [],
            ], [
                'id' => 'text_1234567890',
                'type' => 'text',
                'props' => ['text' => 'Studio-only rendered body'],
                'styles' => ['base' => [], 'tablet' => [], 'mobile' => []],
                'bindings' => [],
                'children' => [],
            ]]],
        ])->assertSessionHasNoErrors();

        $canvas->refresh();
        self::assertSame(2, $canvas->lock_version);
        self::assertSame(2, $canvas->revisions()->count());

        $this->actingAs($admin)->post("/admin/studio/{$canvas->id}/publish")->assertSessionHasNoErrors();
        $canvas->refresh();
        self::assertSame('published', $canvas->status);
        self::assertSame(3, $canvas->lock_version);

        $this->get('/content/'.$document->slug)
            ->assertOk()
            ->assertSee('Studio Public Title')
            ->assertSee('Studio-only rendered body')
            ->assertSee('nx-studio-page', false)
            ->assertSee('@media(max-width:1024px)', false)
            ->assertSee('@media(max-width:640px)', false)
            ->assertDontSee('Document engine fallback body');

        $this->actingAs($admin)->post("/admin/studio/{$canvas->id}/unpublish")->assertSessionHasNoErrors();
        self::assertSame('draft', $canvas->fresh()->status);

        $this->get('/content/'.$document->slug)
            ->assertOk()
            ->assertSee('Document engine fallback body')
            ->assertDontSee('Studio-only rendered body');
    }

    public function test_stale_studio_update_is_rejected_without_overwriting_newer_revision(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $canvas = StudioCanvas::query()->create([
            'uuid' => 'studio-concurrency-fixture',
            'name' => 'Concurrency canvas',
            'scope' => 'standalone',
            'status' => 'draft',
            'content' => ['version' => 1, 'children' => []],
            'metadata' => ['viewport' => 'desktop'],
            'schema_version' => 1,
            'lock_version' => 2,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)->put("/admin/studio/{$canvas->id}", [
            'name' => 'Stale overwrite attempt',
            'lock_version' => 1,
            'content' => ['version' => 1, 'children' => []],
        ])->assertSessionHasErrors('canvas');

        $canvas->refresh();
        self::assertSame('Concurrency canvas', $canvas->name);
        self::assertSame(2, $canvas->lock_version);
        self::assertSame(0, $canvas->revisions()->count());
    }

    public function test_studio_rejects_unsafe_button_url_and_normalizes_target(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $this->actingAs($admin)->post('/admin/studio', [
            'name' => 'Safe link canvas',
            'scope' => 'standalone',
            'document_id' => null,
            'theme_id' => null,
            'template_key' => null,
        ])->assertSessionHasNoErrors();

        $canvas = StudioCanvas::query()->firstOrFail();
        $this->actingAs($admin)->put("/admin/studio/{$canvas->id}", [
            'name' => 'Safe link canvas',
            'lock_version' => 1,
            'content' => ['version' => 1, 'children' => [[
                'id' => 'button_12345678',
                'type' => 'button',
                'props' => ['text' => 'Unsafe link', 'href' => 'javascript:alert(1)', 'target' => 'unsafe-window'],
                'styles' => ['base' => [], 'tablet' => [], 'mobile' => []],
                'bindings' => [],
                'children' => [],
            ]]],
        ])->assertSessionHasNoErrors();

        $button = $canvas->fresh()->content['children'][0];
        self::assertSame('#', $button['props']['href']);
        self::assertSame('_self', $button['props']['target']);
    }
}
