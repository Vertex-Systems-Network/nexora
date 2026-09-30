<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContentCollection;
use App\Models\Document;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContentCollectionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_create_collection_attach_typed_entry_update_and_detach_without_deleting_document(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $article = Document::factory()->create(['type' => 'article', 'author_id' => $admin->id, 'last_edited_by' => $admin->id]);
        $plain = Document::factory()->create(['type' => 'document', 'author_id' => $admin->id, 'last_edited_by' => $admin->id]);

        $this->actingAs($admin)->post('/admin/collections', [
            'name' => 'Case Studies',
            'slug' => 'case-studies',
            'description' => 'Reusable case study entries.',
            'status' => 'active',
            'document_type' => 'article',
            'schema' => [
                ['key' => 'client_name', 'label' => 'Client name', 'type' => 'text', 'required' => true],
                ['key' => 'project_value', 'label' => 'Project value', 'type' => 'number', 'required' => false],
            ],
        ])->assertSessionHasNoErrors();

        $collection = ContentCollection::query()->where('slug', 'case-studies')->firstOrFail();
        self::assertSame('article', $collection->document_type);
        self::assertCount(2, (array) $collection->schema);

        $this->actingAs($admin)->post("/admin/collections/{$collection->id}/documents", [
            'document_id' => $article->id,
            'data' => ['client_name' => 'Acme', 'project_value' => '1250.50'],
        ])->assertSessionHasNoErrors();

        $pivot = $collection->documents()->whereKey($article->id)->firstOrFail()->pivot;
        $data = json_decode((string) $pivot->data, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Acme', $data['client_name']);
        self::assertSame(1250.5, $data['project_value']);

        $this->actingAs($admin)->post("/admin/collections/{$collection->id}/documents", [
            'document_id' => $plain->id,
            'data' => ['client_name' => 'Wrong type'],
        ])->assertSessionHasErrors('document_id');

        $this->actingAs($admin)->put("/admin/collections/{$collection->id}/documents/{$article->id}", [
            'data' => ['client_name' => 'Acme Updated', 'project_value' => 1500],
        ])->assertSessionHasNoErrors();

        $updated = $collection->documents()->whereKey($article->id)->firstOrFail()->pivot;
        $updatedData = json_decode((string) $updated->data, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Acme Updated', $updatedData['client_name']);
        self::assertSame(1500, $updatedData['project_value']);

        $this->actingAs($admin)->delete("/admin/collections/{$collection->id}/documents/{$article->id}")->assertSessionHasNoErrors();
        self::assertFalse($collection->documents()->whereKey($article->id)->exists());
        self::assertTrue(Document::query()->whereKey($article->id)->exists());

        $this->actingAs($admin)->delete("/admin/collections/{$collection->id}")->assertRedirect('/admin/collections');
        self::assertTrue(Document::query()->whereKey($article->id)->exists());
    }

    public function test_collection_schema_rejects_duplicate_keys_and_entry_urls_are_http_only(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $document = Document::factory()->create(['author_id' => $admin->id, 'last_edited_by' => $admin->id]);

        $this->actingAs($admin)->post('/admin/collections', [
            'name' => 'Broken schema', 'status' => 'active', 'document_type' => null,
            'schema' => [
                ['key' => 'website', 'label' => 'Website', 'type' => 'url', 'required' => false],
                ['key' => 'website', 'label' => 'Duplicate', 'type' => 'text', 'required' => false],
            ],
        ])->assertSessionHasErrors('schema.1.key');

        $this->actingAs($admin)->post('/admin/collections', [
            'name' => 'Partner directory', 'slug' => 'partner-directory', 'status' => 'active', 'document_type' => null,
            'schema' => [['key' => 'website', 'label' => 'Website', 'type' => 'url', 'required' => true]],
        ])->assertSessionHasNoErrors();

        $collection = ContentCollection::query()->where('slug', 'partner-directory')->firstOrFail();
        $this->actingAs($admin)->post("/admin/collections/{$collection->id}/documents", [
            'document_id' => $document->id,
            'data' => ['website' => 'javascript:alert(1)'],
        ])->assertSessionHasErrors('data.website');

        self::assertFalse($collection->documents()->whereKey($document->id)->exists());
    }
}
