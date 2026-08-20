<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DocumentEditorialFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_editorial_autosave_detects_stale_server_versions_and_revision_can_be_restored(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Editorial Draft', 'slug' => 'editorial-draft', 'type' => 'document', 'status' => 'draft', 'workflow_status' => 'draft', 'excerpt' => '',
            'content' => ['version' => 1, 'blocks' => [['id' => 'p1', 'type' => 'paragraph', 'version' => 1, 'data' => ['text' => 'Version one'], 'children' => []]]],
        ]);
        $document = Document::query()->where('slug', 'editorial-draft')->firstOrFail();

        $this->actingAs($admin)->put("/admin/documents/{$document->id}/autosave", [
            'base_lock_version' => 1, 'base_revision' => 1, 'title' => 'Editorial Draft', 'slug' => 'editorial-draft', 'excerpt' => '', 'workflow_status' => 'draft',
            'content' => ['version' => 1, 'blocks' => [['id' => 'p1', 'type' => 'paragraph', 'version' => 1, 'data' => ['text' => 'Autosaved text'], 'children' => []]]],
        ])->assertOk()->assertJsonPath('status', 'saved');

        $this->actingAs($admin)->put("/admin/documents/{$document->id}", [
            'title' => 'Editorial Draft', 'slug' => 'editorial-draft', 'type' => 'document', 'status' => 'draft', 'workflow_status' => 'review', 'excerpt' => '', 'lock_version' => 1,
            'content' => ['version' => 1, 'blocks' => [['id' => 'p1', 'type' => 'paragraph', 'version' => 1, 'data' => ['text' => 'Version two'], 'children' => []]]],
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->put("/admin/documents/{$document->id}/autosave", [
            'base_lock_version' => 1, 'base_revision' => 1, 'title' => 'Stale', 'slug' => 'editorial-draft', 'excerpt' => '', 'workflow_status' => 'draft',
            'content' => ['version' => 1, 'blocks' => []],
        ])->assertStatus(409)->assertJsonPath('status', 'conflict');

        $document->refresh();
        $revisionOne = $document->revisions()->where('revision', 1)->firstOrFail();
        $this->actingAs($admin)->post("/admin/documents/{$document->id}/revisions/{$revisionOne->id}/restore", ['lock_version' => $document->lock_version])
            ->assertRedirect(route('admin.documents.edit', $document));

        self::assertSame('Version one', $document->fresh()->content['blocks'][0]['data']['text']);
        self::assertSame(3, $document->fresh()->revisions()->count());
    }
}
