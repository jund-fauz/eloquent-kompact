<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Model\ModelCache;

trait AutoCast
{
    use AutoFillable;

    /**
     * Explicit model casts merged over casts inferred from fillable column tokens.
     *
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        // The parent is placed on the left so that explicit $casts on the model
        // take precedence over the result of auto casting.
        return parent::getCasts() + (ModelCache::$casts[static::class] ??= $this->resolveAutoCasts());
    }

    /**
     * Infers casts for every fillable column from its column token.
     *
     * @return array<string, string>
     */
    protected function resolveAutoCasts(): array
    {
        $casts = [];

        foreach ($this->getFillable() as $column) {
            $token = ColumnCreator::getColumnType($column);

            $cast = $token === ColumnCreator::Enum
                ? $this->resolveEnumCast($column)
                : ColumnCreator::castOf($token);

            if ($cast !== null) {
                $casts[$column] = $cast;
            }
        }

        return $casts;
    }

    /**
     * Backing enum FQCN under `path.database_enum` for `$column`, or `'string'` if no enum class exists.
     *
     * @param string $column
     * @return string
     */
    protected function resolveEnumCast(string $column): string
    {
        $enum = (string) Str::of($this->getTable())
            ->singular()
            ->pascal()
            ->add(ColumnCreator::getPlainColumnName($column)->pascal())
            ->addNamespaceAtStart(config('eloquent-kompact.path.database_enum'));

        return class_exists($enum) ? $enum : 'string';
    }
}
