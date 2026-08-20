<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_concurrency_mutexes', function (Blueprint $table): void {
            $table->string('name', 160)->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_concurrency_mutexes');
    }
};
