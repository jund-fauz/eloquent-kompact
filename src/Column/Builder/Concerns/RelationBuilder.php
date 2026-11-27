<?php

namespace JundFauz\EloquentKompact\Column\Builder\Concerns;

use BadMethodCallException;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;

/**
 * @method static string categorical()
 * @method static string viewed()
 */
class RelationBuilder
{
    use ErrorThrow;

    /** @var array<int, string> */
    private static array $defaultRelation = ['categorical', 'viewed'];

    /** @var array<string|int, string> */
    protected static array $relation = [];

    /**
     * Resolve a registered relation key into its foreign-key column name via `ColumnCreator::relation()`.
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return string
     * @throws BadMethodCallException When `$name` is not registered in `$defaultRelation` or `$relation`
     * @final
     */
    public static function __callStatic(string $name, array $arguments): string
    {
        $relation = array_merge(self::$defaultRelation, static::$relation);
        foreach ($relation as $key => $value) {
            if ($key === $name || (is_int($key) && $value === $name)) {
                return ColumnCreator::relation($relation[$name] ?? $value);
            }
        }

        self::methodNotFound($name);
    }
}