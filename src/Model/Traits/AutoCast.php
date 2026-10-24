<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use App\Constant\Column\Enum;
use App\Constant\FilePath;
use App\Database\Enums\Status;
use Illuminate\Support\Str as LaravelStr;
use JundFauz\EloquentKompact\Column\Creator;
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
        if ($column === Enum::status()) {
            return Status::class;
        }

        $enum = Str::concat(
            FilePath::DatabaseEnums,
            LaravelStr::studly(LaravelStr::singular($this->getTable())),
            LaravelStr::studly(Creator::getPlainColumnName($column))
        );

        return class_exists($enum) ? $enum : 'string';
    }
}
