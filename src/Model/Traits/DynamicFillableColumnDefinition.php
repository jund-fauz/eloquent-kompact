<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Column\ColumnListing;

trait DynamicFillableColumnDefinition
{
    // INFO: DF is a short form of 'DynamicFillable'
    protected static ?array $DFCache = null;

    public function getFillable(): array
    {
        return static::$DFCache[static::class] ??= array_values(array_unique(array_merge(
            collect(ColumnListing::of($this->getTable()))
                ->reject(function ($column) {
                    foreach (config('fillable.excluded_columns_by_regex') as $exclude) {
                        if (preg_match($exclude, $column)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->values()
                ->all(),
            parent::getFillable(),
        )));
    }
}
