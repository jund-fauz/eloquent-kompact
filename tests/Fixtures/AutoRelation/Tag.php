<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class Tag extends KompactModel
{
    protected $table = 'ar_tags';

    public $timestamps = false;
}
