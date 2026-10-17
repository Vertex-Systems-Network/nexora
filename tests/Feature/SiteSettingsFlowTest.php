<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SiteSettingsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_settings_pages_render_for_administrator(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->get('/admin/settings')->assertOk();
        $this->actingAs($admin)->get('/admin/seo/settings')->assertOk();
    }

    public function test_appearance_settings_persist_normalized_and_audited(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->from('/admin/settings')->put('/admin/settings', $this->settingsPayload([
            'theme' => 'dark', 'primary' => '#7c3aed', 'density' => 'compact', 'radius' => 'large',
        ]))->assertRedirect('/admin/settings')->assertSessionHasNoErrors();

        self::assertSame('dark', $this->setting('appearance.theme'));
        self::assertSame('#7C3AED', $this->setting('appearance.primary'));
        self::assertSame('compact', $this->setting('appearance.density'));
        self::assertSame('large', $this->setting('appearance.radius'));
        $this->assertDatabaseHas('nx_audit_logs', ['user_id' => $admin->id, 'event' => 'settings.updated']);
    }

    public function test_invalid_appearance_values_are_rejected_without_writes(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->from('/admin/settings')->put('/admin/settings', $this->settingsPayload([
            'theme' => 'neon', 'primary' => 'red', 'density' => 'tiny', 'radius' => 'huge',
        ]))->assertRedirect('/admin/settings')
            ->assertSessionHasErrors(['theme', 'primary', 'density', 'radius']);

        self::assertNotSame('neon', $this->setting('appearance.theme'));
        $this->assertDatabaseMissing('nx_audit_logs', ['event' => 'settings.updated']);
    }

    public function test_seo_site_identity_persists_and_rejects_unsafe_urls(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->from('/admin/seo/settings')->put('/admin/seo/settings', [
            'site_name' => 'Nexora', 'organization_name' => 'VSN',
            'organization_url' => 'javascript:alert(1)', 'organization_logo' => 'ftp://example.test/logo.png',
        ])->assertSessionHasErrors(['organization_url', 'organization_logo']);
        $this->assertDatabaseMissing('nx_audit_logs', ['event' => 'seo.settings.updated']);

        $this->actingAs($admin)->from('/admin/seo/settings')->put('/admin/seo/settings', [
            'site_name' => 'Nexora', 'organization_name' => 'VSN',
            'organization_url' => 'https://example.test', 'organization_logo' => 'https://example.test/logo.png',
        ])->assertRedirect('/admin/seo/settings')->assertSessionHasNoErrors();

        self::assertSame('Nexora', $this->setting('seo.site_name'));
        self::assertSame('https://example.test', $this->setting('seo.organization_url'));
        $this->assertDatabaseHas('nx_audit_logs', ['user_id' => $admin->id, 'event' => 'seo.settings.updated']);
    }

    public function test_user_without_admin_access_cannot_change_settings(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', 'user')->value('id'));

        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
        $this->actingAs($user)->put('/admin/settings', $this->settingsPayload(['appName' => 'Hijacked']))->assertForbidden();
        $this->actingAs($user)->put('/admin/seo/settings', ['site_name' => 'Hijacked'])->assertForbidden();

        self::assertNotSame('Hijacked', $this->setting('app.name'));
        self::assertNotSame('Hijacked', $this->setting('seo.site_name'));
    }

    /** @param array<string, string> $overrides */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'appName' => 'Nexora', 'logoUrl' => '', 'defaultTimezone' => 'UTC', 'defaultLocale' => 'en',
            'theme' => 'system', 'primary' => '#7C3AED', 'density' => 'comfortable', 'radius' => 'medium',
        ], $overrides);
    }

    private function setting(string $key): mixed
    {
        $organization = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();

        return EnterpriseSetting::query()->where('organization_id', $organization->id)->where('key', $key)->value('value');
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        return $admin;
    }
}
