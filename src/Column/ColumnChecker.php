<?php

namespace JundFauz\EloquentKompact\Column;

use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\str_constant;

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
 * @method static bool isTime(mixed $column)
 * @method static bool isType(mixed $column)
 */
final class ColumnChecker
{
    use ErrorThrow;

    public static function __callStatic(string $method, array $parameters)
    {
        $method = Str::of($method);
        if ($method->startsWith('is')) {
            $constantName = $method->substr(2);
            $customToken = ColumnCreator::getCustomToken($constantName);

            [$column] = $parameters;
            return same_with(ColumnCreator::getColumnType($column?->name ?? $column), match (true) {
                (bool) $customToken => $customToken,
                $constantName->addConstantAtStart(ColumnCreator::class)->isDefined() => ColumnCreator::{$constantName->value()},
                default => self::methodNotFound($method)
            });
        }

        self::methodNotFound($method);
    }
}
