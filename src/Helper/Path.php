<?php

namespace JundFauz\EloquentKompact\Helper;

use function JundFauz\EloquentKompact\path_concat;

/**
 * @internal
 */
class Path
{
    /** @internal */
    public static function fileConfig(): string
    {
        return path_concat(self::root(), 'config', 'eloquent-kompact.php');
    }

    /** @internal */
    public static function lang(): string
    {
        return path_concat(self::root(), 'lang');
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }
}