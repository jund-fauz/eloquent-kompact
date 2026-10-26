<?php

namespace JundFauz\EloquentKompact\Helper;

class Path
{
    public static function concat(string ...$folderOrPackages): string
    {
        return join('/', $folderOrPackages);
    }

    public static function namespaceConcat(string ...$folderOrPackages): string
    {
        return join('\\', array_map(fn (string $part) => trim($part, '\\'), $folderOrPackages));
    }

    /** @internal */
    public static function fileConfig(): string
    {
        return self::concat(self::root(), 'config', 'eloquent-kompact.php');
    }

    /** @internal */
    public static function lang(): string
    {
        return self::concat(self::root(), 'lang');
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }
}