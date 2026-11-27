<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Builder as LBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Column\ColumnCreator;

class Builder extends LBuilder
{
    /**
     * Constrains each given column to equal its value on `$other`.
     *
     * @param Model $other
     * @param string ...$columns
     * @return static
     * @final
     */
    public function whereSameWith(Model $other, ...$columns): static
    {
        return $this->callScope(function (LBuilder $query) use ($other, $columns) {
            foreach ($columns as $column) {
                $query->where($column, $other->{$column});
            }
        });
    }

    /**
     * Constrains the polymorphic type column of `$morphName` to the morph alias of `$modelClass`.
     *
     * @param string $morphName
     * @param class-string<Model>|string $modelClass
     * @return static
     * @final
     */
    public function whereMorphType(string $morphName, string $modelClass): static
    {
        return $this->callScope(fn (LBuilder $query) => $query->where(ColumnCreator::type($morphName), Relation::getMorphAlias($modelClass)));
    }

    /**
     * Constrains the polymorphic type column of `$morphName` to differ from the morph alias of `$modelClass`.
     *
     * @param string $morphName
     * @param class-string<Model>|string $modelClass
     * @return static
     * @final
     */
    public function whereMorphTypeNot(string $morphName, string $modelClass): static
    {
        return $this->callScope(fn (LBuilder $query) => $query->whereNot(ColumnCreator::type($morphName), Relation::getMorphAlias($modelClass)));
    }

    /**
     * Filters models having at least one related `$relation` record where `$columnName` is in `$valuesToCompare`.
     *
     * @param string $relation
     * @param string $columnName
     * @param array<int, mixed> $valuesToCompare
     * @return static
     * @final
     */
    public function whereInRelation(string $relation, string $columnName, array $valuesToCompare): static
    {
        return $this->callScope(fn (LBuilder $query) => $query->whereHas($relation, fn (LBuilder $query) => $query->whereIn($columnName, $valuesToCompare)));
    }

    /**
     * Plucks `$column` values from the query as a Kompact `Builder\Collection`.
     *
     * @param string|\Illuminate\Contracts\Database\Query\Expression $column
     * @param string|null $key
     * @return Collection
     */
    public function pluck($column, $key = null): Collection
    {
        return new Collection(parent::pluck($column, $key));
    }
}