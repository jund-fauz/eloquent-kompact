<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Query\RawQuery;

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
                $selects[] = RawQuery::create($column)
                    ->replaceIfNull(RawQuery::string())
                    ->count()
                    ->as("{$column}_count")
                    ->build();
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
