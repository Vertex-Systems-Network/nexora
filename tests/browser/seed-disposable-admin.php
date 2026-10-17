<?php

declare(strict_types=1);

// CI-only fixture setup. Refuse real targets and all unknown database names.
if (getenv('APP_ENV') !== 'testing'
    || getenv('NEXORA_INSTALLER_BYPASS') !== 'true'
    || getenv('NEXORA_CI_BROWSER_E2E') !== '1'
    || getenv('DB_DATABASE') !== 'nexora_testing') {
    fwrite(STDERR, "Disposable browser seeder: environment guard rejected.\n");
    exit(2);
}
$email = getenv('NEXORA_CI_BROWSER_EMAIL');
$password = getenv('NEXORA_CI_BROWSER_PASSWORD');
if (!is_string($email) || !str_ends_with($email, '@nexora.invalid')
    || !is_string($password) || strlen($password) < 24) {
    fwrite(STDERR, "Disposable browser seeder: credential contract rejected.\n");
    exit(2);
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing')
    || \Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'nexora_testing'
    || !config('installer.bypass')) {
    fwrite(STDERR, "Disposable browser seeder: resolved runtime guard rejected.\n");
    exit(2);
}
\Illuminate\Support\Facades\Artisan::call('db:seed', [
    '--class' => \Database\Seeders\Core\NexoraCoreSeeder::class,
    '--force' => true,
]);
$role = \App\Models\Role::query()->where('slug', 'super-admin')->firstOrFail();
$user = \App\Models\User::query()->updateOrCreate(
    ['email' => $email],
    [
        'name' => 'Disposable Browser QA Admin',
        'password' => $password,
        'status' => 'active',
        'email_verified_at' => now(),
    ]
);
$user->forceFill(['email_verified_at' => now()])->save();
$user->roles()->syncWithoutDetaching([$role->id]);
echo "Disposable CI browser Super Admin provisioned.\n";
