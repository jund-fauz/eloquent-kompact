<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class Author extends KompactModel
{
    protected $table = 'ar_authors';

    public $timestamps = false;
}
