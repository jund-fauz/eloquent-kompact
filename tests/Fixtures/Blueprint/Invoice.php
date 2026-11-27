<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\Blueprint;

use JundFauz\EloquentKompact\Model\KompactModel;

class Invoice extends KompactModel
{
    public static function getPrefixId(): string
    {
        return 'INV';
    }

    public static function getSourceColumnNameForViewedId(): string
    {
        return 'seq_no';
    }
}
