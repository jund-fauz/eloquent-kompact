<?php

namespace JundFauz\EloquentKompact\Query;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Support\Facades\DB;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Internal\Concerns\Builder;
use JundFauz\EloquentKompact\Internal\Database\Driver;
use function JundFauz\EloquentKompact\array_wrap;
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

    public static function select(string|array $column, string $table): static
    {
        return Str::of('SELECT')
            ->addWord(join(', ', array_wrap($column)), 'FROM', $table)
            ->asRawQuery();
    }

    public function zeroIfNull(): static
    {
        return $this->set("COALESCE($this, 0)");
    }

    /**
     * NOTE: On PostgreSQL, the replacement value must match the column type, e.g. NULLIF(integer_column, '') fails because '' is not a valid integer
     */
    public function replaceIfNull(string|self $replaceValue): static
    {
        return $this->set("NULLIF($this, $replaceValue)");
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
        return $this->set("$this IS NULL");
    }

    public function and(string $otherColumn): static
    {
        return $this->set("$this AND $otherColumn");
    }

    public function wrap(): static
    {
        return $this->set("($this)");
    }

    public function sum(): static
    {
        return $this->set("SUM($this)");
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
        return $this->set("CASE WHEN $this THEN $trueValue ELSE $falseValue END");
    }

    public function switchCase(array $conditions, mixed $defaultValue = null): static
    {
        $result = "\nCASE $this";
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

    public function cast(string|self $as): static
    {
        return $this->set("CAST($this AS $as)");
    }

    public function as(string|self $as): static
    {
        return $this->set("$this AS $as");
    }

    /**
     * Recursive CTE over a self-referencing table, e.g. a parent-child tree: the anchor step filters $table with $where, the recursive step joins $table to the CTE with $joinOn
     * NOTE: $columns are plain column names of $table, used in both steps (prefixed with the table in the recursive one). For different columns per step, e.g. a depth or a path, or a CTE without a table, use recursiveUnion()
     * NOTE: The values of $where and $joinOn are SQL, so pass '?' and give the values as bindings, in the order anchor, join, outer query
     */
    public static function recursive(
        string $cteName,
        string $table,
        string|array $columns,
        string|self|array $where,
        string|self|array $joinOn,
        string|array $select = '*',
        bool $isUnionAll = true,
    ): static {
        $columns = array_wrap($columns);

        return self::recursiveUnion(
            $cteName,
            self::select($columns, $table)
                ->asStr()
                ->addWord('WHERE', self::conditions($where))
                ->asRawQuery(),
            self::select(array_map(fn (string $column) => self::qualify($column, $table), $columns), $table)
                ->asStr()
                ->addWord('JOIN', $cteName, 'ON', self::conditions($joinOn, $table))
                ->asRawQuery(),
            $select,
            $isUnionAll
        );
    }

    /**
     * NOTE: Works on MySQL 8+, MariaDB 10.2.2+, PostgreSQL and SQLite 3.8.3+. Each column takes its type from the anchor query, so CAST it there when the recursive query makes a wider value
     */
    public static function recursiveUnion(string $cteName, string|self $anchorQuery, string|self $recursiveQuery, string|array $select = '*', bool $isUnionAll = true): static
    {
        return Str::of('WITH')
            ->addWord('RECURSIVE', $cteName, 'AS', '(')
            ->addAtNewLine($anchorQuery)
            ->addAtNewLine('UNION', 2)
            ->addWordIf($isUnionAll, 'ALL')
            ->addAtNewLine($recursiveQuery, 2)
            ->addAtNewLine(')')
            ->addAtNewLine(self::select($select, $cteName))
            ->asRawQuery();
    }

    public function max(): static
    {
        return $this->set("MAX($this)");
    }

    public static function relations(string $columnName, string|array $relationClass): static
    {
        $result = Str::of(Convert::modelToRelation($relationClass, true, true));

        return ($columnName
            ? $result->add('.', $columnName)
            : $result)
            ->asRawQuery();
    }

    public static function string(mixed $value = ''): string
    {
        return DB::escape($value);
    }

    public function dateAfter(int $count, Interval $type = Interval::Day): static
    {
        return $this->set("$this + INTERVAL '$count' $type->value");
    }

    /**
     * NOTE: Currently, only support 'DAY' interval
     * NOTE: On PostgreSQL, at least one of the columns must be a TIMESTAMP, because subtracting two DATE columns returns an integer instead of an interval
     */
    public function diff(string|self $secondColumnName, Interval $interval = Interval::Day): static
    {
        return $this->set(Driver::matcher(
            mysql: "TIMESTAMPDIFF($interval->value, $secondColumnName, $this)",
            pgsql: "EXTRACT($interval->value FROM ($this - $secondColumnName))"
        ));
    }

    public static function now(): string
    {
        return 'NOW()';
    }

    public function compare(string|self $secondColumnName = '?', Operator $operator = Operator::SameWith): static
    {
        return $this->set("$this $operator->value $secondColumnName");
    }

    public function where(string|self $firstColumnName, Operator $operator = Operator::SameWith, string|self $secondColumnName = '?'): static
    {
        return $this->set("$this WHERE $firstColumnName $operator->value $secondColumnName");
    }

    public function in(string|self $columnName): static
    {
        return $this->set("$this IN ($columnName)");
    }

    public function limit(int $count): static
    {
        return $this->set("$this LIMIT $count");
    }

    public function count(): static
    {
        return $this->set("COUNT($this)");
    }

    public function asStr(): Str
    {
        return Str::of($this);
    }

    public static function queryConcat(string|self ...$expressions): string
    {
        return join(' ', array_map(fn (string|self $expression) => (string)$expression, $expressions));
    }

    /**
     * Puts the current expression in front of the given operands, unless it is still empty
     */
    private function withResult(string|self ...$expressions): array
    {
        $expressions = array_map(fn (string|self $expression) => (string)$expression, $expressions);
        return $this->result === '' ? $expressions : [$this->result, ...$expressions];
    }

    /**
     * Joins the conditions with AND: a string key gives `key = value`, a list item is kept as it is
     */
    private static function conditions(string|self|array $conditions, string $table = ''): string
    {
        $result = [];
        foreach (array_wrap($conditions) as $columnName => $value) {
            $result[] = is_int($columnName)
                ? (string)$value
                : str_concat(self::qualify($columnName, $table), ' = ', (string)$value);
        }

        return join(' AND ', $result);
    }

    /**
     * Prefixes the column with the table, unless there is no table or the column already has one
     */
    private static function qualify(string $columnName, string $table): string
    {
        return $table === '' || str_contains($columnName, '.') ? $columnName : "$table.$columnName";
    }

    public function getValue(Grammar $grammar): string
    {
        return $this->build();
    }
}
