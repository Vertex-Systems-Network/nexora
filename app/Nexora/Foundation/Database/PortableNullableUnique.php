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
        self::assertIdentifiers([$table, $column, $indexName], $indexName);

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $quotedTable = self::quoteSqlServer($table);
            $quotedColumn = self::quoteSqlServer($column);
            $quotedIndex = self::quoteSqlServer($indexName);
            DB::statement("CREATE UNIQUE INDEX {$quotedIndex} ON {$quotedTable} ({$quotedColumn}) WHERE {$quotedColumn} IS NOT NULL");
            return;
        }

        Schema::table($table, static function (Blueprint $blueprint) use ($column, $indexName): void {
            $blueprint->unique($column, $indexName);
        });
    }

    public static function createScoped(string $table, string $scopeColumn, string $column, string $indexName): void
    {
        self::assertIdentifiers([$table, $scopeColumn, $column, $indexName], $indexName);

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $quotedTable = self::quoteSqlServer($table);
            $quotedScope = self::quoteSqlServer($scopeColumn);
            $quotedColumn = self::quoteSqlServer($column);
            $quotedIndex = self::quoteSqlServer($indexName);
            DB::statement("CREATE UNIQUE INDEX {$quotedIndex} ON {$quotedTable} ({$quotedScope}, {$quotedColumn}) WHERE {$quotedColumn} IS NOT NULL");
            return;
        }

        Schema::table($table, static function (Blueprint $blueprint) use ($scopeColumn, $column, $indexName): void {
            $blueprint->unique([$scopeColumn, $column], $indexName);
        });
    }

    /** @param list<string> $identifiers */
    private static function assertIdentifiers(array $identifiers, string $indexName): void
    {
        foreach ($identifiers as $identifier) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
                throw new InvalidArgumentException('Portable nullable unique identifiers must contain only letters, numbers and underscores.');
            }
        }
        if (strlen($indexName) > 63) {
            throw new InvalidArgumentException('Portable nullable unique index names must be at most 63 characters.');
        }
    }

    private static function quoteSqlServer(string $identifier): string
    {
        return '['.str_replace(']', ']]', $identifier).']';
    }
}
