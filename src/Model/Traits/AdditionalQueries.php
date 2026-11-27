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
use function JundFauz\EloquentKompact\Array\filter_filled;
use function JundFauz\EloquentKompact\Array\flat;
use function JundFauz\EloquentKompact\Array\is_count_zero;
use function JundFauz\EloquentKompact\Array\merge;
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
     */
    public function getRelVia(string|array ...$relationClasses): mixed
    {
        return $this->getVia('', ...$relationClasses);
    }

    public function compact(string|array ...$columns): array
    {
        return object_compact($this, ...$columns);
    }

    /**
     * [foreign_key => key] for this model
     *
     * @return array<string, mixed>
     */
    public function compactId(): array
    {
        return [static::foreign() => $this->getKey()];
    }

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
     */
    public function match(array $conditions): bool
    {
        if (blank($conditions)) {
            return false;
        }

        return array_all($conditions, fn ($value, $column) => is_array($value) && is_not('array', $this->{$column})
            ? in_array($this->{$column}, $value, true)
            : $this->sameWith($column, $value)
        );
    }

    /**
     * Shorthand for: $this->{$columnName} === $value
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
     */
    public function sameWith(string $columnName, mixed $value, ?string $cast = null): bool
    {
        return $this->castedAttribute($columnName, $cast) === cast_as($value, $cast);
    }

    /**
     * The attribute after casting, with $cast instead of the cast of the column when it is given.
     * The cast is applied to a clone, so the casts of the model stay as they are
     *
     * @param string $columnName
     * @param string|null $cast
     * @return mixed
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

    public function isMorphModelSame(string $morphName, string $modelClass): bool
    {
        return $this->sameWith(ColumnCreator::type($morphName), Relation::getMorphAlias($modelClass));
    }

    /**
     * The attributes of the columns in the given order, falling back to the default set by withDefault() (null by default)
     *
     * @param string ...$columns
     * @return array
     */
    public function getMany(string ...$columns): array
    {
        return array_map(fn (string $column) => $this->{$column} ?? $this->defaultValue, $columns);
    }

    /**
     * @param string|array $what
     * @param string|array $toColumns
     * @param string|array $conditions
     * @return void
     * @experimental
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
     */
    public function assign(string|array $columns, mixed $valueOrCondition = null, string|array|null $condition = null): void
    {
        if (is_string($columns)) {
            $columns = [$columns => $valueOrCondition];
        } else {
            $condition ??= $valueOrCondition;
        }
        $condition = wrap($condition);
        $notNeedCondition = is_count_zero($condition);
        foreach ($columns as $column => $value) {
            if ($notNeedCondition || $this->isAllOf($column, ...$condition)) {
                $this->{$column} = $value;
            }
        }
    }

    public function assignFrom(Model $other, string ...$columns): void
    {
        foreach ($columns as $column) {
            $this->assign($column, $other->{$column});
        }
    }

    /**
     * Copies each column from $other when it satisfies isAllOf($column, ...$condition), without saving
     *
     * @param string|array $condition
     * @param Model $other
     * @param string ...$columns
     * @return void
     */
    public function assignFromIf(string|array $condition, Model $other, string ...$columns): void
    {
        foreach ($columns as $column) {
            if ($this->isAllOf($column, ...wrap($condition))) {
                $this->assign($column, $other->{$column});
            }
        }
    }

    public function isBlank(string $column): bool
    {
        return blank($this->{$column});
    }

    public function isFilled(string $column): bool
    {
        return filled($this->{$column});
    }

    public function isNull(string $column): bool
    {
        return is_null($this->{$column});
    }

    public function isNotNull(string $column): bool
    {
        return is_not_null($this->{$column});
    }

    public function grep(string $columnName, mixed $defaultValue, ?string $cast = null): mixed
    {
        return $this->castedAttribute($columnName, $cast) ?? cast_as($defaultValue, $cast);
    }

    public static function randomId(...$filter): ?int
    {
        if (count($filter)) {
            $result = static::where(...$filter)->inRandomOrder();
        } else {
            $result = static::inRandomOrder();
        }
        return $result->value('id');
    }

    /**
     * match(), with every column of the conditions being dirty
     *
     * @param array<string, array|mixed> $conditions Column name => expected value, or a list of allowed values
     * @return bool False if no condition is given
     * @deprecated Will be removed in 3.0. Use $this->isAll('dirty', array_keys($conditions)) && $this->match($conditions)
     */
    public function matchAndDirty(array $conditions): bool
    {
        return array_all(array_keys($conditions), fn ($column) => $this->isDirty($column)) && $this->match($conditions);
    }

    /**
     * sameWith(), with the column being dirty
     *
     * @param string $columnName
     * @param mixed $value
     * @param string|null $cast See sameWith()
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAllOf($columnName, 'dirty', sameWith: [$value, $cast])
     */
    public function sameWithAndDirty(string $columnName, mixed $value, ?string $cast = null): bool
    {
        return $this->isDirty($columnName) && $this->sameWith($columnName, $value, $cast);
    }

    /**
     * notSameWith(), with the column being dirty
     *
     * @param string $columnName
     * @param mixed $value
     * @param string|null $cast See sameWith()
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAllOf($columnName, 'dirty', notSameWith: [$value, $cast])
     */
    public function notSameWithAndDirty(string $columnName, mixed $value, ?string $cast = null): bool
    {
        return $this->isDirty($columnName) && $this->notSameWith($columnName, $value, $cast);
    }

    /**
     * sameWithOther(), with every column being dirty
     *
     * @param Model $other
     * @param string ...$columns
     * @return bool False if no column is given
     * @deprecated Will be removed in 3.0. Use $this->isAll('dirty', ...$columns) && $this->sameWithOther($other, ...$columns)
     */
    public function sameWithOtherAndDirty(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc && $this->sameWithAndDirty($column, $other->{$column}), true);
    }

    /**
     * sameWithOtherOr(), with the matching column being dirty
     *
     * @param Model $other
     * @param string ...$columns
     * @return bool False if no column is given
     * @deprecated Will be removed in 3.0. Use array_any($columns, fn ($column) => $this->isAllOf($column, 'dirty', sameWith: [$other->{$column}]))
     */
    public function sameWithOtherOrAndDirty(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc || $this->sameWithAndDirty($column, $other->{$column}), false);
    }

    /**
     * notSameWithOther(), with every column being dirty
     *
     * @param Model $other
     * @param string ...$columns
     * @return bool False if no column is given
     * @deprecated Will be removed in 3.0. Use $this->isAll('dirty', ...$columns) && ! $this->sameWithOther($other, ...$columns, ...['or'])
     */
    public function notSameWithOtherAndDirty(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc && $this->notSameWithAndDirty($column, $other->{$column}), true);
    }

    /**
     * notSameWithOtherOr(), with the differing column being dirty
     *
     * @param Model $other
     * @param string ...$columns
     * @return bool False if no column is given
     * @deprecated Will be removed in 3.0. Use array_any($columns, fn ($column) => $this->isAllOf($column, 'dirty', notSameWith: [$other->{$column}]))
     */
    public function notSameWithOtherOrAndDirty(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc || $this->notSameWithAndDirty($column, $other->{$column}), false);
    }

    /**
     * isMorphModelSame(), with the type column of the morph being dirty
     *
     * @param string $morphName
     * @param string $modelClass
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAllOf(ColumnCreator::type($morphName), 'dirty', sameWith: [Relation::getMorphAlias($modelClass)])
     */
    public function isMorphModelSameAndDirty(string $morphName, string $modelClass): bool
    {
        return $this->sameWithAndDirty(ColumnCreator::type($morphName), Relation::getMorphAlias($modelClass));
    }

    /**
     * isBlank(), with the column being dirty
     *
     * @param string $column
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAllOf($column, 'dirty', 'blank')
     */
    public function isBlankAndDirty(string $column): bool
    {
        return $this->isDirty($column) && $this->isBlank($column);
    }

    /**
     * isFilled(), with the column being dirty
     *
     * @param string $column
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAllOf($column, 'dirty', 'filled')
     */
    public function isFilledAndDirty(string $column): bool
    {
        return $this->isDirty($column) && $this->isFilled($column);
    }

    /**
     * isNull(), with the column being dirty
     *
     * @param string $column
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAllOf($column, 'dirty', 'null')
     */
    public function isNullAndDirty(string $column): bool
    {
        return $this->isDirty($column) && $this->isNull($column);
    }

    /**
     * isNotNull(), with the column being dirty
     *
     * @param string $column
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAllOf($column, 'dirty', 'not_null')
     */
    public function isNotNullAndDirty(string $column): bool
    {
        return $this->isDirty($column) && $this->isNotNull($column);
    }

    /**
     * Whether every column was changed by the last save; without a column, whether any column was
     *
     * @param string ...$columns
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAll('was_changed', ...$columns), or $this->wasChanged() without a column
     */
    public function wereChanged(string ...$columns): bool
    {
        if (blank($columns)) {
            return $this->wasChanged();
        }
        return array_all($columns, fn ($column) => $this->wasChanged($column));
    }

    /**
     * Whether no column was changed by the last save; without a column, whether no column at all was
     *
     * @param string ...$columns
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->isAllNot('was_changed', ...$columns), or ! $this->wasChanged() without a column
     */
    public function wereNotChanged(string ...$columns): bool
    {
        if (blank($columns)) {
            return ! $this->wasChanged();
        }
        return array_all($columns, fn ($column) => ! $this->wasChanged($column));
    }

    /**
     * Whether at least one column is the same as in the other model
     *
     * @param Model $other
     * @param string ...$columns
     * @return bool False if no column is given
     * @deprecated Will be removed in 3.0. Use $this->sameWithOther($other, ...$columns, ...['or'])
     */
    public function sameWithOtherOr(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc || $this->sameWith($column, $other->{$column}), false);
    }

    /**
     * Whether every column is different from the other model
     *
     * @param Model $other
     * @param string ...$columns
     * @return bool False if no column is given
     * @deprecated Will be removed in 3.0. Use ! $this->sameWithOther($other, ...$columns, ...['or']) when at least one column is given
     */
    public function notSameWithOther(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc && $this->notSameWith($column, $other->{$column}), true);
    }

    /**
     * Whether at least one column is different from the other model
     *
     * @param Model $other
     * @param string ...$columns
     * @return bool False if no column is given
     * @deprecated Will be removed in 3.0. Use ! $this->sameWithOther($other, ...$columns) when at least one column is given
     */
    public function notSameWithOtherOr(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc || $this->notSameWith($column, $other->{$column}), false);
    }

    /**
     * Shorthand for: $this->{$columnName} !== $value
     *
     * The attribute is compared after casting, like sameWith()
     *
     * @param string $columnName
     * @param mixed $value
     * @param string|null $cast See sameWith()
     * @return bool
     * @deprecated Will be removed in 3.0. Use $this->not('same_with', $columnName, $value, $cast), or the check not_same_with, e.g. $this->isAllOf($columnName, 'dirty', not_same_with: [$value, $cast])
     */
    public function notSameWith(string $columnName, mixed $value, ?string $cast = null): bool
    {
        return $this->castedAttribute($columnName, $cast) !== cast_as($value, $cast);
    }

    /**
     * The columns of the model reached through the relations, see getVia(), in the given order
     *
     * @param array $columnNames
     * @param string|array ...$relationClasses
     * @return array
     * @deprecated Will be removed in 3.0. Use $this->getVia($columnNames, ...$relationClasses), which keeps the keys of $columnNames
     */
    public function getManyVia(array $columnNames, string|array ...$relationClasses): array
    {
        $result = [];
        foreach ($columnNames as $columnName) {
            $result[] = $this->getVia($columnName, ...$relationClasses);
        }
        return $result;
    }

    /**
     * Sets the column or [column => value] entries whose current attribute is blank(), without saving
     *
     * @param string|array $columns
     * @param mixed $value
     * @return void
     * @deprecated Will be removed in 3.0. Use $this->assign($columns, $value, 'blank'), or $this->assign($columns, 'blank') for an array
     */
    public function assignIfBlank(string|array $columns, mixed $value = null): void
    {
        if (is_string($columns)) {
            if ($this->isBlank($columns)) {
                $this->assign($columns, $value);
            }
        } else {
            foreach ($columns as $column => $value) {
                if ($this->isBlank($column)) {
                    $this->assign($column, $value);
                }
            }
        }
    }

    /**
     * Copies each column whose current attribute is blank() from $other, without saving
     *
     * @param Model $other
     * @param string ...$columns
     * @return void
     * @deprecated Will be removed in 3.0. Use $this->assignFromIf('blank', $other, ...$columns)
     */
    public function assignIfBlankFrom(Model $other, string ...$columns): void
    {
        foreach ($columns as $column) {
            if ($this->isBlank($column)) {
                $this->assign($column, $other->{$column});
            }
        }
    }

    /**
     * Sets the column or [column => value] entries whose current attribute is null, without saving
     *
     * @param string|array $columns
     * @param mixed $value
     * @return void
     * @deprecated Will be removed in 3.0. Use $this->assign($columns, $value, 'null'), or $this->assign($columns, 'null') for an array
     */
    public function assignIfNull(string|array $columns, mixed $value = null): void
    {
        if (is_string($columns)) {
            if ($this->isNull($columns)) {
                $this->assign($columns, $value);
            }
        } else {
            foreach ($columns as $column => $value) {
                if ($this->isNull($column)) {
                    $this->assign($column, $value);
                }
            }
        }
    }

    /**
     * Copies each column whose current attribute is null from $other, without saving
     *
     * @param Model $other
     * @param string ...$columns
     * @return void
     * @deprecated Will be removed in 3.0. Use $this->assignFromIf('null', $other, ...$columns)
     */
    public function assignIfNullFrom(Model $other, string ...$columns): void
    {
        foreach ($columns as $column) {
            if ($this->isNull($column)) {
                $this->assign($column, $other->{$column});
            }
        }
    }
}
