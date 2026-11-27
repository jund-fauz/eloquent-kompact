<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Support\Str as SupportStr;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnCheck;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Model\ModelCache;
use function JundFauz\EloquentKompact\Array\get;
use function JundFauz\EloquentKompact\not;
use function JundFauz\EloquentKompact\same_with;

trait AutoFillable
{
    protected static bool $includeTimestampColumn = false;

    /**
     * Fillable columns resolved from the table schema, filtered by config rules, and merged with explicit and custom fillables.
     *
     * @return array<int, string>
     */
    public function getFillable(): array
    {
        return ModelCache::$fillable[static::class] ??= $this->resolveAutoFillable();
    }

    /**
     * Computes the fillable column list for this model from the table schema and package configuration.
     *
     * @return array<int, string>
     */
    protected function resolveAutoFillable(): array
    {
        $config = config('eloquent-kompact.fillable', []);
        $includedColumnsFromConfig = get($config['include_explicitly'], []);
        $excludeConfig = get($config['exclude_column_by'], []);

        return collect(ColumnListing::of($this->getTable()))
                ->reject(function ($column) use ($excludeConfig) {
                    foreach (get($excludeConfig['exact'], []) as $value) {
                        if (same_with($column, $value)) {
                            return true;
                        }
                    }

                    foreach (get($excludeConfig['contain'], []) as $value) {
                        if (SupportStr::contains($column, $value)) {
                            return true;
                        }
                    }

                    foreach (get($excludeConfig['prefix'], []) as $value) {
                        if (SupportStr::startsWith($column, $value)) {
                            return true;
                        }
                    }

                    foreach (get($excludeConfig['postfix'], []) as $value) {
                        if (SupportStr::endsWith($column, $value)) {
                            return true;
                        }
                    }

                    foreach (get($excludeConfig['token'], []) as $token) {
                        if (ColumnCheck::{Str::of($token)->pascal()->addAtStart('is')->value()}($column)) {
                            return true;
                        }
                    }

                    foreach (get($excludeConfig['regex'], []) as $exclude) {
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
                ->merge(parent::getFillable())
                ->merge(static::getCustomFillable())
                ->merge(array_intersect($includedColumnsFromConfig, ColumnListing::of($this->getTable())))
                ->unique()
                ->values()
                ->all();
    }

    /**
     * Extra fillable attributes merged after `parent::getFillable()`.
     *
     * @return array<int, string>
     */
    public function getCustomFillable(): array
    {
        return [];
    }
}
