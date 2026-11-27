<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

class KeptAuthor extends KompactModel
{
    protected $table = 'ar_kept_authors';

    public $timestamps = false;

    protected static bool $includeSoftDeletedData = true;
}
