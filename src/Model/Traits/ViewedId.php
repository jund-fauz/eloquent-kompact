<?php

namespace JundFauz\EloquentKompact\Model\Traits;

trait ViewedId
{
    /**
     * Prefix configuration for the model's generated `viewed_id` column:
     * - `string` (e.g. `'ORD'`) prepends `'ORD-'` before the source column expression,
     * - `array` maps `CASE` conditions to SQL expressions via `RawQuery::switchCase()`,
     * - `null` omits any prefix and formats the source column expression directly.
     *
     * @return string|array<string|int, mixed>|null
     */
    public static function getPrefixId(): string|array|null
    {
        return null;
    }

    /**
     * Source column name used to compute the model's generated `viewed_id` column.
     * Returning `'id'` generates a `virtualAs` column, whereas any other column name generates a `storedAs` column.
     *
     * @return string
     */
    public static function getSourceColumnNameForViewedId(): string
    {
        return 'id';
    }
}
