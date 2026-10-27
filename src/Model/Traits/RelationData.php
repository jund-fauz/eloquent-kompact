<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Column\Convert;

trait RelationData
{
    public function getDataFromRelation(string $columnName = '', ...$relationClasses)
    {
        $result = $this;
        foreach ($relationClasses as $relationClass) {
            $result = $result?->{Convert::modelToRelation($relationClass)};
        }

        return $columnName ? $result?->{$columnName} : $result;
    }

    public function getDatasFromRelation(array $columnNames, ...$relationClasses): array
    {
        $result = [];
        foreach ($columnNames as $columnName) {
            $result[] = $this->getDataFromRelation($columnName, ...$relationClasses);
        }
        return $result;
    }
}
