<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Model\KompactModel;

trait Filterable
{
    public static function getCountForFilter(string ...$columns): ?KompactModel
    {
        $selects = [];
        $relationalColumns = [];

        foreach ($columns as $column) {
            if (str_contains($column, '.')) {
                $relationalColumns[] = $column;
            } else {
                $selects[] = "COUNT(NULLIF($column, '')) as {$column}_count";
            }
        }

        $result = empty($selects) ? new static : static::selectRaw(implode(', ', $selects))->first();

        if ($result) {
            foreach ($relationalColumns as $relationalColumn) {
                $result->{"{$relationalColumn}_count"} = 1;
            }
        }

        return $result;
    }
}
