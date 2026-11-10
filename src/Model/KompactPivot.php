<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\AutoFillable;
use JundFauz\EloquentKompact\Model\Traits\AutoRelation;
use JundFauz\EloquentKompact\Model\Traits\StaticH;
use JundFauz\EloquentKompact\Model\Traits\Validation;

abstract class KompactPivot extends Pivot
{
    use AdditionalQueries, AutoFillable, AutoRelation, StaticH, SoftDeletes, Validation;

    protected static string $collectionClass = Collection::class;
    protected static string $builder = Builder::class;

    public static function flushKompactCache(): void
    {
        self::$dynamicRelationCache = [];
        self::$AFCache = null;
    }
}
