<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
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
        $modelName = Str::studly(Str::singular($tableName));
        $modelClass = Path::namespaceConcat(config('eloquent-kompact.path.model'), $modelName);

        return class_exists($modelClass) ? $modelClass : null;
    }
}
