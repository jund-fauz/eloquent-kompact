<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use App\Database\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait AdditionalQueries
{


    public function copyRelationsTo(BaseModel $target, array $relations): void
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
    protected function wheres(Builder $query, BaseModel $other, ...$columns): Builder
    {
        foreach ($columns as $column) {
            $query->where($column, $other->{$column});
        }

        return $query;
    }

    public function getOr(string ...$columnNames)
    {
        return array_reduce($columnNames, fn ($acc, $column) => $acc ?? $this?->{$column});
    }
}
