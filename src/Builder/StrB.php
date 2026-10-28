<?php

namespace JundFauz\EloquentKompact\Builder;

use Countable;
use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Internal\Concerns\Builder;

/**
 * Credit: Some of StrB methods are backed by Laravel/Illuminate Str methods
 *
 * @method static self initiate(string|self $string = '')
 * @method self trim(string $characters = " \n\r\t\v\0")
 * @method self ltrim(string $characters = " \n\r\t\v\0")
 * @method self rtrim(string $characters = " \n\r\t\v\0")
 *
 * Laravel Str backed method:
 * @method self singular()
 * @method self plural(int|array|Countable $count = 2, bool $prependCount = false) $prependCount requires Laravel 12+
 * @method self pascal(bool $normalize = false) $normalize requires Laravel 13+
 * @method self camel()
 * @method self snake(string $delimiter = '_')
 */
final class StrB extends Builder
{
    protected static ?string $initiationFunction = 'initiate';

    public function __call(string $name, array $arguments)
    {
        $parameters = [$this->result];
        if (in_array($name, ['trim', 'ltrim', 'rtrim'])) {
            if (isset($arguments[0])) {
                $parameters[] = $arguments[0];
            }
            return $this->set($name(...$parameters));
        } elseif (in_array($name, ['singular', 'plural', 'pascal', 'camel', 'snake'])) {
            if (count($arguments)) {
                $parameters = array_merge($parameters, $arguments);
            }
            return $this->set(Str::{$name}(...$parameters));
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

    /**
     * @param array<string>|string $search
     * @param bool $useRegex
     * @return self
     */
    public function remove(array|string $search, bool $useRegex = false): self
    {
        return $this->replace($search, '', $useRegex);
    }

    public function join(string $delimiter, string ...$otherStrings): self
    {
        return $this->set(join($delimiter, [$this->result, ...$otherStrings]));
    }

    public function concat(string ...$otherStrings): self
    {
        return $this->join('', ...$otherStrings);
    }

    public function emptyIfFalse(bool $condition): self
    {
        return $this->set($condition ? $this->result : '');
    }
}