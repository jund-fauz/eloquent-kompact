<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str as LaravelStr;
use JundFauz\EloquentKompact\Helper\Path;

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
        $modelClass = Path::namespaceConcat(config('eloquent-kompact.path.model'), $modelName);

        return class_exists($modelClass) ? $modelClass : null;
    }
}
