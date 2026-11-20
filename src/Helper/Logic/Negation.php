<?php

namespace JundFauz\EloquentKompact;

use Illuminate\Support\Collection as SupportCollection;
use JundFauz\EloquentKompact\Builder\Collection;
use function JundFauz\EloquentKompact\Array\in;

/**
 * ! $condition
 *
 * @param bool $condition
 * @return bool
 */
function not(bool $condition): bool
{
    return ! $condition;
}

/**
 * ! is_null()
 *
 * @param mixed $value
 * @return bool
 */
function is_not_null(mixed $value): bool
{
    return ! is_null($value);
}

/**
 * ! same_with()
 *
 * @param mixed $firstValue
 * @param mixed $secondValue
 * @param string|null $cast A cast of Laravel, see cast_as()
 * @return bool
 */
function not_same_with(mixed $firstValue, mixed $secondValue, ?string $cast = null): bool
{
    return ! same_with($firstValue, $secondValue, $cast);
}

/**
 * ! in(), the needle is not in the array or the collection
 *
 * @deprecated Will be removed in 3.0. Use is_not('in', $needle, $haystack, $strict)
 * @param mixed $needle
 * @param array|SupportCollection|Collection $haystack
 * @param bool $strict
 * @return bool
 */
function not_in(mixed $needle, array|SupportCollection|Collection $haystack, bool $strict = false): bool
{
    return ! in($needle, $haystack, $strict);
}

/**
 * ! in_array()
 *
 * @deprecated Will be removed in 3.0. Use is_not('in_array', $needle, $haystack, $strict)
 * @param mixed $needle
 * @param array $haystack
 * @param bool $strict
 * @return bool
 */
function not_in_array(mixed $needle, array $haystack, bool $strict = false): bool
{
    return ! in_array($needle, $haystack, $strict);
}
