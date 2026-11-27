<?php

namespace JundFauz\EloquentKompact\Column;

use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use function JundFauz\EloquentKompact\schema_concat;
use function JundFauz\EloquentKompact\String\str;

final class ColumnListing
{
    /** @var array<string, array<int, string>> */
    private static array $columns = [];

    /**
     * Retrieve and cache the column names of the given database table.
     *
     * @param string $table
     * @return array<int, string>
     */
    public static function of(string $table): array
    {
        return self::$columns[$table] ??= self::store()->rememberForever(
            self::key($table),
            fn () => Schema::getColumnListing($table)
        );
    }

    /**
     * Determine whether the given table contains a column (case-insensitively).
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function has(string $table, string $column): bool
    {
        return in_array(str('lower', $column), str('lowers', ...self::of($table)), true);
    }

    /**
     * Flush both the file-cached and in-memory column listings for all tables.
     *
     * @return void
     */
    public static function flush(): void
    {
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            self::store()->forget(self::key($table));
        }

        self::$columns = [];
    }

    /**
     * Build the cache key for storing a table's column listing.
     *
     * @param string $table
     * @return string
     */
    private static function key(string $table): string
    {
        return schema_concat('schema_columns', Schema::getConnection()->getDatabaseName(), "$table");
    }

    /**
     * Get the file cache repository used for persisting table column listings.
     *
     * @return Repository
     */
    private static function store(): Repository
    {
        return Cache::store('file');
    }
}
