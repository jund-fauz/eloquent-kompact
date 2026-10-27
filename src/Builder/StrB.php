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
            $parameters = [$this->result];
            if (isset($arguments[0])) {
                $parameters[] = $arguments[0];
            }
            return $this->set($name(...$parameters));
        }

        self::methodNotFound($name);
    }

    /**
     * @param array<string>|string $search
     * @param array<string>|string $replace
     * @param bool $useRegex
     * @return self
     */
    public function replace(array|string $search, array|string $replace, bool $useRegex = false): self
    {
        $method = $useRegex ? 'preg_replace' : 'str_replace';
        return $this->set($method($search, $replace, $this->result));
    }
}