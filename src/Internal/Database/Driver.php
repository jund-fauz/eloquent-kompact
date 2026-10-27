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

    public static function info(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function matcher(string $mysql, string $pgsql): string
    {
        $driver = self::info();
        return match ($driver) {
            self::MySQL, self::MariaDB => $mysql,
            self::PostgresSQL => $pgsql,
            default => throw new RuntimeException(__('eloquent-kompact::messages.driver_not_supported_yet', compact('driver')))
        };
    }
}