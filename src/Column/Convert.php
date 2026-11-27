<?php

namespace JundFauz\EloquentKompact\Column;

use InvalidArgumentException;
use JundFauz\EloquentKompact\Builder\Str;
use function JundFauz\EloquentKompact\Array\key_values;
use function JundFauz\EloquentKompact\chain;
use function JundFauz\EloquentKompact\is_any_of;

final class Convert
{
    /**
     * Convert a boolean column name into translated `[falseLabel, trueLabel]` display strings.
     *
     * @param string $columnName
     * @return array{0: string, 1: string}
     */
    public static function booleanToString(string $columnName): array
    {
        $trueValue = __((string) ColumnCreator::getPlainColumnName($columnName)->ucfirst());
        $falseValue = __('Not :value', ['value' => (string) Str::of($trueValue)->lower()]);

        return [$falseValue, $trueValue];
    }

    /**
     * Convert hours into seconds.
     *
     * @param int $hour
     * @return int
     */
    public static function hourToSecond(int $hour): int
    {
        return $hour * 3600;
    }

    /**
     * Convert days into seconds.
     *
     * @param int $day
     * @return int
     */
    public static function dayToSecond(int $day): int
    {
        return self::hourToSecond($day * 24);
    }

    /**
     * Convert a model class name into a foreign-key relation column name, optionally prefixed.
     *
     * @param string $model
     * @param string|null $prefix
     * @return string
     */
    public static function modelToColumn(string $model, ?string $prefix = null): string
    {
        $result = chain(Str::of($model, true), 'classBasename', 'snake', 'relation');
        if ($prefix) {
            $result->addAtStart($prefix, '_');
        }

        return (string) $result;
    }

    /**
     * Convert a model class or `[prefix => model]` entry into a relation or table name.
     *
     * @param string|array<string, string> $model
     * @param bool $isPlural
     * @param bool $isSnakeCase
     * @return string
     * @throws InvalidArgumentException
     */
    public static function modelToRelation(string|array $model, bool $isPlural = false, bool $isSnakeCase = false): string
    {
        $prefix = null;
        if (is_array($model)) {
            if (is_any_of($model, 'not_count_one', 'list')) {
                throw new InvalidArgumentException('A prefixed model has to be [prefix => model class].');
            }
            [$prefix, $model] = key_values($model);
        }
        $baseName = Str::of($model, true)->classBasename();
        if ($prefix) {
            $baseName->addAtStart($prefix);
        }
        if ($isPlural) {
            $baseName->plural();
        }

        return (string) ($isSnakeCase
            ? $baseName->snake()
            : $baseName->camel());
    }
}
