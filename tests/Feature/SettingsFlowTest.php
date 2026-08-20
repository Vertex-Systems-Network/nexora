<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EnterpriseSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SettingsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_update_site_identity_and_regional_defaults(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $this->actingAs($admin)->put('/admin/settings', [
            'appName' => 'Nexora Workspace',
            'logoUrl' => '/media/nexora-logo.svg',
            'defaultTimezone' => 'Asia/Karachi',
            'defaultLocale' => 'ur',
            'theme' => 'system',
            'primary' => '#7C3AED',
            'density' => 'comfortable',
            'radius' => 'medium',
        ])->assertRedirect();

        self::assertSame('Nexora Workspace', EnterpriseSetting::query()->where('key', 'app.name')->value('value'));
        self::assertSame('/media/nexora-logo.svg', EnterpriseSetting::query()->where('key', 'app.logo_url')->value('value'));
        self::assertSame('Asia/Karachi', EnterpriseSetting::query()->where('key', 'app.default_timezone')->value('value'));
        self::assertSame('ur', EnterpriseSetting::query()->where('key', 'app.default_locale')->value('value'));
    }

    public function test_settings_reject_invalid_logo_timezone_and_locale(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $response = $this->actingAs($admin)->from('/admin/settings')->put('/admin/settings', [
            'appName' => 'Nexora',
            'logoUrl' => 'javascript:alert(1)',
            'defaultTimezone' => 'Mars/Olympus',
            'defaultLocale' => 'xx',
            'theme' => 'system',
            'primary' => '#7C3AED',
            'density' => 'comfortable',
            'radius' => 'medium',
        ]);

        $response->assertRedirect('/admin/settings');
        $response->assertSessionHasErrors(['logoUrl', 'defaultTimezone', 'defaultLocale']);
    }
}
