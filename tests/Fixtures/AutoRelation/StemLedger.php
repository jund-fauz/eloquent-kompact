<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class StemLedger extends KompactModel
{
    protected $table = 'ar_stem_ledgers';

    public $timestamps = false;
}
