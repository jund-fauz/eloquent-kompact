<?php

namespace JundFauz\EloquentKompact\Column;

use InvalidArgumentException;
use JundFauz\EloquentKompact\Builder\Str;
use function JundFauz\EloquentKompact\array_key_values;

final class Convert
{
    public static function booleanToString(string $columnName): array
    {
        $trueValue = __((string) ColumnCreator::getPlainColumnName($columnName)->ucfirst());
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

    public static function modelToRelation(string|array $model, bool $isPlural = false, bool $isSnakeCase = false): string
    {
        $prefix = null;
        if (is_array($model)) {
            if (count($model) !== 1 || array_is_list($model)) {
                throw new InvalidArgumentException('A prefixed model has to be [prefix => model class].');
            }
            [$prefix, $model] = array_key_values($model);
        }
        $baseName = Str::of($model)->classBasename();
        if ($prefix) {
            $baseName = $baseName->addAtStart($prefix);
        }
        if ($isPlural) {
            $baseName = $baseName->plural();
        }

        return (string) ($isSnakeCase
            ? $baseName->snake()
            : $baseName->camel());
    }
}
