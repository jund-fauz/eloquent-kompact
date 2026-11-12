<?php

namespace JundFauz\EloquentKompact\Column\Builder\Concerns;

use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\not_in_array;

class RelationBuilder
{
    use ErrorThrow;

    /** @var array<string|int, string> */
    protected static array $relation = [];

    public static function __callStatic(string $name, array $arguments)
    {
        $relation = static::$relation;
        $keys = collect($relation)->map(fn ($value, $key) => is_int($key) ? $value : $key)->toArray();
        if (not_in_array($name, $keys)) {
            self::methodNotFound($name);
        }

        return ColumnCreator::relation($relation[$name] ?? $name);
    }
}