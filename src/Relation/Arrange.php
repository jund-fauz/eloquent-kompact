<?php

namespace JundFauz\EloquentKompact\Relation;

use BadMethodCallException;
use Illuminate\Support\Arr;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Convert;

/**
 * @method static string relation(string $columnName, string ...$relationClasses)
 * @method static string relations(string $columnName, string ...$relationClasses)
 */
final class Arrange
{
    public static function __callStatic(string $name, array $arguments)
    {
        if (in_array($name, ['relation', 'relations'], true)) {
            $columnName = array_shift($arguments);
            $result = Str::of();
            foreach (Arr::flatten($arguments) as $relationClass) {
                $result = $result->add(class_exists($relationClass) ? Convert::modelToRelation($relationClass, $name === 'relations') : $relationClass, '.');
            }

            return (string) ($columnName
                ? $result->add($columnName)
                : $result->substr(0, -1));
        }

        throw new BadMethodCallException("Method $name not found.");
    }
}
