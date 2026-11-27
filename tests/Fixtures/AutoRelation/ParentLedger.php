<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class ParentLedger extends KompactModel
{
    protected $table = 'ar_parent_ledgers';

    public $timestamps = false;
}
