<?php

namespace JundFauz\EloquentKompact\Column\Builder\Concerns;

use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Column\ColumnCreator;

class ColumnBuilder
{
    public static function __callStatic(string $name, array $arguments)
    {
        $method = class_basename(static::class);
        if (Str::endsWith($method, 'C')) {
            $method = Str::chopEnd($method, 'C');
        }
        $parameter = Str::snake($name);

        return ColumnCreator::{$method}($parameter);
    }
}
