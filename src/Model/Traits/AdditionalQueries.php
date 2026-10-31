<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\Column\Creator;
use function JundFauz\EloquentKompact\is_not_array;

trait AdditionalQueries
{
    public function copyRelationsTo(Model $target, array $relations): void
    {
        foreach ($relations as $relationName => $columns) {
            $datas = $this
                ->{$relationName}()
                ->get($columns)
                ->map
                ->only($columns)
                ->all();

            if ($datas) {
                $target->{$relationName}()->createMany($datas);
            }
        }
    }

    #[Scope]
    protected function whereSameWith(Builder $query, Model $other, mixed ...$columns): Builder
    {
        foreach ($columns as $column) {
            $query->where($column, $other->{$column});
        }

        return $query;
    }

    #[Scope]
    protected function whereMorphType(Builder $query, string $morphName, string $modelClass): Builder
    {
        return $query->where(Creator::type($morphName), Relation::getMorphAlias($modelClass));
    }

    #[Scope]
    protected function whereMorphTypeNot(Builder $query, string $morphName, string $modelClass): Builder
    {
        return $query->whereNot(Creator::type($morphName), Relation::getMorphAlias($modelClass));
    }

    public function getOr(string ...$columnNames)
    {
        return array_reduce($columnNames, fn ($acc, $column) => $acc ?? $this?->{$column});
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
        if (! count($conditions)) {
            return false;
        }

        return array_all($conditions, fn ($value, $column) => is_array($value) && is_not_array($this->{$column})
            ? in_array($this->{$column}, $value, true)
            : $this->sameWith($column, $value)
        );
    }

    /**
     * Shorthand for: $this->{$columnName} === $value
     *
     * The attribute is compared after casting, so the value must have the cast type,
     * e.g. an enum case for an enum column. Object attributes such as dates are compared by identity,
     * so they never match another instance with the same value
     *
     * @param string $columnName
     * @param mixed $value
     * @return bool
     */
    public function sameWith(string $columnName, mixed $value): bool
    {
        return $this->{$columnName} === $value;
    }

    public function isMorphModelSame(string $morphName, string $modelClass): bool
    {
        return $this->sameWith(Creator::type($morphName), Relation::getMorphAlias($modelClass));
    }

    public function getMany(string ...$columns)
    {
        return array_map(fn (string $column) => $this->{$column}, $columns);
    }
}
