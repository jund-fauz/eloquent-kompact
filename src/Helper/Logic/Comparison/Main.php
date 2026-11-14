<?php

namespace JundFauz\EloquentKompact;

/**
 * Whether the values are identical (===), after casting both with $cast when it is given
 *
 * @param mixed $firstValue
 * @param mixed $secondValue
 * @param string|null $cast A cast of Laravel, see cast_as()
 * @return bool
 */
function same_with(mixed $firstValue, mixed $secondValue, ?string $cast = null): bool
{
    return cast_as($firstValue, $cast) === cast_as($secondValue, $cast);
}

/**
 * Whether the value is identical (===) to one of the other values
 *
 * @param mixed $toCompareValue
 * @param mixed ...$anotherValues
 * @return bool
 */
function same_with_one_of(mixed $toCompareValue, mixed ...$anotherValues): bool
{
    return in_array($toCompareValue, $anotherValues, true);
}

/**
 * @experimental
 * @param mixed $value
 * @param mixed $ifNotNullValue
 * @return mixed|null
 */
function if_not_null(mixed $value, mixed $ifNotNullValue): mixed
{
    return is_not_null($value)
        ? $ifNotNullValue
        : null;
}
