<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class PortableNullableUnique
{
    public static function create(string $table, string $column, string $indexName): void
    {
        foreach ([$table, $column, $indexName] as $identifier) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
                throw new InvalidArgumentException('Portable nullable unique identifiers must contain only letters, numbers and underscores.');
            }
        }
        if (strlen($indexName) > 63) {
            throw new InvalidArgumentException('Portable nullable unique index names must be at most 63 characters.');
        }

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $quotedTable='['.str_replace(']', ']]', $table).']';
            $quotedColumn='['.str_replace(']', ']]', $column).']';
            $quotedIndex='['.str_replace(']', ']]', $indexName).']';
            DB::statement("CREATE UNIQUE INDEX {$quotedIndex} ON {$quotedTable} ({$quotedColumn}) WHERE {$quotedColumn} IS NOT NULL");
            return;
        }

        Schema::table($table, static function (Blueprint $blueprint) use ($column, $indexName): void {
            $blueprint->unique($column, $indexName);
        });
    }
}
