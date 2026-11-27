<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Column\Convert;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use function JundFauz\EloquentKompact\any;
use function JundFauz\EloquentKompact\Array\filter;
use function JundFauz\EloquentKompact\Array\filter_filled;
use function JundFauz\EloquentKompact\Array\flat;
use function JundFauz\EloquentKompact\Array\is_count_zero;
use function JundFauz\EloquentKompact\Array\merge_as_array;
use function JundFauz\EloquentKompact\Array\wrap;
use function JundFauz\EloquentKompact\cast_as;
use function JundFauz\EloquentKompact\execute_what_function;
use function JundFauz\EloquentKompact\is;
use function JundFauz\EloquentKompact\is_negation_and_get_raw_function;
use function JundFauz\EloquentKompact\is_not;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\not;
use function JundFauz\EloquentKompact\object_compact;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\same_with_one_of;
use function JundFauz\EloquentKompact\schema_concat;
use function JundFauz\EloquentKompact\String\concat;
use function JundFauz\EloquentKompact\String\str;

trait AdditionalQueries
{
    protected static array $aqCache = [];
    private mixed $defaultValue = null;

    /**
     * Copies records of the given relations and columns to the target model via `createMany()`.
     *
     * @param Model $target
     * @param array<string, array<int, string>> $relations Relation name => columns to copy
     * @return void
     * @final
     */
    public function copyRelationsTo(Model $target, array $relations): void
    {
        foreach ($relations as $relationName => $columns) {
            $data = $this
                ->{$relationName}()
                ->get($columns)
                ->map
                ->only($columns)
                ->all();

            if ($data) {
                $target->{$relationName}()->createMany($data);
            }
        }
    }

    /**
     * Returns a clone whose getOr(), getMany(), getVia() and getRelVia() fall back to $value instead of null
     *
     * @param mixed $value
     * @return static
     * @final
     */
    public function withDefault(mixed $value): static
    {
        $newInstance = clone $this;
        $newInstance->defaultValue = $value;
        return $newInstance;
    }

    /**
     * The first non-null attribute, or the default set by withDefault() (null by default)
     *
     * @param string ...$columnNames
     * @return mixed
     * @final
     */
    public function getOr(string ...$columnNames): mixed
    {
        return array_reduce($columnNames, fn ($acc, $column) => $acc ?? $this?->{$column}) ?? $this->defaultValue;
    }

    /**
     * An attribute, an array of attributes, or the related model reached through nested relations,
     * falling back to the default set by withDefault() (null by default)
     *
     * @param string|array $columnName
     * @param string|array ...$relationClasses
     * @return mixed
     * @final
     */
    public function getVia(string|array $columnName = '', string|array ...$relationClasses): mixed
    {
        $result = $this;
        foreach ($relationClasses as $relationClass) {
            $result = $result?->{Convert::modelToRelation($relationClass)};
        }

        return match (true) {
            is_array($columnName) => array_map(fn (string $columnName) => $result?->{$columnName} ?? $this->defaultValue, $columnName),
            filled($columnName) => $result?->{$columnName},
            default => $result
        } ?? $this->defaultValue;
    }

    /**
     * The related model reached through nested relations, or the default set by withDefault() (null by default)
     *
     * @param string|array ...$relationClasses
     * @return mixed
     * @final
     */
    public function getRelVia(string|array ...$relationClasses): mixed
    {
        return $this->getVia('', ...$relationClasses);
    }

    /**
     * Associative array of the given attributes keyed by column name (or alias when mapped).
     *
     * @param string|array ...$columns
     * @return array<string, mixed>
     * @final
     */
    public function compact(string|array ...$columns): array
    {
        return object_compact($this, ...$columns);
    }

    /**
     * [foreign_key => key] for this model
     *
     * @return array<string, mixed>
     * @final
     */
    public function compactId(): array
    {
        return [Convert::modelToColumn(static::class) => $this->getKey()];
    }

    /**
     * Merges non-empty associative attribute arrays and updates the model; returns false if any entry is a list.
     *
     * @param array<string, mixed> ...$data
     * @return bool
     * @final
     */
    public function updateMany(array ...$data): bool
    {
        $data = filter_filled($data);
        if (array_any($data, fn ($data) => array_is_list($data))) {
            return false;
        }
        return $this->update(merge_as_array(...array_values($data)));
    }

    /**
     * Runs the check $what: a method of the model returning a bool ($what or is_$what, in camel case),
     * called with the parameters as they are; otherwise a bool function found by execute_what_function(),
     * called with the attribute of the first parameter, then the other parameters
     *
     * @param string $what E.g. 'null', 'dirty', 'same_with' or 'in'
     * @param mixed ...$parameters A column name first, or the argument of the method, e.g. the conditions of match()
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for $what
     * @internal
     */
    private function executeWhatFunction(string $what, ...$parameters): bool
    {
        [$isNegationFunction, $rawWhat] = is_negation_and_get_raw_function($what);
        $whats = str('camels', $rawWhat, is($rawWhat));
        foreach ($whats as $whatValue) {
            if ($this->isMethodEligible($whatValue)) {
                $result = $this->{$whatValue}(...$parameters);
                return $isNegationFunction
                    ? not($result)
                    : $result;
            }
        }
        return execute_what_function($what, $this->{array_shift($parameters)}, ...$parameters);
    }

    /**
     * Whether the model has the method and it returns a bool. The result is cached per class and
     * method (case-insensitive, like PHP method names), so the reflection runs once for each
     *
     * @param string $function
     * @return bool
     * @internal
     */
    private function isMethodEligible(string $function): bool
    {
        return static::$aqCache[schema_concat(static::class, str('lower', $function))] ??= $this->resolveMethodEligible($function);
    }

    /**
     * isMethodEligible() without the cache
     *
     * @param string $function
     * @return bool
     * @internal
     */
    private function resolveMethodEligible(string $function): bool
    {
        if (not(method_exists($this, $function))) {
            return false;
        }
        try {
            $returnType = ($method = (new ReflectionClass($this))->getMethod($function))->getReturnType();
            if (is_null($returnType)) {
                return (bool)preg_match('/@return\s+bool\b/', $method->getDocComment() ?: '');
            }
        } catch (ReflectionException) {
            return false;
        }
        return same_with(not($returnType instanceof ReflectionNamedType) ?: $returnType->getName(), 'bool');
    }

    /**
     * One check of isAllOf() or isAnyOf(): a named argument is the check with its extra parameters,
     * a positional one is the check alone
     *
     * @param mixed $value
     * @param int|string $key
     * @param mixed $column
     * @return bool
     * @internal
     */
    private function execute(mixed $value, int|string $key, mixed $column = null): bool
    {
        $arguments = [($keyIsString = is_string($key)) ? $key : $value];
        if (same_with(func_num_args(), 3)) {
            $arguments[] = $column;
        }
        if ($keyIsString) {
            $arguments = merge_as_array($arguments, $value);
        }
        return $this->executeWhatFunction(...$arguments);
    }

    /**
     * Whether every check is true, stopping at the first false one; true without a check.
     * Checks are model methods or bool functions; named arguments pass their parameters,
     * while positional checks are called without parameters.
     *
     * @param mixed ...$methodAndParameters
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for a reached check
     * @final
     */
    public function matchAll(mixed ...$methodAndParameters): bool
    {
        return array_all($methodAndParameters, $this->execute(...));
    }

    /**
     * Whether at least one check is true, stopping at the first true one; false without a check.
     * Checks are model methods or bool functions; named arguments pass their parameters,
     * while positional checks are called without parameters.
     *
     * @param mixed ...$methodAndParameters
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for a reached check
     * @final
     */
    public function any(mixed ...$methodAndParameters): bool
    {
        return array_any($methodAndParameters, $this->execute(...));
    }

    /**
     * Whether the check is false, e.g. $order->not('null', 'note') for ! $order->isNull('note')
     *
     * @param string $what See isAllOf()
     * @param mixed ...$parameters A column name first, or the argument of the method
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for $what
     * @final
     */
    public function not(string $what, mixed ...$parameters): bool
    {
        return not($this->executeWhatFunction($what, ...$parameters));
    }

    /**
     * Whether the check is true for every column, nested arrays flattened, stopping at the first false one;
     * true without a column. The check is one of isAllOf(), e.g. $order->isAll('dirty', 'status', 'note')
     *
     * @param string $what See isAllOf()
     * @param string|array ...$columns
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for $what and a column is given
     * @final
     */
    public function isAll(string $what, string|array ...$columns): bool
    {
        flat($columns);
        return array_all($columns, fn (string $column) => $this->executeWhatFunction($what, $column));
    }

    /**
     * Whether the check is true for at least one column, nested arrays flattened, stopping at the first true one;
     * false without a column. The check is one of isAllOf(), e.g. $order->isAny('null', 'status', 'note')
     *
     * @param string $what See isAllOf()
     * @param string|array ...$columns
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for $what and a column is given
     * @final
     */
    public function isAny(string $what, string|array ...$columns): bool
    {
        flat($columns);
        return array_any($columns, fn (string $column) => $this->executeWhatFunction($what, $column));
    }

    /**
     * Whether the check is false for every column, stopping at the first true one; true without a column.
     * The check is one of isAllOf(), e.g. $order->isAllNot('was_changed', 'status', 'note')
     *
     * @param string $what See isAllOf()
     * @param string|array ...$columns
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for $what and a column is given
     * @final
     */
    public function isAllNot(string $what, string|array ...$columns): bool
    {
        flat($columns);
        return not(array_any($columns, fn (string $column) => $this->executeWhatFunction($what, $column)));
    }

    /**
     * Whether the check is false for at least one column, stopping at the first false one; false without a column.
     * The check is one of isAllOf(), e.g. $order->isAnyNot('filled', 'status', 'note')
     *
     * @param string $what See isAllOf()
     * @param string|array ...$columns
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for $what and a column is given
     * @final
     */
    public function isAnyNot(string $what, string|array ...$columns): bool
    {
        flat($columns);
        return not(array_all($columns, fn (string $column) => $this->executeWhatFunction($what, $column)));
    }

    /**
     * Whether every check is true for the column, stopping at the first false one; true without a check.
     * A check is a method of the model returning a bool ($what or is_$what in camel case, e.g. 'blank'
     * for isBlank(), 'dirty' for isDirty()), otherwise a bool function of PHP or of this package run on
     * the attribute (see is_not()). Extra parameters go in a named argument,
     * e.g. $order->isAllOf('status', 'dirty', in: 'paid,sent')
     *
     * A method without a native return type counts when its docblock has `@return bool`
     *
     * @param mixed $columnOrParameters The column name, or the argument of a method such as the conditions of match()
     * @param mixed ...$what
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for a check that is reached
     * @final
     */
    public function isAllOf(mixed $columnOrParameters, mixed ...$what): bool
    {
        return array_all($what, fn ($value, $key) => $this->execute($value, $key, $columnOrParameters));
    }

    /**
     * Whether at least one check is true for the column, stopping at the first true one; false without a check.
     * The checks are the ones of isAllOf(), e.g. $order->isAnyOf('note', 'null', 'blank')
     *
     * @param mixed $columnOrParameters See isAllOf()
     * @param mixed ...$what
     * @return bool
     * @throws BadMethodCallException When no method nor function returning a bool is found for a check that is reached
     * @final
     */
    public function isAnyOf(mixed $columnOrParameters, mixed ...$what): bool
    {
        return array_any($what, fn ($value, $key) => $this->execute($value, $key, $columnOrParameters));
    }

    /**
     * Checks whether the model attributes match every given condition, using strict comparison
     *
     * An array value matches if the attribute equals one of its items, unless the attribute itself is an array
     *
     * Example: $user->match(['role' => ['admin', 'editor'], 'is_active' => true])
     *
     * @param array<string, array|mixed> $conditions Column name => expected value, or a list of allowed values
     * @return bool False if no condition is given
     * @final
     */
    public function match(array $conditions): bool
    {
        if (blank($conditions)) {
            return false;
        }

        return array_all($conditions, fn ($value, $column) => is_array($value) && is_not('array', $columnValue = $this->{$column})
            ? in_array($columnValue, $value, true)
            : $this->sameWith($column, $value)
        );
    }

    /**
     * Whether `$this->{$columnName}` strictly equals `$value`, or one of its items when `$value` is an array
     *
     * The attribute is compared after casting, so without $cast the value must have the cast type,
     * e.g. an enum case for an enum column. Object attributes such as dates are compared by identity,
     * so they never match another instance with the same value
     *
     * @param string $columnName
     * @param mixed $value
     * @param string|null $cast A cast of Laravel (e.g. 'integer', 'decimal:2', an enum class) used instead of
     *                          the cast of the column for this comparison only, and also applied to $value
     *                          by cast_as(), e.g. sameWith('qty', '5', 'integer')
     * @return bool
     * @final
     */
    public function sameWith(string $columnName, mixed $value, ?string $cast = null): bool
    {
        $castedColumnValue = $this->castedAttribute($columnName, $cast);
        if (any(not_array: [$value], array: [$castedColumnValue])) {
            $value = [$value];
        }
        return array_any($value, fn ($value) => $castedColumnValue === cast_as($value, $cast));
    }

    /**
     * The attribute after casting, with $cast instead of the cast of the column when it is given.
     * The cast is applied to a clone, so the casts of the model stay as they are
     *
     * @param string $columnName
     * @param string|null $cast
     * @return mixed
     * @final
     */
    protected function castedAttribute(string $columnName, ?string $cast = null): mixed
    {
        return (is_not_null($cast)
            ? (clone $this)->mergeCasts([$columnName => $cast])
            : $this)->{$columnName};
    }

    /**
     * Whether the columns are the same as in the other model (see sameWith()). The last column can be a
     * logic word instead: 'and' or 'all' (the default) for every column, 'or' or 'any' for at least one,
     * e.g. $order->sameWithOther($other, 'status', 'note', 'or')
     *
     * @param Model $other
     * @param string ...$columns
     * @return bool False if no column is given
     * @final
     */
    public function sameWithOther(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        if (not(same_with_one_of($logic = array_pop($columns), 'and', 'or', 'all', 'any'))) {
            $columns[] = $logic;
            $logic = 'all';
        }
        if (same_with_one_of($logic, 'and', 'or')) {
            $logic = match ($logic) {
                'and' => 'all',
                'or' => 'any'
            };
        }

        return concat('array_', $logic)($columns, fn (string $column) => $this->sameWith($column, $other->{$column}));
    }

    /**
     * Whether the polymorphic type column of `$morphName` matches the morph alias of `$modelClass`.
     *
     * @param string $morphName
     * @param class-string<Model>|string $modelClass
     * @return bool
     * @final
     */
    public function isMorphModelSame(string $morphName, string $modelClass): bool
    {
        return $this->sameWith(ColumnCreator::type($morphName), Relation::getMorphAlias($modelClass));
    }

    /**
     * The attributes of the columns in the given order, falling back to the default set by withDefault() (null by default)
     *
     * @param string ...$columns
     * @return array
     * @final
     */
    public function getMany(string ...$columns): array
    {
        return array_map(fn (string $column) => $this->{$column} ?? $this->defaultValue, $columns);
    }

    /**
     * Calls the model method `$what` (in PascalCase) on each column in `$toColumns` that satisfies `$conditions`.
     *
     * @param string|array $what
     * @param string|array $toColumns
     * @param string|array $conditions
     * @return void
     * @experimental
     * @final
     */
    public function process(string|array $what, string|array $toColumns, string|array $conditions): void
    {
        $toColumns = wrap($toColumns);
        foreach ($toColumns as $toColumn) {
            if ($this->isAllOf($toColumn, ...wrap($conditions))) {
                $function = str('pascal', ($whatIsArray = is_array($what)) ? array_key_first($what) : $what);
                $arguments = [$toColumn];
                if ($whatIsArray) {
                    $arguments = merge_as_array($arguments, ...array_values(wrap($what)));
                }
                $this->{$function}(...$arguments);
            }
        }
    }

    /**
     * Sets one attribute ($columns as string, $valueOrCondition as value) or several ($columns as [column => value],
     * $valueOrCondition as condition), without saving. When a condition is given, only columns satisfying
     * isAllOf($column, ...$condition) are assigned
     *
     * @param string|array $columns
     * @param mixed $valueOrCondition
     * @param string|array|null $condition
     * @return void
     * @final
     */
    public function assign(string|array $columns, mixed $valueOrCondition = null, string|array|null $condition = null): void
    {
        if (is_string($columns)) {
            $columns = [$columns => $valueOrCondition];
        } else {
            $condition ??= $valueOrCondition;
        }
        $condition = filter(wrap($condition), fn ($value, $key) => is_string($key) || is_not_null($value));
        $notNeedCondition = is_count_zero($condition);
        foreach ($columns as $column => $value) {
            if ($notNeedCondition || $this->isAllOf($column, ...$condition)) {
                $this->{$column} = $value;
            }
        }
    }

    /**
     * Copies each given column from `$other` onto this model without saving.
     *
     * @param Model $other
     * @param string ...$columns
     * @return void
     * @final
     */
    public function assignFrom(Model $other, string ...$columns): void
    {
        foreach ($columns as $column) {
            $this->assign($column, $other->{$column});
        }
    }

    /**
     * Copies each column from $other when it satisfies isAllOf($column, ...$condition), without saving
     *
     * @param string|array|null $condition
     * @param Model $other
     * @param string ...$columns
     * @return void
     * @final
     */
    public function assignFromIf(string|array|null $condition, Model $other, string ...$columns): void
    {
        $condition = filter(wrap($condition), fn ($value, $key) => is_string($key) || is_not_null($value));
        $notNeedCondition = is_count_zero($condition);
        foreach ($columns as $column) {
            if ($notNeedCondition || $this->isAllOf($column, ...$condition)) {
                $this->{$column} = $other->{$column};
            }
        }
    }

    /**
     * Array of the given `real_*` columns (`'real_'` stripped from the keys) or explicit `[source => target]` pairs
     *
     * @param string|array<string, string> ...$realColumns
     * @return array<string, mixed>
     * @final
     */
    public function customCompact(string|array ...$realColumns): array
    {
        $result = [];
        foreach ($realColumns as $realColumn) {
            if (is_array($realColumn)) {
                foreach ($realColumn as $source => $target) {
                    $result[$target] = $this->{$source};
                }
            } else {
                $result[str_replace('real_', '', $realColumn)] = $this->{$realColumn};
            }
        }

        return $result;
    }

    /**
     * Copies each given column onto its `real_{$column}` column, without saving
     *
     * @param string ...$columns
     * @return void
     * @final
     */
    public function assignSelfToReal(string ...$columns): void
    {
        foreach ($columns as $column) {
            $this->assign("real_$column", $this->{$column});
        }
    }

    /**
     * Copies each given column that satisfies isAllOf($column, ...$condition) onto its `real_{$column}` column, without saving
     *
     * @param string|array|null $condition
     * @param string ...$columns
     * @return void
     * @final
     */
    public function assignSelfToRealIf(string|array|null $condition, string ...$columns): void
    {
        $condition = filter(wrap($condition), fn ($value, $key) => is_string($key) || is_not_null($value));
        $notNeedCondition = is_count_zero($condition);
        foreach ($columns as $column) {
            if ($notNeedCondition || $this->isAllOf($column, ...$condition)) {
                $this->{"real_$column"} = $this->{$column};
            }
        }
    }

    /**
     * Runs `$condition` against the value of `$column` on this model.
     *
     * @param string $condition
     * @param string $column
     * @return bool
     * @throws BadMethodCallException
     * @final
     */
    public function if(string $condition, string $column): bool
    {
        return $this->executeWhatFunction($condition, $column);
    }

    /**
     * Whether the attribute of `$column` is `blank()`.
     *
     * @param string $column
     * @return bool
     * @final
     */
    public function isBlank(string $column): bool
    {
        return blank($this->{$column});
    }

    /**
     * Whether the attribute of `$column` is `filled()`.
     *
     * @param string $column
     * @return bool
     * @final
     */
    public function isFilled(string $column): bool
    {
        return filled($this->{$column});
    }

    /**
     * Whether the attribute of `$column` is `null`.
     *
     * @param string $column
     * @return bool
     * @final
     */
    public function isNull(string $column): bool
    {
        return is_null($this->{$column});
    }

    /**
     * Whether the attribute of `$column` is not `null`.
     *
     * @param string $column
     * @return bool
     * @final
     */
    public function isNotNull(string $column): bool
    {
        return is_not_null($this->{$column});
    }

    /**
     * Casted attribute of `$columnName`, or `$defaultValue` (also cast when `$cast` is given) when null.
     *
     * @param string $columnName
     * @param mixed $defaultValue
     * @param string|null $cast
     * @return mixed
     * @final
     */
    public function grep(string $columnName, mixed $defaultValue, ?string $cast = null): mixed
    {
        return $this->castedAttribute($columnName, $cast) ?? cast_as($defaultValue, $cast);
    }

    /**
     * Random `id` value from the model's table, optionally filtered by `where(...$filter)`.
     *
     * @param mixed ...$filter
     * @return int|null
     * @final
     */
    public static function randomId(...$filter): ?int
    {
        if (count($filter)) {
            $result = static::where(...$filter)->inRandomOrder();
        } else {
            $result = static::inRandomOrder();
        }
        return $result->value('id');
    }
}
