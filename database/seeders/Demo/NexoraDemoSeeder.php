<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\AdminNotification;
use App\Models\Role;
use App\Models\User;
use App\Models\Document;
use App\Nexora\Documents\Contracts\DocumentRepositoryContract;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class NexoraDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => env('NEXORA_DEMO_ADMIN_EMAIL', 'admin@nexora.test')],
            [
                'name' => 'Nexora Admin',
                'email_verified_at' => now(),
                'password' => Hash::make(env('NEXORA_DEMO_ADMIN_PASSWORD', 'password')),
            ],
        );

        if ($role = Role::query()->where('slug', 'super-admin')->first()) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }

        AdminNotification::query()->updateOrCreate(
            ['user_id' => $admin->id, 'title' => 'Nexora demo workspace ready'],
            ['type' => 'success', 'message' => 'The deterministic demo workspace is ready for release-candidate verification.'],
        );

        if (Document::query()->count() === 0) {
            $documents = app(DocumentRepositoryContract::class);
            foreach ([
                ['title' => 'Nexora publishing foundation', 'slug' => 'nexora-publishing-foundation', 'excerpt' => 'A structured document demonstrating the universal document engine.'],
                ['title' => 'Editorial workflow draft', 'slug' => 'editorial-workflow-draft', 'excerpt' => 'A draft document ready for future Writer and Editorial modules.'],
                ['title' => 'Reusable content architecture', 'slug' => 'reusable-content-architecture', 'excerpt' => 'A neutral content record that future Blog, Books, CV and research types can extend.'],
            ] as $sample) {
                $documents->create($sample + ['type' => 'document', 'status' => 'draft'], $admin->id);
            }
        }

        $userRole = Role::query()->where('slug', 'user')->first();
        for ($index = 1; $index <= 12; $index++) {
            $user = User::query()->updateOrCreate(
                ['email' => sprintf('demo-user-%02d@nexora.test', $index)],
                [
                    'name' => sprintf('Demo User %02d', $index),
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ],
            );
            if ($userRole !== null) {
                $user->roles()->syncWithoutDetaching([$userRole->id]);
            }
        }
    }
}
