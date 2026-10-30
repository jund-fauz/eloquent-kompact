<?php

namespace JundFauz\EloquentKompact\Query;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Support\Facades\DB;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Internal\Concerns\Builder;
use JundFauz\EloquentKompact\Internal\Database\Driver;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\str_concat;

/**
 * NOTE: Built to compatible with MySQL & PostgreSQL database
 * @method static self create(string|self $expression = '')
 */
class RawQuery extends Builder implements Expression
{
    public const string CHAR = 'CHAR';
    public const string NULL = 'NULL';

    protected static ?string $initiationFunction = 'create';

    public function zeroIfNull(): static
    {
        return $this->set("COALESCE($this->result, 0)");
    }

    /**
     * NOTE: On PostgreSQL, the replacement value must match the column type, e.g. NULLIF(integer_column, '') fails because '' is not a valid integer
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

    public function and(string $otherColumn): static
    {
        return $this->set("$this->result AND $otherColumn");
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
            str_concat(array_shift($columnNames), ' * 1.0'),
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
        return $this->set(str_concat($result, "\nEND"));
    }

    public static function flexibleCase(string|self|array $conditions, mixed $defaultValue = null): self
    {
        $result = 'CASE';
        if (! is_array($conditions)) {
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
        return self::create(str_concat($result, "\nEND"));
    }

    public function concat(string|self ...$values): static
    {
        return $this->set(str_concat(
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

    public static function relations(string $columnName, ...$relationClasses): static
    {
        $result = Str::of();
        foreach ($relationClasses as $relationClass) {
            $result = $result->add(Convert::modelToRelation($relationClass, true, true), '.');
        }

        return ($columnName
            ? $result->add($columnName)
            : $result->substr(0, -1))
            ->asRawQuery();
    }

    public static function string(mixed $value = ''): string
    {
        return DB::escape($value);
    }

    public function dateAfter(int $count, Interval $type = Interval::Day): static
    {
        return $this->set("$this->result + INTERVAL '$count' $type->value");
    }

    /**
     * NOTE: Currently, only support 'DAY' interval
     * NOTE: On PostgreSQL, at least one of the columns must be a TIMESTAMP, because subtracting two DATE columns returns an integer instead of an interval
     */
    public function diff(string|self $secondColumnName, Interval $interval = Interval::Day): static
    {
        return $this->set(Driver::matcher(
            mysql: "TIMESTAMPDIFF($interval->value, $secondColumnName, $this->result)",
            pgsql: "EXTRACT($interval->value FROM ($this->result - $secondColumnName))"
        ));
    }

    public static function now(): string
    {
        return 'NOW()';
    }

    public function compare(string|self $secondColumnName = '?', Operator $operator = Operator::SameWith): static
    {
        return $this->set("$this->result $operator->value $secondColumnName");
    }

    public function count(): static
    {
        return $this->set("COUNT($this->result)");
    }

    public static function queryConcat(string|self ...$expressions): string
    {
        return join(' ', array_map(fn (string|self $expression) => (string) $expression, $expressions));
    }

    /**
     * Puts the current expression in front of the given operands, unless it is still empty
     */
    private function withResult(string|self ...$expressions): array
    {
        $expressions = array_map(fn (string|self $expression) => (string) $expression, $expressions);
        return $this->result === '' ? $expressions : [$this->result, ...$expressions];
    }

    public function getValue(Grammar $grammar): string
    {
        return $this->build();
    }
}
