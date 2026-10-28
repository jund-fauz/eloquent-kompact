<?php

namespace JundFauz\EloquentKompact\Helper;

/**
 * @deprecated since 0.27.0, use Compare::of()->sameWith() instead
 * @param mixed $firstValue
 * @param mixed $secondValue
 * @return bool
 */
function same_with(mixed $firstValue, mixed $secondValue): bool
{
    return $firstValue === $secondValue;
}