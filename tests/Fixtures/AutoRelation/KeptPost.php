<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class KeptPost extends KompactModel
{
    protected $table = 'ar_kept_posts';

    public $timestamps = false;

    protected static bool $includeSoftDeletedData = true;
}
