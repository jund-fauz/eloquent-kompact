<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Column\Convert;

trait RelationData
{
    /**
     * @deprecated since 0.78.0, use getVia instead
     * @param string $columnName
     * @param string ...$relationClasses
     * @return mixed
     */
    public function getDataFromRelation(string $columnName = '', string ...$relationClasses): mixed
    {
        return $this->getVia($columnName, ...$relationClasses);
    }

    /**
     * @deprecated since 0.78.0, use getManyVia instead
     * @param array $columnNames
     * @param string ...$relationClasses
     * @return array
     */
    public function getDatasFromRelation(array $columnNames, string ...$relationClasses): array
    {
        return $this->getManyVia($columnNames, ...$relationClasses);
    }

    public function getVia(string $columnName = '', string ...$relationClasses)
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
    public function getManyVia(array $columnNames, string ...$relationClasses): array
    {
        $result = [];
        foreach ($columnNames as $columnName) {
            $result[] = $this->getVia($columnName, ...$relationClasses);
        }
        return $result;
    }
}
