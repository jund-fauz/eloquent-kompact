<?php

namespace JundFauz\EloquentKompact\Query;

use Illuminate\Support\Facades\DB;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Helper\Str;
use JundFauz\EloquentKompact\Query\Driver\Driver;
use Stringable;

/**
 * NOTE: Built to compatible with MySQL & PostgresSQL database
 */
class RawQuery implements Stringable
{
    public const string CHAR = 'CHAR';
    public const string NULL = 'NULL';

    protected string $result = '';

    public static function create(string|self $expression = ''): static
    {
        return (new static)->set((string) $expression);
    }

    public function zeroIfNull(): static
    {
        return $this->set("COALESCE($this->result, 0)");
    }

    /**
     * NOTE: On PostgreSQL, the replace value must match the column type, e.g. NULLIF(integer_column, '') fails because '' is not a valid integer
     */
    public function replaceIfNull(string|self $replaceValue): static
    {
        return $this->set("NULLIF($this->result, $replaceValue)");
    }

    public function isZeroOrNull(string|self ...$columnNames): static
    {
        $conditions = array_map(
            fn (string $columnName) => "$columnName = 0 OR $columnName IS NULL",
            $this->withResult(...$columnNames)
        );

        return $this->set(join(' OR ', $conditions));
    }

    public function isNull(): static
    {
        return $this->set("$this->result IS NULL");
    }

    public function and(string|self ...$conditions): static
    {
        return $this->set(join(' AND ', $this->withResult(...$conditions)));
    }

    public function wrap(): static
    {
        return $this->set("($this->result)");
    }

    public function sum(): static
    {
        return $this->set("SUM($this->result)");
    }

    public function plus(string|self ...$columnNames): static
    {
        return $this->set(join(' + ', $this->withResult(...$columnNames)));
    }

    public function minus(string|self ...$columnNames): static
    {
        return $this->set(join(' - ', $this->withResult(...$columnNames)));
    }

    public function multiply(string|self ...$columnNames): static
    {
        return $this->set(join(' * ', $this->withResult(...$columnNames)));
    }

    public function divide(string|self ...$columnNames): static
    {
        $columnNames = $this->withResult(...$columnNames);
        $result = [
            array_shift($columnNames) . ' * 1.0',
            ...array_map(fn (string $columnName) => "NULLIF($columnName, 0)", $columnNames)
        ];

        return $this->set(join(' / ', $result));
    }

    public function if(string|self $trueValue, string|self $falseValue): static
    {
        return $this->set("CASE WHEN $this->result THEN $trueValue ELSE $falseValue END");
    }

    public function switchCase(array $conditions, mixed $defaultValue = null): static
    {
        $result = "\nCASE $this->result";
        foreach ($conditions as $condition => $value) {
            $result .= "\n  WHEN $condition THEN $value";
        }
        if (is_not_null($defaultValue)) {
            $result .= "\n  ELSE $defaultValue";
        }
        return $this->set($result . "\nEND");
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
        if (is_not_null($defaultValue)) {
            $result .= "\n  ELSE $defaultValue";
        }
        return $result . "\nEND";
    }

    public function concat(string|self ...$values): static
    {
        return $this->set(Str::concat(
            "CONCAT_WS('', ",
            join(', ', $this->withResult(...$values)),
            ')'
        ));
    }

    public function cast(string $as): static
    {
        return $this->set("CAST($this->result AS $as)");
    }

    public function as(string $as): static
    {
        return $this->set("$this->result AS $as");
    }

    public function max(): static
    {
        return $this->set("MAX($this->result)");
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

    public static function string(mixed $value = ''): string
    {
        return DB::escape($value);
    }

    public function dateAfter(int $count, Interval $type = Interval::Day): static
    {
        return $this->set("$this->result + INTERVAL '$count' {$type->value}");
    }

    /**
     * NOTE: Currently, only support 'DAY' interval
     * NOTE: On PostgreSQL, at least one of the columns must be a TIMESTAMP, because subtracting two DATE columns returns an integer instead of an interval
     */
    public function diff(string|self $secondColumnName, Interval $interval = Interval::Day): static
    {
        return $this->set(Driver::matcher(
            mysql: "TIMESTAMPDIFF({$interval->value}, $secondColumnName, $this->result)",
            pgsql: "EXTRACT({$interval->value} FROM ($this->result - $secondColumnName))"
        ));
    }

    public static function now(): string
    {
        return 'NOW()';
    }

    public function compare(string|self $secondColumnName = '?', Operator $operator = Operator::SameWith): static
    {
        return $this->set("$this->result {$operator->value} $secondColumnName");
    }

    public function count(): static
    {
        return $this->set("COUNT($this->result)");
    }

    public static function queryConcat(string|self ...$expressions): string
    {
        return join(' ', array_map(fn (string|self $expression) => (string) $expression, $expressions));
    }

    public function build(): string
    {
        return $this->result;
    }

    public function __toString(): string
    {
        return $this->build();
    }

    protected function set(string $expression): static
    {
        $this->result = $expression;
        return $this;
    }

    /**
     * Puts the current expression in front of the given operands, unless it is still empty
     */
    protected function withResult(string|self ...$expressions): array
    {
        $expressions = array_map(fn (string|self $expression) => (string) $expression, $expressions);
        return $this->result === '' ? $expressions : [$this->result, ...$expressions];
    }
}
