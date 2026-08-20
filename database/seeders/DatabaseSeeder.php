<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Core\NexoraCoreSeeder;
use Database\Seeders\Demo\NexoraDemoSeeder;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NexoraCoreSeeder::class);
        $this->call(NexoraDemoSeeder::class);
    }
}
