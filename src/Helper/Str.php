<?php

namespace JundFauz\EloquentKompact\Helper;

class Str
{
    public static function concat(string ...$strings): string
    {
        return implode('', $strings);
    }

    public static function ifTrue($condition, string $trueValue): string
    {
        return $condition ? $trueValue : '';
    }
}
