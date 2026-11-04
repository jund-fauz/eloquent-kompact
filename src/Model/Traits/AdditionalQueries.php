<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Column\Creator;
use function JundFauz\EloquentKompact\is_not_array;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\model_compact;

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

    public function getOr(string ...$columnNames)
    {
        return array_reduce($columnNames, fn ($acc, $column) => $acc ?? $this?->{$column});
    }

    public function getVia(string $columnName = '', string|array ...$relationClasses)
    {
        $result = $this;
        foreach ($relationClasses as $relationClass) {
            $result = $result?->{Convert::modelToRelation($relationClass)};
        }

        return $columnName ? $result?->{$columnName} : $result;
    }

    public function getRelVia(string|array ...$relationClasses)
    {
        return $this->getVia('', ...$relationClasses);
    }

    public function getManyVia(array $columnNames, string|array ...$relationClasses): array
    {
        $result = [];
        foreach ($columnNames as $columnName) {
            $result[] = $this->getVia($columnName, ...$relationClasses);
        }
        return $result;
    }

    public function compact(string ...$columns): array
    {
        return model_compact($this, ...$columns);
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

        return array_all($conditions, fn ($value, $column) => is_array($value) && is_not_array($this->{$column})
            ? in_array($this->{$column}, $value, true)
            : $this->sameWith($column, $value)
        );
    }

    /**
     * match(), with every column of the conditions being dirty
     *
     * @param array<string, array|mixed> $conditions Column name => expected value, or a list of allowed values
     * @return bool False if no condition is given
     */
    public function matchAndDirty(array $conditions): bool
    {
        return array_all(array_keys($conditions), fn ($column) => $this->isDirty($column)) && $this->match($conditions);
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

    public function sameWithAndDirty(string $columnName, mixed $value): bool
    {
        return $this->isDirty($columnName) && $this->sameWith($columnName, $value);
    }

    public function notSameWith(string $columnName, mixed $value): bool
    {
        return $this->{$columnName} !== $value;
    }

    public function notSameWithAndDirty(string $columnName, mixed $value): bool
    {
        return $this->isDirty($columnName) && $this->notSameWith($columnName, $value);
    }

    public function sameWithOther(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc && $this->sameWith($column, $other->{$column}), true);
    }

    public function sameWithOtherAndDirty(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc && $this->sameWithAndDirty($column, $other->{$column}), true);
    }

    public function sameWithOtherOr(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc || $this->sameWith($column, $other->{$column}), false);
    }

    public function sameWithOtherOrAndDirty(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc || $this->sameWithAndDirty($column, $other->{$column}), false);
    }

    public function notSameWithOther(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc && $this->notSameWith($column, $other->{$column}), true);
    }

    public function notSameWithOtherAndDirty(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc && $this->notSameWithAndDirty($column, $other->{$column}), true);
    }

    public function notSameWithOtherOr(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc || $this->notSameWith($column, $other->{$column}), false);
    }

    public function notSameWithOtherOrAndDirty(Model $other, string ...$columns): bool
    {
        if (blank($columns)) {
            return false;
        }

        return array_reduce($columns, fn ($acc, string $column) => $acc || $this->notSameWithAndDirty($column, $other->{$column}), false);
    }

    public function isMorphModelSame(string $morphName, string $modelClass): bool
    {
        return $this->sameWith(Creator::type($morphName), Relation::getMorphAlias($modelClass));
    }

    public function isMorphModelSameAndDirty(string $morphName, string $modelClass): bool
    {
        return $this->sameWithAndDirty(Creator::type($morphName), Relation::getMorphAlias($modelClass));
    }

    public function getMany(string ...$columns): array
    {
        return array_map(fn (string $column) => $this->{$column}, $columns);
    }

    public function assign(string|array $columns, mixed $value = null): void
    {
        if (is_string($columns)) {
            $this->{$columns} = $value;
        } else {
            foreach ($columns as $column => $value) {
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

    public function assignIfBlankFrom(Model $other, string ...$columns): void
    {
        foreach ($columns as $column) {
            if ($this->isBlank($column)) {
                $this->assign($column, $other->{$column});
            }
        }
    }

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

    public function assignIfNullFrom(Model $other, string ...$columns): void
    {
        foreach ($columns as $column) {
            if ($this->isNull($column)) {
                $this->assign($column, $other->{$column});
            }
        }
    }

    public function isBlank(string $column): bool
    {
        return blank($this->{$column});
    }

    public function isBlankAndDirty(string $column): bool
    {
        return $this->isDirty($column) && $this->isBlank($column);
    }

    public function isFilled(string $column): bool
    {
        return filled($this->{$column});
    }

    public function isFilledAndDirty(string $column): bool
    {
        return $this->isDirty($column) && $this->isFilled($column);
    }

    public function isNull(string $column): bool
    {
        return is_null($this->{$column});
    }

    public function isNullAndDirty(string $column): bool
    {
        return $this->isDirty($column) && $this->isNull($column);
    }

    public function isNotNull(string $column): bool
    {
        return is_not_null($this->{$column});
    }

    public function isNotNullAndDirty(string $column): bool
    {
        return $this->isDirty($column) && $this->isNotNull($column);
    }

    public function wereChanged(string ...$columns): bool
    {
        if (blank($columns)) {
            return $this->wasChanged();
        }
        return array_reduce($columns, fn ($acc, $column) => $acc && $this->wasChanged($column), true);
    }

    public function wereNotChanged(string ...$columns): bool
    {
        if (blank($columns)) {
            return ! $this->wasChanged();
        }
        return array_reduce($columns, fn ($acc, $column) => $acc && ! $this->wasChanged($column), true);
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
}
