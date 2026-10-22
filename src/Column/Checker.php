<?php

namespace JundFauz\EloquentKompact\Column;

use Illuminate\Support\Str;

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
    public static function __callStatic(string $method, array $parameters)
    {
        if (Str::startsWith($method, 'is')) {
            $constantName = Str::substr($method, 2);

            if (defined(Creator::class.'::'.$constantName)) {
                [$column] = $parameters;

                return Str::contains($column?->name ?? $column, Creator::{$constantName});
            }
        }

        throw new \BadMethodCallException(sprintf('Fungsi %s::%s tidak ada.', self::class, $method));
    }
}
