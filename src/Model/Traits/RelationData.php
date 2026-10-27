<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Column\Convert;

trait RelationData
{
    public function getDataFromRelation(string $columnName = '', string ...$relationClasses)
    {
        $result = $this;
        foreach ($relationClasses as $relationClass) {
            $result = $result?->{Convert::modelToRelation($relationClass)};
        }

        return $columnName ? $result?->{$columnName} : $result;
    }

    /**
     * @experimental This method may change in a minor release.
     * @param array $columnNames
     * @param string ...$relationClasses
     * @return array
     */
    public function getDatasFromRelation(array $columnNames, string ...$relationClasses): array
    {
        $result = [];
        foreach ($columnNames as $columnName) {
            $result[] = $this->getDataFromRelation($columnName, ...$relationClasses);
        }
        return $result;
    }
}
