<?php

namespace JundFauz\EloquentKompact\Internal\Error;

use BadMethodCallException;

/**
 * @internal
 */
trait ErrorThrow
{
    public static function methodNotFound(string $method)
    {
        throw new BadMethodCallException(__('eloquent-kompact::messages.method_not_found', [
            'class' => static::class,
            'method' => $method,
        ]));
    }
}