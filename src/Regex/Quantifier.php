<?php

namespace JundFauz\EloquentKompact\Regex;

enum Quantifier: string
{
    case zeroOrMore = '*';
    case oneOrMore = '+';
    case zeroOrOne = '?';
}
