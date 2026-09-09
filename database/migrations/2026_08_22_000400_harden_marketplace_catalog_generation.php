<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nx_marketplace_sources') && ! Schema::hasColumn('nx_marketplace_sources', 'catalog_generation')) {
            Schema::table('nx_marketplace_sources', function (Blueprint $table): void {
                $table->uuid('catalog_generation')->nullable()->index('nx_marketplace_source_generation_idx');
            });
        }

        if (Schema::hasTable('nx_marketplace_catalog_items') && ! Schema::hasColumn('nx_marketplace_catalog_items', 'sync_generation')) {
            Schema::table('nx_marketplace_catalog_items', function (Blueprint $table): void {
                $table->uuid('sync_generation')->nullable()->index('nx_marketplace_item_generation_idx');
            });
        }

        // Historical catalog rows intentionally remain generation-null. A source must complete a fresh
        // Marketplace 2.0 synchronization before those rows can be displayed/staged again.
    }

    public function down(): void
    {
        if (Schema::hasTable('nx_marketplace_catalog_items') && Schema::hasColumn('nx_marketplace_catalog_items', 'sync_generation')) {
            Schema::table('nx_marketplace_catalog_items', function (Blueprint $table): void {
                $table->dropIndex('nx_marketplace_item_generation_idx');
                $table->dropColumn('sync_generation');
            });
        }

        if (Schema::hasTable('nx_marketplace_sources') && Schema::hasColumn('nx_marketplace_sources', 'catalog_generation')) {
            Schema::table('nx_marketplace_sources', function (Blueprint $table): void {
                $table->dropIndex('nx_marketplace_source_generation_idx');
                $table->dropColumn('catalog_generation');
            });
        }
    }
};
