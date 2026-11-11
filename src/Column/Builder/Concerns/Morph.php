<?php

namespace JundFauz\EloquentKompact\Column\Builder\Concerns;

use BadMethodCallException;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use ReflectionClass;

class Morph
{
    /**
     * Resolve `{prefix}Id()` and `{prefix}Model()` calls, where the prefix is either
     * a constant name (e.g. `attributeId`) or its morph name (e.g. `sourceableId`).
     *
     * @param  array<int, mixed>  $arguments
     */
    public static function __callStatic(string $name, array $arguments): string
    {
        if (preg_match('/^(.+)(Id|Model)$/', $name, $matches)) {
            [, $prefix, $suffix] = $matches;

            $constants = (new ReflectionClass(static::class))->getConstants();
            $morphName = $constants[$prefix] ?? (in_array($prefix, $constants, true) ? $prefix : null);

            if ($morphName !== null) {
                return $suffix === 'Id' ? ColumnCreator::relation($morphName) : ColumnCreator::type($morphName);
            }
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }
}
