<?php

namespace JundFauz\EloquentKompact\Query;

enum Interval: string
{
    case Second = 'SECOND';
    case Minute = 'MINUTE';
    case Hour = 'HOUR';
    case Day = 'DAY';
    case Month = 'MONTH';
    case Year = 'YEAR';
}