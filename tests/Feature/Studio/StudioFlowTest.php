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

    public function test_administrator_can_create_save_and_publish_document_canvas(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $document = Document::factory()->create(['author_id' => $admin->id, 'last_edited_by' => $admin->id]);

        $response = $this->actingAs($admin)->post('/admin/studio', [
            'name' => 'Article visual layout', 'scope' => 'document', 'document_id' => $document->id,
            'theme_id' => null, 'template_key' => null,
        ]);
        $canvas = StudioCanvas::query()->firstOrFail();
        $response->assertRedirect("/admin/studio/{$canvas->id}/edit");

        $this->actingAs($admin)->put("/admin/studio/{$canvas->id}", [
            'name' => 'Article visual layout', 'lock_version' => 1,
            'content' => ['version' => 1, 'children' => [[
                'id' => 'heading_12345678', 'type' => 'heading', 'props' => ['text' => 'Hello', 'level' => 2],
                'styles' => ['base' => ['fontSize' => '36px'], 'tablet' => [], 'mobile' => []], 'bindings' => ['text' => 'document.title'], 'children' => [],
            ]]],
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post("/admin/studio/{$canvas->id}/publish")->assertSessionHasNoErrors();
        self::assertSame('published', $canvas->fresh()->status);
        self::assertSame(2, $canvas->revisions()->count());
    }
}
