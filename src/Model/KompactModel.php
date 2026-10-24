<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\AutoCast;
use JundFauz\EloquentKompact\Model\Traits\DynamicFillableColumnDefinition;
use JundFauz\EloquentKompact\Model\Traits\DynamicModelRelation;
use JundFauz\EloquentKompact\Model\Traits\Filterable;
use JundFauz\EloquentKompact\Model\Traits\RelationData;
use JundFauz\EloquentKompact\Model\Traits\StaticH;

abstract class KompactModel extends Model
{
    use AdditionalQueries, AutoCast, DynamicFillableColumnDefinition, DynamicModelRelation, Filterable, RelationData, StaticH, SoftDeletes {
        SoftDeletes::bootSoftDeletes as traitBootSoftDeletes;
        SoftDeletes::performDeleteOnModel as traitPerformDeleteOnModel;
    }

    protected static bool $useSoftDelete = true;

    public static function bootSoftDeletes(): void
    {
        if (static::$useSoftDelete) {
            static::traitBootSoftDeletes();
        }
    }
}
