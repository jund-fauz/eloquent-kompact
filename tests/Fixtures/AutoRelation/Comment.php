<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use JundFauz\EloquentKompact\Model\KompactModel;

class Comment extends KompactModel
{
    protected $table = 'ar_comments';

    public $timestamps = false;

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
