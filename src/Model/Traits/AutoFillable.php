<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Column\ColumnListing;

trait AutoFillable
{
    // INFO: AF is a short form of 'AutoFillable'
    protected static ?array $AFCache = null;

    public function getFillable(): array
    {
        return static::$AFCache[static::class] ??= array_values(array_unique(array_merge(
            collect(ColumnListing::of($this->getTable()))
                ->reject(function ($column) {
                    foreach (config('eloquent-kompact.fillable.excluded_columns_by_regex') as $exclude) {
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
