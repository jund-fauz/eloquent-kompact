<?php

if (! function_exists('is_not_null')) {
    function is_not_null($value): bool
    {
        return ! is_null($value);
    }
}