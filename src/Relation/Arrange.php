<?php

namespace JundFauz\EloquentKompact\Relation;

use BadMethodCallException;
use Illuminate\Support\Arr;
use JundFauz\EloquentKompact\Column\Convert;
use function substr;

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
            $result = '';
            foreach (Arr::flatten($arguments) as $relationClass) {
                $result .= str_concat(class_exists($relationClass) ? Convert::modelToRelation($relationClass, $name === 'relations') : $relationClass, '.');
            }

            if ($columnName) {
                $result .= $columnName;
            } else {
                $result = substr($result, 0, -1);
            }

            return $result;
        }

        throw new BadMethodCallException("Method $name not found.");
    }
}
