<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Support\Str as LaravelStr;
use JundFauz\EloquentKompact\Column\Creator;
use JundFauz\EloquentKompact\Helper\Path;
use JundFauz\EloquentKompact\Helper\Str;

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
            Str::concat(
                LaravelStr::studly(LaravelStr::singular($this->getTable())),
                LaravelStr::studly(Creator::getPlainColumnName($column))
            )
        );

        return class_exists($enum) ? $enum : 'string';
    }
}
