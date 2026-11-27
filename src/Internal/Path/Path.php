<?php

namespace JundFauz\EloquentKompact\Internal\Path;

use function JundFauz\EloquentKompact\path_concat;

/**
 * Package filesystem path resolver.
 *
 * @internal
 */
class Path
{
    /**
     * Get the absolute path to the package configuration file.
     *
     * @return string
     */
    public static function fileConfig(): string
    {
        return path_concat(self::root(), 'config', 'eloquent-kompact.php');
    }

    /**
     * Get the absolute path to the package language directory.
     *
     * @return string
     */
    public static function lang(): string
    {
        return path_concat(self::root(), 'lang');
    }

    /**
     * Get the root directory path of the package.
     *
     * @return string
     */
    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }
}