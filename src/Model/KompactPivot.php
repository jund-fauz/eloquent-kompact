<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\AutoRelation;
use JundFauz\EloquentKompact\Model\Traits\StaticH;

abstract class KompactPivot extends Pivot
{
    use AdditionalQueries, AutoRelation, StaticH, SoftDeletes;

    protected static string $collectionClass = Collection::class;
    protected static string $builder = Builder::class;

    public static function flushKompactCache(): void
    {
        self::$dynamicRelationCache = [];
    }
}
