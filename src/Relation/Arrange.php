<?php

namespace JundFauz\EloquentKompact\Relation;

use BadMethodCallException;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Convert;

/**
 * A relation class is a model class, a relation name, or [prefix => model class] for a prefixed relation
 *
 * @method static string relation(string $columnName, string|array ...$relationClasses)
 * @method static string relations(string $columnName, string|array ...$relationClasses)
 */
final class Arrange
{
    public static function __callStatic(string $name, array $arguments)
    {
        if (in_array($name, ['relation', 'relations'], true)) {
            $columnName = array_shift($arguments);
            $result = Str::of();
            foreach (self::flatten($arguments) as $relationClass) {
                $result = $result->add(is_array($relationClass) || class_exists($relationClass) ? Convert::modelToRelation($relationClass, $name === 'relations') : $relationClass, '.');
            }

            return (string) ($columnName
                ? $result->add($columnName)
                : $result->substr(0, -1));
        }

        throw new BadMethodCallException("Method $name not found.");
    }

    /**
     * Flattens the lists only, so a [prefix => model class] keeps its prefix
     */
    private static function flatten(array $relationClasses): array
    {
        $result = [];
        foreach ($relationClasses as $relationClass) {
            if (is_array($relationClass) && array_is_list($relationClass)) {
                array_push($result, ...self::flatten($relationClass));
            } else {
                $result[] = $relationClass;
            }
        }

        return $result;
    }
}
