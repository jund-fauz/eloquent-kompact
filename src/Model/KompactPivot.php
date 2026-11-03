<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\DynamicModelRelation;
use JundFauz\EloquentKompact\Model\Traits\StaticH;

abstract class KompactPivot extends Pivot
{
    use AdditionalQueries, DynamicModelRelation, StaticH, SoftDeletes;

    public static function flushKompactCache(): void
    {
        self::$dynamicRelationCache = [];
    }
}
