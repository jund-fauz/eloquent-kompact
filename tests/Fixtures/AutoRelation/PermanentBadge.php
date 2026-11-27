<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class PermanentBadge extends KompactModel
{
    protected $table = 'ar_permanent_badges';

    public $timestamps = false;

    protected static bool $useSoftDelete = false;

    protected static bool $includeSoftDeletedData = true;
}
