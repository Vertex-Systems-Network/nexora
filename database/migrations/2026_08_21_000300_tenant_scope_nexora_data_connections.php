<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tenantColumnAddedHere = false;
        if (! Schema::hasColumn('nx_data_connections', 'tenant_id')) {
            $tenantColumnAddedHere = true;
            Schema::table('nx_data_connections', function (Blueprint $table): void {
                $table->uuid('tenant_id')
                    ->nullable()
                    ->index('nx_tenant_'.substr(hash('sha256', 'nx_data_connections'), 0, 12).'_idx');
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

        if ($tenantColumnAddedHere) {
            Schema::table('nx_data_connections', function (Blueprint $table): void {
                $table->foreign(
                    'tenant_id',
                    'nx_tenant_'.substr(hash('sha256', 'nx_data_connections'), 0, 12).'_fk',
                )->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            });
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
            $table->unique(['provider', 'name']);
        });
    }
};
