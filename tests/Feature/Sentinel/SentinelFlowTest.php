<?php

declare(strict_types=1);

namespace Tests\Feature\Sentinel;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

final class SentinelFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_safe_package_is_quarantined_scanned_and_allowed_without_extraction(): void
    {
        $admin = $this->admin();
        $zipPath = $this->packageZip('safe', [
            'nexora.json' => json_encode(['schema' => '1.0', 'id' => 'acme.safe', 'name' => 'Safe', 'type' => 'extension', 'version' => '1.0.0', 'capabilities' => []], JSON_THROW_ON_ERROR),
            'src/Safe.php' => "<?php\nnamespace Acme; final class Safe {}\n",
        ]);

        $response = $this->actingAs($admin)->post('/admin/security/sentinel', [
            'package' => new UploadedFile($zipPath, 'safe.zip', 'application/zip', null, true),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nx_security_scans', ['source_name' => 'safe.zip', 'decision' => 'allow', 'risk_score' => 0]);
        $this->assertDatabaseHas('nx_quarantine_packages', ['original_name' => 'safe.zip', 'status' => 'scanned']);
        self::assertDirectoryDoesNotExist(base_path('extensions/acme.safe'));
    }

    public function test_malicious_package_is_blocked_with_exact_security_rules(): void
    {
        $admin = $this->admin();
        $zipPath = $this->packageZip('malicious', [
            'nexora.json' => json_encode(['schema' => '1.0', 'id' => 'acme.bad', 'name' => 'Bad', 'type' => 'extension', 'version' => '1.0.0', 'capabilities' => []], JSON_THROW_ON_ERROR),
            'src/Backdoor.php' => "<?php\n\$payload = base64_decode(\$_POST['x']);\neval(\$payload);\n",
        ]);

        $this->actingAs($admin)->post('/admin/security/sentinel', [
            'package' => new UploadedFile($zipPath, 'bad.zip', 'application/zip', null, true),
        ])->assertRedirect();

        $this->assertDatabaseHas('nx_security_scans', ['source_name' => 'bad.zip', 'decision' => 'block']);
        $this->assertDatabaseHas('nx_security_findings', ['rule_id' => 'NEX-PHP-0000', 'file_path' => 'src/Backdoor.php', 'line_start' => 3, 'hard_block' => true]);
        $this->assertDatabaseHas('nx_quarantine_packages', ['original_name' => 'bad.zip', 'status' => 'quarantined']);
    }

    public function test_path_traversal_entry_is_hard_blocked(): void
    {
        $admin = $this->admin();
        $zipPath = $this->packageZip('traversal', [
            'nexora.json' => json_encode(['schema' => '1.0', 'id' => 'acme.traversal', 'name' => 'Traversal', 'type' => 'extension', 'version' => '1.0.0', 'capabilities' => []], JSON_THROW_ON_ERROR),
            '../escape.php' => '<?php echo "escape";',
        ]);

        $this->actingAs($admin)->post('/admin/security/sentinel', [
            'package' => new UploadedFile($zipPath, 'traversal.zip', 'application/zip', null, true),
        ])->assertRedirect();

        $this->assertDatabaseHas('nx_security_findings', ['rule_id' => 'NEX-ARC-0012', 'hard_block' => true]);
        $this->assertDatabaseHas('nx_security_scans', ['source_name' => 'traversal.zip', 'decision' => 'block']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        return $admin;
    }

    /** @param array<string,string> $entries */
    private function packageZip(string $name, array $entries): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-'.$name.'-'.bin2hex(random_bytes(4)).'.zip';
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($entries as $entry => $contents) {
            self::assertTrue($zip->addFromString($entry, $contents));
        }
        self::assertTrue($zip->close());

        return $path;
    }
}
