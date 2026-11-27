<?php

namespace JundFauz\EloquentKompact\Query;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Support\Facades\DB;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Internal\Concerns\Builder;
use JundFauz\EloquentKompact\Internal\Database\Driver;
use function JundFauz\EloquentKompact\Array\wrap;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\String\concat;

/**
 * Fluent cross-driver SQL expression builder.
 *
 * NOTE: Built to compatible with SQLite, MySQL & PostgreSQL database
 * @method static self create(string|self $expression = '')
 * @final
 */
class RawQuery extends Builder implements Expression
{
    public const string CHAR = 'CHAR';
    public const string NULL = 'NULL';

    protected static ?string $initiationFunction = 'create';

    /**
     * Build a `SELECT ... FROM ...` query expression.
     *
     * @param string|array<int, string> $column
     * @param string $table
     * @return static
     */
    public static function select(string|array $column, string $table): static
    {
        return Str::of('SELECT')
            ->addWord(join(', ', wrap($column)), 'FROM', $table)
            ->asRawQuery();
    }

    /**
     * Wrap the current expression in `COALESCE(..., 0)`.
     *
     * @return static
     */
    public function zeroIfNull(): static
    {
        return $this->set("COALESCE($this, 0)");
    }

    /**
     * Wrap the current expression in `NULLIF(..., $replaceValue)`.
     *
     * NOTE: On PostgreSQL, the replacement value must match the column type, e.g. NULLIF(integer_column, '') fails because '' is not a valid integer
     *
     * @param string|self $replaceValue
     * @return static
     */
    public function replaceIfNull(string|self $replaceValue): static
    {
        return $this->set("NULLIF($this, $replaceValue)");
    }

    /**
     * Build a condition checking whether the current expression and/or given columns equal 0 or `NULL`.
     *
     * @param string|self ...$columnNames
     * @return static
     */
    public function isZeroOrNull(string|self ...$columnNames): static
    {
        $conditions = array_map(
            fn (string $columnName) => "$columnName = 0 OR $columnName IS NULL",
            $this->withResult(...$columnNames)
        );

        return $this->set(join(' OR ', $conditions));
    }

    /**
     * Append `IS NULL` to the current expression.
     *
     * @return static
     */
    public function isNull(): static
    {
        return $this->set("$this IS NULL");
    }

    /**
     * Appends IS NOT NULL to the current expression
     *
     * @return static
     */
    public function isNotNull(): static
    {
        return $this->set("$this IS NOT NULL");
    }

    /**
     * Combine the current expression with another condition using `AND`.
     *
     * @param string $otherColumn
     * @return static
     */
    public function and(string $otherColumn): static
    {
        return $this->set("$this AND $otherColumn");
    }

    /**
     * Wrap the current expression in parentheses.
     *
     * @return static
     */
    public function wrap(): static
    {
        return $this->set("($this)");
    }

    /**
     * Wrap the current expression in `SUM(...)`.
     *
     * @return static
     */
    public function sum(): static
    {
        return $this->set("SUM($this)");
    }

    /**
     * Join the current expression and given operands with `+`.
     *
     * @param string|self ...$columnNames
     * @return static
     */
    public function plus(string|self ...$columnNames): static
    {
        return $this->set(join(' + ', $this->withResult(...$columnNames)));
    }

    /**
     * Join the current expression and given operands with `-`.
     *
     * @param string|self ...$columnNames
     * @return static
     */
    public function minus(string|self ...$columnNames): static
    {
        return $this->set(join(' - ', $this->withResult(...$columnNames)));
    }

    /**
     * Join the current expression and given operands with `*`.
     *
     * @param string|self ...$columnNames
     * @return static
     */
    public function multiply(string|self ...$columnNames): static
    {
        return $this->set(join(' * ', $this->withResult(...$columnNames)));
    }

    /**
     * Divide the current expression and given operands safely using float coercion and `NULLIF(..., 0)`.
     *
     * @param string|self ...$columnNames
     * @return static
     */
    public function divide(string|self ...$columnNames): static
    {
        $columnNames = $this->withResult(...$columnNames);
        $result = [
            concat(array_shift($columnNames), ' * 1.0'),
            ...array_map(fn (string $columnName) => "NULLIF($columnName, 0)", $columnNames)
        ];

        return $this->set(join(' / ', $result));
    }

    /**
     * Build a `CASE WHEN ... THEN ... ELSE ... END` expression from the current condition.
     *
     * @param string|self $trueValue
     * @param string|self $falseValue
     * @return static
     */
    public function if(string|self $trueValue, string|self $falseValue): static
    {
        return $this->set("CASE WHEN $this THEN $trueValue ELSE $falseValue END");
    }

    /**
     * Build a simple `CASE <expr> WHEN ... THEN ... END` expression against the current subject.
     *
     * @param array<string|int, mixed> $conditions
     * @param mixed $defaultValue
     * @return static
     */
    public function switchCase(array $conditions, mixed $defaultValue = null): static
    {
        $result = "\nCASE $this";
        foreach ($conditions as $condition => $value) {
            $result .= "\n  WHEN $condition THEN $value";
        }
        if (is_not_null($defaultValue)) {
            $result .= "\n  ELSE $defaultValue";
        }
        return $this->set(concat($result, "\nEND"));
    }

    /**
     * Build a searched `CASE WHEN ... THEN ... END` expression from a condition list or map.
     *
     * @param string|self|array<int|string, mixed> $conditions
     * @param mixed $defaultValue
     * @return self
     */
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
        return self::create(concat($result, "\nEND"));
    }

    /**
     * Concatenate the current expression and given values using `CONCAT_WS('', ...)`.
     *
     * @param string|self ...$values
     * @return static
     */
    public function concat(string|self ...$values): static
    {
        return $this->set(concat(
            "CONCAT_WS('', ",
            join(', ', $this->withResult(...$values)),
            ')'
        ));
    }

    /**
     * Wrap the current expression in `CAST(... AS $as)`.
     *
     * @param string|self $as
     * @return static
     */
    public function cast(string|self $as): static
    {
        return $this->set("CAST($this AS $as)");
    }

    /**
     * Alias the current expression with `AS $as`.
     *
     * @param string|self $as
     * @return static
     */
    public function as(string|self $as): static
    {
        return $this->set("$this AS $as");
    }

    /**
     * Recursive CTE over a self-referencing table, e.g. a parent-child tree: the anchor step filters $table with $where, the recursive step joins $table to the CTE with $joinOn
     * NOTE: $columns are plain column names of $table, used in both steps (prefixed with the table in the recursive one). For different columns per step, e.g. a depth or a path, or a CTE without a table, use recursiveUnion()
     * NOTE: The values of $where and $joinOn are SQL, so pass '?' and give the values as bindings, in the order anchor, join, outer query
     *
     * @param string $cteName
     * @param string $table
     * @param string|array<int, string> $columns
     * @param string|self|array<int|string, string|self> $where
     * @param string|self|array<int|string, string|self> $joinOn
     * @param string|array<int, string> $select
     * @param bool $isUnionAll
     * @return static
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
        $columns = wrap($columns);

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
     * Build a `WITH RECURSIVE` CTE uniting an anchor query and a recursive query.
     *
     * NOTE: Works on MySQL 8+, MariaDB 10.2.2+, PostgreSQL and SQLite 3.8.3+. Each column takes its type from the anchor query, so CAST it there when the recursive query makes a wider value
     *
     * @param string $cteName
     * @param string|self $anchorQuery
     * @param string|self $recursiveQuery
     * @param string|array<int, string> $select
     * @param bool $isUnionAll
     * @return static
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

    /**
     * Wrap the current expression in `MAX(...)`.
     *
     * @return static
     */
    public function max(): static
    {
        return $this->set("MAX($this)");
    }

    /**
     * Resolve a snake-cased plural table/column expression from a model class or prefixed map.
     *
     * @param string $columnName
     * @param string|array<string, string> $relationClass
     * @return static
     */
    public static function relations(string $columnName, string|array $relationClass): static
    {
        $result = Str::of(Convert::modelToRelation($relationClass, true, true));

        return ($columnName
            ? $result->add('.', $columnName)
            : $result)
            ->asRawQuery();
    }

    /**
     * Escape a literal value for safe inclusion in raw SQL via the database driver.
     *
     * @param mixed $value
     * @return string
     */
    public static function string(mixed $value = ''): string
    {
        return DB::escape($value);
    }

    /**
     * Add a date/time interval to the current expression across supported SQL drivers.
     *
     * @param int $count
     * @param Interval $type
     * @return static
     */
    public function dateAfter(int $count, Interval $type = Interval::Day): static
    {
        return $this->set(Driver::matcher(
            sqlite: concat("datetime($this, '$count ", Str::of($type->value)->plural()->lower(), "')"),
            etc: "$this + INTERVAL '$count' $type->value"
        ));
    }

    /**
     * Compute the difference between the current timestamp expression and a second column.
     *
     * NOTE: Currently, only support 'DAY' interval
     * NOTE: On PostgreSQL, at least one of the columns must be a TIMESTAMP, because subtracting two DATE columns returns an integer instead of an interval
     *
     * @param string|self $secondColumnName
     * @param Interval $interval
     * @return static
     */
    public function diff(string|self $secondColumnName, Interval $interval = Interval::Day): static
    {
        return $this->set(Driver::matcher(
            mysql: "TIMESTAMPDIFF($interval->value, $secondColumnName, $this)",
            pgsql: "EXTRACT($interval->value FROM ($this - $secondColumnName))",
            sqlite: concat("(strftime('%s', $this) - strftime('%s', $secondColumnName)) / ", match ($interval) {
                Interval::Day => 60 * 60 * 24,
                default => ''
            })
        ));
    }

    /**
     * Return the SQL expression for the current timestamp on the active database driver.
     *
     * @return string
     */
    public static function now(): string
    {
        return Driver::matcher(
            sqlite: 'CURRENT_TIMESTAMP',
            etc: 'NOW()'
        );
    }

    /**
     * Compare the current expression against a second operand using the given SQL operator.
     *
     * @param string|self $secondColumnName
     * @param Operator $operator
     * @return static
     */
    public function compare(string|self $secondColumnName = '?', Operator $operator = Operator::SameWith): static
    {
        return $this->set("$this $operator->value $secondColumnName");
    }

    /**
     * Append a `WHERE` comparison clause to the current expression.
     *
     * @param string|self $firstColumnName
     * @param Operator $operator
     * @param string|self $secondColumnName
     * @return static
     */
    public function where(string|self $firstColumnName, Operator $operator = Operator::SameWith, string|self $secondColumnName = '?'): static
    {
        return $this->set("$this WHERE $firstColumnName $operator->value $secondColumnName");
    }

    /**
     * Append an `IN (...)` clause to the current expression.
     *
     * @param string|self $columnName
     * @return static
     */
    public function in(string|self $columnName): static
    {
        return $this->set("$this IN ($columnName)");
    }

    /**
     * Append a `LIMIT` clause to the current expression.
     *
     * @param int $count
     * @return static
     */
    public function limit(int $count): static
    {
        return $this->set("$this LIMIT $count");
    }

    /**
     * Wrap the current expression in `COUNT(...)`.
     *
     * @return static
     */
    public function count(): static
    {
        return $this->set("COUNT($this)");
    }

    /**
     * Convert the current raw query expression into a Kompact `Str` instance.
     *
     * @return Str
     */
    public function asStr(): Str
    {
        return Str::of($this);
    }

    /**
     * Concatenate multiple SQL expressions separated by spaces.
     *
     * @param string|self ...$expressions
     * @return string
     */
    public static function queryConcat(string|self ...$expressions): string
    {
        return join(' ', array_map(fn (string|self $expression) => (string)$expression, $expressions));
    }

    /**
     * Puts the current expression in front of the given operands, unless it is still empty
     *
     * @param string|self ...$expressions
     * @return array<int, string>
     */
    private function withResult(string|self ...$expressions): array
    {
        $expressions = array_map(fn (string|self $expression) => (string)$expression, $expressions);
        return $this->result === '' ? $expressions : [$this->result, ...$expressions];
    }

    /**
     * Joins the conditions with AND: a string key gives `key = value`, a list item is kept as it is
     *
     * @param string|self|array<int|string, string|self> $conditions
     * @param string $table
     * @return string
     */
    private static function conditions(string|self|array $conditions, string $table = ''): string
    {
        $result = [];
        foreach (wrap($conditions) as $columnName => $value) {
            $result[] = is_int($columnName)
                ? (string)$value
                : concat(self::qualify($columnName, $table), ' = ', (string)$value);
        }

        return join(' AND ', $result);
    }

    /**
     * Prefixes the column with the table, unless there is no table or the column already has one
     *
     * @param string $columnName
     * @param string $table
     * @return string
     */
    private static function qualify(string $columnName, string $table): string
    {
        return $table === '' || str_contains($columnName, '.') ? $columnName : "$table.$columnName";
    }

    /**
     * Get the value of the expression for the database grammar.
     *
     * @param Grammar $grammar
     * @return string
     */
    public function getValue(Grammar $grammar): string
    {
        return $this->build();
    }
}
