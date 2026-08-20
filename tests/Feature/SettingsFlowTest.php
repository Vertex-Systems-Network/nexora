<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SettingsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_appearance_settings(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        $this->actingAs($user)->put('/admin/settings', [
            'appName' => 'Nexora Studio',
            'theme' => 'dark',
            'primary' => '#6D28D9',
            'density' => 'compact',
            'radius' => 'large',
        ])->assertSessionHasNoErrors();

        self::assertSame('Nexora Studio', Setting::query()->where('key', 'app.name')->value('value'));
        self::assertSame('dark', Setting::query()->where('key', 'appearance.theme')->value('value'));
    }
}
