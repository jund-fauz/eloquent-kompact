<?php

namespace JundFauz\EloquentKompact\Helper;

/**
 * @deprecated since 0.22.0, use StrB class instead
 */
class Str extends \Illuminate\Support\Str
{
    /**
     * @deprecated since 0.22.0, use concat() in StrB instead
     * @param string ...$strings
     * @return string
     */
    public static function concat(string ...$strings): string
    {
        return implode('', $strings);
    }

    /**
     * @deprecated since 0.22.0, use emptyIfFalse() in StrB instead
     * @param $condition
     * @param string $trueValue
     * @return string
     */
    public static function ifTrue($condition, string $trueValue): string
    {
        return $condition ? $trueValue : '';
    }
}
