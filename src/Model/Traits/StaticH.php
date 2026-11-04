<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Schema\Blueprint;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Relation\Arrange;
use const JundFauz\EloquentKompact\NAMESPACE_DELIMITER;

trait StaticH
{
    public static function tableName(): string
    {
        return (new static)->getTable();
    }

    public static function morphClass()
    {
        return (new static)->getMorphClass();
    }

    public static function baseName(): string
    {
        return class_basename(static::class);
    }

    public static function singular(?string $prefix = null): string
    {
        return Convert::modelToRelation($prefix ? [$prefix => static::class] : static::class);
    }

    public static function plural(?string $prefix = null): string
    {
        return Convert::modelToRelation($prefix ? [$prefix => static::class] : static::class, true);
    }

    public static function relationTo(string|array ...$modelClasses): string
    {
        return static::columnTo('', ...$modelClasses);
    }

    public static function columnTo(string $columnName, string|array ...$modelClasses): string
    {
        return Arrange::relation($columnName, static::class, ...$modelClasses);
    }

    public static function foreign(?string $prefix = null): string
    {
        return Convert::modelToColumn(static::class, $prefix);
    }

    public static function getModel(Blueprint $column): ?string
    {
        $tableName = $column->getTable();
        $modelClass = (string) Str::of($tableName)
            ->singular()
            ->pascal()
            ->addAtStart(config('eloquent-kompact.path.model'), NAMESPACE_DELIMITER);

        return class_exists($modelClass) ? $modelClass : null;
    }
}
