<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nx_security_scans') || ! Schema::hasColumn('nx_security_scans', 'error')) {
            return;
        }

        DB::table('nx_security_scans')
            ->whereNotNull('error')
            ->where('error', '<>', '')
            ->update([
                'error' => 'Sentinel scan failed. Historical raw diagnostic details were removed by Sentinel 2.0 privacy hardening; review retained server logs where available.',
            ]);
    }

    public function down(): void
    {
        // Intentionally irreversible: previously persisted raw exception text may contain secrets,
        // local filesystem paths or package-controlled diagnostic content and must not be restored.
    }
};
