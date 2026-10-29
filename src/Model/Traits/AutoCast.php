<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use JundFauz\EloquentKompact\Builder\StrB;
use JundFauz\EloquentKompact\Column\Creator;
use JundFauz\EloquentKompact\Helper\Path;

trait AutoCast
{
    /** @var array<class-string, array<string, string>> */
    protected static array $autoCastCache = [];

    public function getCasts(): array
    {
        // parent ditaruh di kiri supaya $casts eksplisit pada model
        // menang atas hasil autocast.
        return parent::getCasts() + (static::$autoCastCache[static::class] ??= $this->resolveAutoCasts());
    }

    /**
     * @return array<string, string>
     */
    protected function resolveAutoCasts(): array
    {
        $casts = [];

        foreach ($this->getFillable() as $column) {
            $token = Creator::getColumnType($column);

            $cast = $token === Creator::Enum
                ? $this->resolveEnumCast($column)
                : Creator::castOf($token);

            if ($cast !== null) {
                $casts[$column] = $cast;
            }
        }

        return $casts;
    }

    protected function resolveEnumCast(string $column): string
    {
        $enum = Path::namespaceConcat(
            config('eloquent-kompact.path.database_enum'),
            (string) StrB::initiate($this->getTable())
                ->singular()
                ->pascal()
                ->concat(
                    StrB::initiate(Creator::getPlainColumnName($column))
                        ->pascal()
                )
        );

        return class_exists($enum) ? $enum : 'string';
    }
}
