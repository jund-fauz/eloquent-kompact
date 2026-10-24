<?php

namespace JundFauz\EloquentKompact\Model;

use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\DynamicModelRelation;
use JundFauz\EloquentKompact\Model\Traits\RelationData;
use JundFauz\EloquentKompact\Model\Traits\StaticH;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class KompactPivot extends Pivot
{
    use AdditionalQueries, DynamicModelRelation, RelationData, StaticH, SoftDeletes;
}
