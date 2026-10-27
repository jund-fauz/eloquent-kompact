<?php

if (! function_exists('is_not_null')) {
    function is_not_null($value): bool
    {
        return ! is_null($value);
    }
}

if (! function_exists('is_all_null')) {
    function is_all_null(mixed ...$values): bool
    {
        return array_all($values, fn (mixed $value) => is_null($value));
    }
}