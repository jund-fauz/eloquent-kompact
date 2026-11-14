<?php

namespace JundFauz\EloquentKompact\Helper\Error;

use BadMethodCallException;

trait ErrorThrow
{
    /**
     * @param string $method
     * @return never
     * @throws BadMethodCallException Always, with the translated message that the method does not exist
     */
    protected static function methodNotFound(string $method): never
    {
        throw new BadMethodCallException(__('eloquent-kompact::messages.method_not_found', [
            'class' => static::class,
            'method' => $method,
        ]));
    }
}