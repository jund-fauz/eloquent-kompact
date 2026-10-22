<?php

namespace JundFauz\EloquentKompact\Column;

use Illuminate\Support\Str;

final class Convert
{
    public static function booleanToString(string $columnName): array
    {
        $trueValue = __(Str::ucfirst(Creator::getPlainColumnName($columnName)));
        $falseValue = __('Not :value', ['value' => Str::lower($trueValue)]);

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
        $result = Creator::relation(Str::snake(class_basename($model)));
        if ($prefix) {
            $result = "{$prefix}_$result";
        }

        return $result;
    }

    public static function modelToRelation(string $model, bool $isPlural = false, bool $isSnakeCase = false): string
    {
        $baseName = class_basename($model);
        if ($isPlural) {
            $baseName = Str::plural($baseName);
        }

        return $isSnakeCase ? Str::snake($baseName) : Str::camel($baseName);
    }
}
