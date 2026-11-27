<?php

namespace JundFauz\EloquentKompact;

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
