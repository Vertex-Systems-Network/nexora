<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('nx_data_connections', 'tenant_id')) {
            Schema::table('nx_data_connections', function (Blueprint $table): void {
                $table->uuid('tenant_id')->nullable()->index('nx_data_conn_tenant_idx');
            });
        }

        $defaultTenantId = DB::table('nx_enterprise_organizations')
            ->where('is_default', true)
            ->value('id');
        if (is_string($defaultTenantId) && $defaultTenantId !== '') {
            DB::table('nx_data_connections')
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $defaultTenantId]);
        }

        DB::table('nx_data_connections')
            ->whereNotNull('endpoint')
            ->orderBy('id')
            ->each(function (object $connection): void {
                $endpoint = trim((string) ($connection->endpoint ?? ''));
                if ($endpoint === '') return;
                $sanitized = preg_replace(
                    '#^([a-z][a-z0-9+.-]*://)([^/@\s]+)@#i',
                    '$1',
                    $endpoint,
                );
                if (! is_string($sanitized) || $sanitized === $endpoint) return;

                DB::table('nx_data_connections')->where('id', $connection->id)->update([
                    'endpoint' => $sanitized,
                    'status' => 'credential-rotation-required',
                    'is_enabled' => false,
                    'last_tested_at' => null,
                    'last_error' => 'Embedded endpoint credentials were removed during the secure tenancy upgrade. Re-enter credentials in encrypted fields and test again.',
                    'updated_at' => now(),
                ]);
            });

        Schema::table('nx_data_connections', function (Blueprint $table): void {
            $table->dropUnique(['provider', 'name']);
            $table->foreign('tenant_id', 'nx_data_conn_tenant_fk')
                ->references('id')
                ->on('nx_enterprise_organizations')
                ->nullOnDelete();
            $table->unique(
                ['tenant_id', 'provider', 'name'],
                'nx_data_conn_tenant_provider_name_uq',
            );
        });
    }

    public function down(): void
    {
        Schema::table('nx_data_connections', function (Blueprint $table): void {
            $table->dropUnique('nx_data_conn_tenant_provider_name_uq');
            $table->dropForeign('nx_data_conn_tenant_fk');
            $table->dropIndex('nx_data_conn_tenant_idx');
            $table->dropColumn('tenant_id');
            $table->unique(['provider', 'name']);
        });
    }
};
