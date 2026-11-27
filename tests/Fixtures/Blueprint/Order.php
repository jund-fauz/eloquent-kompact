<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\Blueprint;

use JundFauz\EloquentKompact\Model\KompactModel;

class Order extends KompactModel
{
    public static function getPrefixId(): string
    {
        return 'ORD';
    }
}
