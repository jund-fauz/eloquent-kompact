<?php

namespace JundFauz\EloquentKompact\Column;

use JundFauz\EloquentKompact\Builder\Str;

final class Convert
{
    public static function booleanToString(string $columnName): array
    {
        $trueValue = __((string) Creator::getPlainColumnName($columnName)->ucfirst());
        $falseValue = __('Not :value', ['value' => (string) Str::of($trueValue)->lower()]);

        return [$falseValue, $trueValue];
    }

    public static function hourToSecond(int $hour): int
    {
        return $hour * 3600;
    }

    public static function dayToSecond(int $day): int
    {
        return self::hourToSecond($day * 24);
    }

    public static function modelToColumn(string $model, ?string $prefix = null): string
    {
        $result = Str::of($model)->classBasename()->snake()->relation();
        if ($prefix) {
            $result = $result->addAtStart($prefix, '_');
        }

        return (string) $result;
    }

    public static function modelToRelation(string $model, bool $isPlural = false, bool $isSnakeCase = false): string
    {
        $baseName = Str::of($model)->classBasename();
        if ($isPlural) {
            $baseName = $baseName->plural();
        }

        return (string) ($isSnakeCase
            ? $baseName->snake()
            : $baseName->camel());
    }
}
