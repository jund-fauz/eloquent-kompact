<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnCreator;

trait AutoCast
{
    /** @var array<class-string, array<string, string>> */
    protected static array $autoCastCache = [];

    public function getCasts(): array
    {
        // The parent is placed on the left so that explicit $casts on the model
        // take precedence over the result of auto casting.
        return parent::getCasts() + (static::$autoCastCache[static::class] ??= $this->resolveAutoCasts());
    }

    /**
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
