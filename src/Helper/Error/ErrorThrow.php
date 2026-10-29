<?php

namespace JundFauz\EloquentKompact\Helper\Error;

use BadMethodCallException;

trait ErrorThrow
{
    protected static function methodNotFound(string $method)
    {
        throw new BadMethodCallException(__('eloquent-kompact::messages.method_not_found', [
            'class' => static::class,
            'method' => $method,
        ]));
    }
}