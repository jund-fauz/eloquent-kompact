<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoRelation;

use JundFauz\EloquentKompact\Model\KompactModel;

/**
 * Has neither a writer column nor a morph, so `$writer->labels` cannot be resolved
 */
class Label extends KompactModel
{
    protected $table = 'ar_labels';

    public $timestamps = false;

    protected static bool $useSoftDelete = false;
}
