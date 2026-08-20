<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DocumentEngineFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_create_and_revision_a_structured_document(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $response = $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Publishing Foundation',
            'slug' => 'publishing-foundation',
            'type' => 'document',
            'status' => 'draft',
            'workflow_status' => 'draft',
            'excerpt' => 'A structured content foundation.',
            'content' => [
                'version' => 1,
                'blocks' => [[
                    'id' => 'paragraph_0001',
                    'type' => 'paragraph',
                    'version' => 1,
                    'data' => ['text' => 'Writer foundation content.'],
                    'children' => [],
                ]],
            ],
        ]);

        $document = Document::query()->where('slug', 'publishing-foundation')->firstOrFail();
        $response->assertRedirect(route('admin.documents.edit', $document));
        self::assertSame(1, $document->revisions()->count());
        self::assertSame('Writer foundation content.', $document->content['blocks'][0]['data']['text']);

        $this->actingAs($admin)->put("/admin/documents/{$document->id}", [
            'title' => 'Publishing Foundation Updated',
            'slug' => 'publishing-foundation',
            'type' => 'document',
            'status' => 'published',
            'workflow_status' => 'review',
            'lock_version' => 1,
            'excerpt' => 'Updated structured content foundation.',
            'content' => [
                'version' => 1,
                'blocks' => [[
                    'id' => 'heading_0001',
                    'type' => 'heading',
                    'version' => 1,
                    'data' => ['text' => 'Updated heading', 'level' => 2],
                    'children' => [],
                ]],
            ],
        ])->assertSessionHasNoErrors();

        self::assertSame(2, $document->fresh()->revisions()->count());
        self::assertNotNull($document->fresh()->published_at);
    }
}
