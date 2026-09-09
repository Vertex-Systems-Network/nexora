<?php

declare(strict_types=1);

use App\Nexora\Foundation\Database\PortableNullableUnique;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE = 'nx_commerce_products';
    private const GLOBAL_SKU = 'nx_commerce_products_sku_uq';
    private const GLOBAL_SLUG = 'nx_commerce_products_slug_unique';
    private const TENANT_SKU = 'nx_commerce_products_tenant_sku_uq';
    private const TENANT_SLUG = 'nx_commerce_products_tenant_slug_uq';

    public function up(): void
    {
        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->dropUnique(self::GLOBAL_SKU);
            $table->dropUnique(self::GLOBAL_SLUG);
        });

        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->unique(['tenant_id', 'slug'], self::TENANT_SLUG);
        });
        PortableNullableUnique::createScoped(self::TABLE, 'tenant_id', 'sku', self::TENANT_SKU);
    }

    public function down(): void
    {
        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->dropUnique(self::TENANT_SKU);
            $table->dropUnique(self::TENANT_SLUG);
        });

        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->unique('slug', self::GLOBAL_SLUG);
        });
        PortableNullableUnique::create(self::TABLE, 'sku', self::GLOBAL_SKU);
    }
};
