<?php

namespace JundFauz\EloquentKompact\Query;

use Illuminate\Support\Facades\DB;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Helper\Str;
use JundFauz\EloquentKompact\Query\Driver\Driver;
use function array_is_list;
use function array_shift;
use function is_string;
use function join;
use function substr;

/**
 * NOTE: Built to compatible with MySQL & PostgresSQL database
 */
final class RawQuery
{
    public const string CHAR = 'CHAR';
    public const string NULL = 'NULL';

    public static function zeroIfNull(string ...$columnNames): string|array
    {
        $result = array_map(fn (string $columnName) => "COALESCE($columnName, 0)", $columnNames);
        if (count($result) === 1) {
            $result = $result[0];
        }
        return $result;
    }

    public static function nullIfZero(string ...$columnNames): string|array
    {
        $result = array_map(fn (string $columnName) => "NULLIF($columnName, 0)", $columnNames);
        if (count($result) === 1) {
            $result = $result[0];
        }
        return $result;
    }

    public static function isZeroOrNull(...$columnNames): string
    {
        $firstColumn = array_shift($columnNames);
        $result = "$firstColumn = 0 OR $firstColumn IS NULL";

        foreach ($columnNames as $columnName) {
            $result .= " OR $columnName = 0 OR $columnName IS NULL";
        }

        return $result;
    }

    public static function isNull(string $columnName): string
    {
        return "$columnName IS NULL";
    }

    public static function and(...$condition): string
    {
        return join(' AND ', $condition);
    }

    public static function wrap(string $expression): string
    {
        return "($expression)";
    }

    public static function sum(string $columnNames): string
    {
        return "SUM($columnNames)";
    }

    public static function plus(string ...$columnNames): string
    {
        return join(' + ', $columnNames);
    }

    public static function minus(string ...$columnNames): string
    {
        return join(' - ', $columnNames);
    }

    public static function multiply(string ...$columnNames): string
    {
        return join(' * ', $columnNames);
    }

    public static function divide(string ...$columnNames): string
    {
        $result = [
            self::multiply(array_shift($columnNames), '1.0'),
            ...array_map(fn (string $columnName) => self::nullIfZero($columnName), $columnNames)
        ];

        return join(' / ', $result);
    }

    public static function if(string $condition, string $trueValue, string $falseValue): string
    {
        return "CASE WHEN $condition THEN $trueValue ELSE $falseValue END";
    }

    public static function switchCase(string $columnName, array $conditions, mixed $defaultValue = null): string
    {
        $result = "\nCASE $columnName";
        foreach ($conditions as $condition => $value) {
            $result .= "\n  WHEN $condition THEN $value";
        }
        if ($defaultValue) {
            $result .= "\n  ELSE $defaultValue";
        }
        return $result . "\nEND";
    }

    public static function flexibleCase(string|array $conditions, mixed $defaultValue = null): string
    {
        $result = 'CASE';
        if (is_string($conditions)) {
            $conditions = [$conditions];
        }
        if (array_is_list($conditions)) {
            foreach ($conditions as $condition) {
                $result .= "\n  WHEN $condition THEN ?";
            }
        } else {
            foreach ($conditions as $condition => $value) {
                $result .= "\n  WHEN $condition THEN $value";
            }
        }
        if ($defaultValue) {
            $result .= "\n  ELSE $defaultValue";
        }
        return $result . "\nEND";
    }

    public static function concat(string ...$values): string
    {
        return Str::concat(
            "CONCAT_WS('', ",
            join(', ', $values),
            ')'
        );
    }

    public static function cast(string $columnName, string $as): string
    {
        return "CAST($columnName AS $as)";
    }

    public static function as(string $expression, string $as): string
    {
        return "$expression AS $as";
    }

    public static function max(string $columnName): string
    {
        return "MAX($columnName)";
    }

    public static function relations(string $columnName, ...$relationClasses): string
    {
        $result = '';
        foreach ($relationClasses as $relationClass) {
            $result .= Convert::modelToRelation($relationClass, true, true) . '.';
        }

        if ($columnName) {
            $result .= $columnName;
        } else {
            $result = substr($result, 0, -1);
        }

        return $result;
    }

    public static function string($value): string
    {
        return DB::escape($value);
    }

    public static function dateAfter(int $count, string $sourceColumnName, Interval $type = Interval::Day): string
    {
        return "$sourceColumnName + INTERVAL '$count' {$type->value}";
    }

    /**
     * NOTE: Currently, only support 'DAY' interval
     */
    public static function diff(string $firstColumnName, string $secondColumnName, Interval $interval = Interval::Day): string
    {
        return Driver::matcher(
            mysql: "TIMESTAMPDIFF({$interval->value}, $secondColumnName, $firstColumnName)",
            pgsql: Str::concat(
                "EXTRACT({$interval->value} FROM ",
                self::wrap(self::minus($firstColumnName, $secondColumnName)),
                ')'
            )
        );
    }

    public static function now(): string
    {
        return 'NOW()';
    }

    public static function compare(string $firstColumnName, string $secondColumnName = '?', Operator $operator = Operator::SameWith): string
    {
        return "$firstColumnName {$operator->value} $secondColumnName";
    }

    public static function queryConcat(string ...$expressions): string
    {
        return join(' ', $expressions);
    }
}