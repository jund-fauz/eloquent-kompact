<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactPivot;

class CastingStemConnector extends KompactPivot
{
    protected $table = 'ar_casting_stem_connector';

    public $timestamps = false;

    protected static array $pivotBetween = [
        'casting' => StemLedger::class,
        'sub' => [StemLedger::class, 'target_sub_id'],
    ];
}
