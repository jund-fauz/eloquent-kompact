<?php

namespace JundFauz\EloquentKompact\Internal\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * @internal
 */
class Driver
{
    public const string PostgresSQL = 'pgsql';
    public const string MySQL = 'mysql';
    public const string MariaDB = 'mariadb';
    public const string SQLite = 'sqlite';

    public static function info(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function matcher(string $mysql = '', string $pgsql = '', string $sqlite = '', string $etc = ''): string
    {
        $driver = self::info();
        return match ($driver) {
            self::MySQL, self::MariaDB => $mysql ?: $etc,
            self::PostgresSQL => $pgsql ?: $etc,
            self::SQLite => $sqlite ?: $etc,
            default => throw new RuntimeException(__('eloquent-kompact::messages.driver_not_supported_yet', compact('driver')))
        };
    }
}