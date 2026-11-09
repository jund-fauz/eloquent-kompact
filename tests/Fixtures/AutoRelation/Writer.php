<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class Writer extends KompactModel
{
    protected $table = 'ar_writers';

    public $timestamps = false;

    protected static bool $useSoftDelete = false;
}
