<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str as LaravelStr;
use JundFauz\EloquentKompact\Helper\Str;

trait StaticH
{
    public static function tableName(): string
    {
        return (new static)->getTable();
    }

    public static function getModel(Blueprint $column): ?string
    {
        $tableName = $column->getTable();
        $modelName = LaravelStr::studly(LaravelStr::singular($tableName));
        $modelClass = Str::concat(config('eloquent-kompact.path.model'), $modelName);

        return class_exists($modelClass) ? $modelClass : null;
    }
}
