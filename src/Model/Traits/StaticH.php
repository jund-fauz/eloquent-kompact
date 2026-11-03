<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Schema\Blueprint;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Convert;
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

    public static function singular(): string
    {
        return Convert::modelToRelation(static::class);
    }

    public static function plural(): string
    {
        return Convert::modelToRelation(static::class, true);
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
