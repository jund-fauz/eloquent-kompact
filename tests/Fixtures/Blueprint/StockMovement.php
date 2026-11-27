<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\Blueprint;

use JundFauz\EloquentKompact\Model\KompactModel;

class StockMovement extends KompactModel
{
    public static function getPrefixId(): array
    {
        return [
            "'in'" => "'IN'",
            "'out'" => "'OUT'",
        ];
    }

    public static function getSourceColumnNameForViewedId(): string
    {
        return 'seq_no';
    }
}
