<?php

namespace JundFauz\EloquentKompact\Query;

enum Operator: string
{
    case SameWith = '=';
    case NotSameWith = '!=';
    case MoreThan = '>';
    case LessThan = '<';
    case MoreThanOrSame = '>=';
    case LessThanOrSame = '<=';
}
