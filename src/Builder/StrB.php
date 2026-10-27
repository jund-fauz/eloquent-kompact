<?php

namespace JundFauz\EloquentKompact\Builder;

use JundFauz\EloquentKompact\Internal\Concerns\Builder;

/**
 * @method static self initiate(string|self $string = '')
 * @method self trim(string $characters = " \n\r\t\v\0")
 * @method self ltrim(string $characters = " \n\r\t\v\0")
 * @method self rtrim(string $characters = " \n\r\t\v\0")
 */
final class StrB extends Builder
{
    protected static ?string $initiationFunction = 'initiate';

    public function __call(string $name, array $arguments)
    {
        if (in_array($name, ['trim', 'ltrim', 'rtrim'])) {
            return $this->set($name(...$arguments));
        }

        self::methodNotFound($name);
    }

    public function replace(array|string $search, array|string $replace): self
    {
        return $this->set(str_replace($search, $replace, $this->result));
    }
}