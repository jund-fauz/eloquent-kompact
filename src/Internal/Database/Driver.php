<?php

namespace JundFauz\EloquentKompact\Internal\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Database driver inspection and SQL dialect dispatcher.
 *
 * @internal
 */
class Driver
{
    public const string PostgresSQL = 'pgsql';
    public const string MySQL = 'mysql';
    public const string MariaDB = 'mariadb';
    public const string SQLite = 'sqlite';

    /**
     * Get the name of the default database connection driver.
     *
     * @return string
     */
    public static function info(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Select the SQL fragment matching the active database driver, falling back to `$etc`.
     *
     * @param string $mysql
     * @param string $pgsql
     * @param string $sqlite
     * @param string $etc
     * @return string
     * @throws RuntimeException
     */
    public static function matcher(string $mysql = '', string $pgsql = '', string $sqlite = '', string $etc = ''): string
    {
        $driver = self::info();
        return match ($driver) {
            self::MySQL, self::MariaDB => $mysql ?: $etc,
            self::PostgresSQL => $pgsql ?: $etc,
            self::SQLite => $sqlite ?: $etc,
            default => $etc ?: throw new RuntimeException(__('eloquent-kompact::messages.driver_not_supported_yet', compact('driver')))
        };
    }
}