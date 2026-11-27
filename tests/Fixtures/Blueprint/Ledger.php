<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\Blueprint;

use JundFauz\EloquentKompact\Model\KompactModel;

class Ledger extends KompactModel
{
    public static function getPrefixId(): array
    {
        return [
            "'debit'" => "'DB'",
            "'credit'" => "'CR'",
        ];
    }
}
