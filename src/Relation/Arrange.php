<?php

namespace JundFauz\EloquentKompact\Relation;

use JundFauz\EloquentKompact\Column\Convert;
use function substr;

final class Arrange
{
    public static function relation(string $columnName = '', ...$relationClasses): string
    {
        $result = '';
        foreach ($relationClasses as $relationClass) {
            $result .= Convert::modelToRelation($relationClass) . '.';
        }

        if ($columnName) {
            $result .= $columnName;
        } else {
            $result = substr($result, 0, -1);
        }

        return $result;
    }

    public static function relations(string $columnName = '', ...$relationClasses): string
    {
        $result = '';
        foreach ($relationClasses as $relationClass) {
            $result .= Convert::modelToRelation($relationClass, true) . '.';
        }

        if ($columnName) {
            $result .= $columnName;
        } else {
            $result = substr($result, 0, -1);
        }

        return $result;
    }
}
