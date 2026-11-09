<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Builder as LBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Column\ColumnCreator;

class Builder extends LBuilder
{
    public function whereSameWith(Model $other, ...$columns): static
    {
        return $this->callScope(function (LBuilder $query) use ($other, $columns) {
            foreach ($columns as $column) {
                $query->where($column, $other->{$column});
            }
        });
    }

    public function whereMorphType(string $morphName, string $modelClass): static
    {
        return $this->callScope(fn (LBuilder $query) => $query->where(ColumnCreator::type($morphName), Relation::getMorphAlias($modelClass)));
    }

    public function whereMorphTypeNot(string $morphName, string $modelClass): static
    {
        return $this->callScope(fn (LBuilder $query) => $query->whereNot(ColumnCreator::type($morphName), Relation::getMorphAlias($modelClass)));
    }

    public function whereInRelation(string $relation, string $columnName, array $valuesToCompare): static
    {
        return $this->callScope(fn (LBuilder $query) => $query->whereHas($relation, fn (LBuilder $query) => $query->whereIn($columnName, $valuesToCompare)));
    }

    public function pluck($column, $key = null): Collection
    {
        return new Collection(parent::pluck($column, $key));
    }
}