<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class Post extends KompactModel
{
    protected $table = 'ar_posts';

    public $timestamps = false;
}
