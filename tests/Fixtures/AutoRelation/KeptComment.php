<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use JundFauz\EloquentKompact\Model\KompactModel;

class KeptComment extends KompactModel
{
    protected $table = 'ar_kept_comments';

    public $timestamps = false;

    protected static bool $includeSoftDeletedData = true;

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
