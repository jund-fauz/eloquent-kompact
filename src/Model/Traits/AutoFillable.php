<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Column\ColumnCheck;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Helper\Arr;
use function JundFauz\EloquentKompact\array_get;
use function JundFauz\EloquentKompact\null_coalescing;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\str_concat;

trait AutoFillable
{
    // INFO: AF is a short form of 'AutoFillable'
    protected static ?array $AFCache = null;
    protected static bool $includeTimestampColumn = false;

    public function getFillable(): array
    {
        $config = config('eloquent-kompact.fillable.exclude_column_by', []);
        return static::$AFCache[static::class] ??= array_values(array_unique(array_merge(
            collect(ColumnListing::of($this->getTable()))
                ->reject(function ($column) use ($config) {
                    foreach (array_get($config['exact'], []) as $value) {
                        if (same_with($column, $value)) {
                            return true;
                        }
                    }

                    foreach (array_get($config['contain'], []) as $value) {
                        if (Str::contains($column, $value)) {
                            return true;
                        }
                    }

                    foreach (array_get($config['token'], []) as $token) {
                        if (ColumnCheck::{str_concat('is', Str::pascal($token))}($column)) {
                            return true;
                        }
                    }

                    foreach (array_get($config['regex'], []) as $exclude) {
                        if (preg_match($exclude, $column)) {
                            return true;
                        }
                    }

                    if (! static::$includeTimestampColumn && ColumnCheck::isTimestamp($column)) {
                        return true;
                    }

                    return false;
                })
                ->values()
                ->all(),
            parent::getFillable(),
        )));
    }
}
