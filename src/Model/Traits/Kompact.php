<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use JundFauz\EloquentKompact\Model\ModelCache;

trait Kompact
{
    use AdditionalQueries, AutoCast, AutoRelation, StaticH, SoftDeletes, Validation, ViewedId {
        SoftDeletes::bootSoftDeletes as traitBootSoftDeletes;
        SoftDeletes::performDeleteOnModel as traitPerformDeleteOnModel;
    }

    protected static bool $useSoftDelete = true;

    /**
     * Boots the `SoftDeletes` trait when `$useSoftDelete` is enabled on the model.
     *
     * @return void
     */
    public static function bootSoftDeletes(): void
    {
        if (static::$useSoftDelete) {
            static::traitBootSoftDeletes();
        }
    }

    /**
     * Performs a soft delete when `$useSoftDelete` is enabled, or a hard delete otherwise.
     *
     * @return void
     */
    protected function performDeleteOnModel(): void
    {
        if (static::$useSoftDelete) {
            $this->traitPerformDeleteOnModel();
        } else {
            parent::performDeleteOnModel();
        }
    }

    /**
     * Clears static caches for auto-casts, auto-fillables, dynamic relations, and custom pivots.
     *
     * @return void
     * @final
     */
    public static function flushKompactCache(): void
    {
        ModelCache::flush();
    }
}
