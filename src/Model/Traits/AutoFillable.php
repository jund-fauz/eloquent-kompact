<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Support\Str as SupportStr;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnCheck;
use JundFauz\EloquentKompact\Column\ColumnListing;
use function JundFauz\EloquentKompact\array_get;
use function JundFauz\EloquentKompact\not;
use function JundFauz\EloquentKompact\same_with;

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
                        if (SupportStr::contains($column, $value)) {
                            return true;
                        }
                    }

                    foreach (array_get($config['prefix'], []) as $value) {
                        if (SupportStr::startsWith($column, $value)) {
                            return true;
                        }
                    }

                    foreach (array_get($config['postfix'], []) as $value) {
                        if (SupportStr::endsWith($column, $value)) {
                            return true;
                        }
                    }

                    foreach (array_get($config['token'], []) as $token) {
                        if (ColumnCheck::{Str::of($token)->pascal()->addAtStart('is')->value()}($column)) {
                            return true;
                        }
                    }

                    foreach (array_get($config['regex'], []) as $exclude) {
                        if (preg_match($exclude, $column)) {
                            return true;
                        }
                    }

                    if (not(static::$includeTimestampColumn) && ColumnCheck::isTimestamp($column)) {
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
