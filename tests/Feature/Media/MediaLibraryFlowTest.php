<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\MediaAsset;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MediaLibraryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_admin_can_upload_safe_media_and_trash_then_restore_it(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));

        $this->actingAs($admin)->post('/admin/media/upload', [
            'file'=>$this->fakePdf(),
            'title'=>'Platform guide', 'alt_text'=>'', 'caption'=>'Reference document',
        ])->assertSessionHasNoErrors();

        $asset=MediaAsset::query()->firstOrFail();
        self::assertSame('document',$asset->media_type);
        self::assertSame('application/pdf',$asset->mime_type);
        Storage::disk('public')->assertExists($asset->path);

        $this->actingAs($admin)->delete('/admin/media/'.$asset->id)->assertSessionHasNoErrors();
        self::assertNotNull(MediaAsset::withTrashed()->findOrFail($asset->id)->deleted_at);
        $this->actingAs($admin)->post('/admin/media/'.$asset->id.'/restore')->assertSessionHasNoErrors();
        self::assertNull(MediaAsset::query()->findOrFail($asset->id)->deleted_at);
    }

    public function test_media_picker_returns_reusable_active_media_without_exposing_trash(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));

        $this->actingAs($admin)->post('/admin/media/upload', [
            'file'=>$this->fakePdf(),
            'title'=>'Reusable guide', 'alt_text'=>'', 'caption'=>'Reusable document',
        ])->assertSessionHasNoErrors();

        $asset=MediaAsset::query()->firstOrFail();

        // The real Admin media picker uses same-origin fetch, which is wrapped
        // by the deployment fence and carries the active generation header.
        $this->withHeader(
            'X-Nexora-Deployment-Generation',
            app(RuntimeDeploymentIdentity::class)->generation(),
        );

        $this->actingAs($admin)
            ->getJson('/admin/media?picker=1&type=document&limit=12')
            ->assertOk()
            ->assertJsonPath('limit', 12)
            ->assertJsonPath('assets.0.id', $asset->id)
            ->assertJsonPath('assets.0.title', 'Reusable guide')
            ->assertJsonPath('assets.0.media_type', 'document')
            ->assertJsonStructure(['assets' => [['id','uuid','title','original_name','media_type','mime_type','url']]]);

        $this->actingAs($admin)->delete('/admin/media/'.$asset->id)->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->getJson('/admin/media?picker=1&type=document&limit=12')
            ->assertOk()
            ->assertJsonCount(0, 'assets');
    }

    public function test_active_content_upload_is_rejected(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));
        $this->actingAs($admin)->post('/admin/media/upload', [
            'file'=>UploadedFile::fake()->create('payload.svg', 2, 'image/svg+xml'),
        ])->assertSessionHasErrors('file');
        self::assertSame(0, MediaAsset::query()->count());
    }

    private function fakePdf(): UploadedFile
    {
        $header = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";

        return UploadedFile::fake()->createWithContent(
            'guide.pdf',
            str_pad($header, 64 * 1024, "\n"),
        );
    }
}
