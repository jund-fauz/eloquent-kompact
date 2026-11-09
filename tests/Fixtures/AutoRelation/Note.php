<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use JundFauz\EloquentKompact\Model\KompactModel;

class Note extends KompactModel
{
    protected $table = 'ar_notes';

    public $timestamps = false;

    protected static bool $useSoftDelete = false;

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
