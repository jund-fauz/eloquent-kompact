<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class Article extends KompactModel
{
    protected $table = 'ar_articles';

    public $timestamps = false;

    protected static bool $useSoftDelete = false;
}
