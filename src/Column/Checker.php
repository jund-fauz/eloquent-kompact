<?php

namespace JundFauz\EloquentKompact\Column;

use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;

/**
 * @method static bool isEnum(mixed $column)
 * @method static bool isArray(mixed $column)
 * @method static bool isGram(mixed $column)
 * @method static bool isPcs(mixed $column)
 * @method static bool isPercentage(mixed $column)
 * @method static bool isTimestamp(mixed $column)
 * @method static bool isBoolean(mixed $column)
 * @method static bool isDropdown(mixed $column)
 * @method static bool isRelation(mixed $column)
 */
final class Checker
{
    use ErrorThrow;

    public static function __callStatic(string $method, array $parameters)
    {
        if (Str::startsWith($method, 'is')) {
            $constantName = Str::substr($method, 2);

            if (defined(Creator::class.'::'.$constantName)) {
                [$column] = $parameters;

                return Str::contains($column?->name ?? $column, Creator::{$constantName});
            }
        }

        self::methodNotFound($method);
    }
}
