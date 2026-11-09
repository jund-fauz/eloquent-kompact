<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

trait Kompact
{
    use AdditionalQueries, AutoCast, AutoFillable, AutoRelation, StaticH, SoftDeletes {
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

    protected function performDeleteOnModel(): void
    {
        if (static::$useSoftDelete) {
            $this->traitPerformDeleteOnModel();
        } else {
            parent::performDeleteOnModel();
        }
    }

    public static function flushKompactCache(): void
    {
        self::$autoCastCache = [];
        self::$AFCache = null;
        self::$dynamicRelationCache = [];
    }
}
