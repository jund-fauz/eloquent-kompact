<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class KeptTag extends KompactModel
{
    protected $table = 'ar_kept_tags';

    public $timestamps = false;

    protected static bool $includeSoftDeletedData = true;
}
