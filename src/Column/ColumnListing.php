<?php

namespace JundFauz\EloquentKompact\Column;

use Cache;
use Illuminate\Cache\Repository;
use Schema;

final class ColumnListing
{
    /** @var array<string, array<int, string>> */
    private static array $columns = [];

    public static function of(string $table): array
    {
        return self::$columns[$table] ??= self::store()->rememberForever(
            self::key($table),
            fn () => Schema::getColumnListing($table)
        );
    }

    public static function has(string $table, string $column): bool
    {
        return in_array(strtolower($column), array_map(strtolower(...), self::of($table)), true);
    }

    public static function flush(): void
    {
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            self::store()->forget(self::key($table));
        }

        self::$columns = [];
    }

    private static function key(string $table): string
    {
        return 'schema_columns:' . Schema::getConnection()->getDatabaseName() . ":$table";
    }

    private static function store(): Repository
    {
        return Cache::store('file');
    }
}
