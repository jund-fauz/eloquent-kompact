<?php

namespace JundFauz\EloquentKompact\Tests\Fixtures\AutoCast;

enum OrderStatus: string
{
    case New = 'new';
    case Paid = 'paid';
}
