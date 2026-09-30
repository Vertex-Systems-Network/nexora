<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions;

use App\Models\Extension;
use App\Models\Role;
use App\Models\SecurityScan;
use App\Models\SupplyChainArtifact;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

final class ExtensionsAdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/nexora/extensions/acme.e2e-extension'));
        parent::tearDown();
    }

    public function test_administrator_can_open_the_extensions_workspace(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));

        $this->actingAs($admin)->get('/admin/extensions')->assertOk();
    }

    public function test_non_authenticated_user_cannot_open_the_extensions_workspace(): void
    {
        $this->get('/admin/extensions')->assertRedirect('/login');
    }

    public function test_declarative_extension_moves_from_sentinel_to_install_enable_disable_and_uninstall(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));
        $zipPath=$this->extensionZip();
        $quarantinePath=null;

        try {
            $this->actingAs($admin)->post('/admin/security/sentinel', [
                'package'=>new UploadedFile($zipPath,'acme-e2e-extension.zip','application/zip',null,true),
            ])->assertRedirect();

            $scan=SecurityScan::query()->where('source_name','acme-e2e-extension.zip')->firstOrFail();
            self::assertSame('allow',$scan->decision);
            self::assertSame('completed',$scan->status);
            $quarantinePath=$scan->quarantinePackage?->path;

            $artifact=SupplyChainArtifact::query()->where('scan_id',$scan->id)->firstOrFail();
            self::assertSame('acme.e2e-extension',$artifact->package_identifier);
            self::assertNotSame('',$artifact->content_sha256);

            $this->actingAs($admin)
                ->post('/admin/extensions/install/'.$artifact->id)
                ->assertSessionHasNoErrors();

            $extension=Extension::query()->where('identifier','acme.e2e-extension')->firstOrFail();
            self::assertSame('installed',$extension->status);
            $version=$extension->versions()->where('version','1.0.0')->firstOrFail();
            self::assertDirectoryExists($version->install_path);

            $this->actingAs($admin)
                ->post('/admin/extensions/'.$extension->id.'/enable')
                ->assertSessionHasNoErrors();
            self::assertSame('enabled',$extension->fresh()->status);
            self::assertSame('1.0.0',$extension->fresh()->current_version);

            $this->actingAs($admin)
                ->post('/admin/extensions/'.$extension->id.'/disable')
                ->assertSessionHasNoErrors();
            self::assertSame('disabled',$extension->fresh()->status);

            $this->actingAs($admin)
                ->delete('/admin/extensions/'.$extension->id)
                ->assertSessionHasNoErrors();
            self::assertSame('uninstalled',$extension->fresh()->status);
            self::assertDirectoryDoesNotExist($version->install_path);
            $this->assertDatabaseHas('nx_extension_lifecycle_events',['extension_id'=>$extension->id,'event'=>'uninstalled','status'=>'completed']);
        } finally {
            @unlink($zipPath);
            if(is_string($quarantinePath)&&$quarantinePath!=='') @unlink($quarantinePath);
        }
    }

    private function extensionZip(): string
    {
        $path=sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-extension-e2e-'.bin2hex(random_bytes(4)).'.zip';
        $zip=new ZipArchive();
        self::assertTrue($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE));
        $manifest=[
            'schema'=>'1.0',
            'id'=>'acme.e2e-extension',
            'name'=>'Acme E2E Extension',
            'type'=>'extension',
            'version'=>'1.0.0',
            'description'=>'Declarative end-to-end extension acceptance fixture.',
            'requires'=>['nexora'=>'*'],
            'runtime'=>['mode'=>'declarative'],
            'capabilities'=>[],
            'dependencies'=>(object)[],
            'migrations'=>['policy'=>'none','schema_compatible_rollback'=>true],
        ];
        self::assertTrue($zip->addFromString('nexora.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)));
        self::assertTrue($zip->addFromString('extension.json',json_encode(['kind'=>'declarative','entry'=>'none'],JSON_THROW_ON_ERROR)));
        self::assertTrue($zip->addFromString('README.md','# Acme E2E Extension'));
        self::assertTrue($zip->close());
        return $path;
    }
}
