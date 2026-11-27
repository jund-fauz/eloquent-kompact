<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactPivot;

class CastingConnector extends KompactPivot
{
    protected $table = 'ar_casting_connector';

    public $timestamps = false;

    protected static array $pivotBetween = [
        'casting_id' => ParentLedger::class,
        'target_sub_id' => [ParentLedger::class, 'sub'],
    ];
}
