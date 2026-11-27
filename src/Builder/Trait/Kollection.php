<?php

namespace JundFauz\EloquentKompact\Builder\Trait;

use JundFauz\EloquentKompact\Builder\Str;
use function JundFauz\EloquentKompact\Array\filter_filled;
use function JundFauz\EloquentKompact\Array\get_or;
use function JundFauz\EloquentKompact\is_not_null;

trait Kollection
{
    /**
     * Filter the collection items using PHP's native `array_filter`.
     *
     * @param callable|null $callback
     * @return static
     * @final
     */
    public function normalFilter(?callable $callback = null): static
    {
        return $this->newInstance(array_filter($this->items, $callback));
    }

    /**
     * Filter the collection to keep only filled items.
     *
     * @return static
     * @final
     */
    public function filterFilled(): static
    {
        return $this->newInstance(filter_filled($this->items));
    }

    /**
     * Remove all `null` items from the collection.
     *
     * @return static
     * @final
     */
    public function deleteNull(): static
    {
        return $this->normalFilter(is_not_null(...));
    }

    /**
     * Get an item from the collection by key, wrapping arrays as collections and strings as `Str`.
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->return(parent::get($key, $default));
    }

    /**
     * Get the item before the given item, wrapping arrays as collections and strings as `Str`.
     *
     * @param mixed $value
     * @param bool $strict
     * @return mixed
     */
    public function before($value, $strict = false)
    {
        return $this->return($this->toBase()->before($value, $strict));
    }

    /**
     * Get the item after the given item, wrapping arrays as collections and strings as `Str`.
     *
     * @param mixed $value
     * @param bool $strict
     * @return mixed
     */
    public function after($value, $strict = false)
    {
        return $this->return($this->toBase()->after($value, $strict));
    }

    /**
     * Return the first non-null value matching one of the given dot-notated keys.
     *
     * @param string ...$keys
     * @return mixed
     * @final
     */
    public function getOr(string ...$keys): mixed
    {
        return $this->return(get_or($this->items, ...$keys));
    }

    /**
     * Return the first non-null value matching one of the given keys, or the wrapped default value.
     *
     * @param mixed $default
     * @param string ...$keys
     * @return mixed
     * @final
     */
    public function getOrWithDefault(mixed $default, string ...$keys): mixed
    {
        return $this->getOr(...$keys) ?? $this->return($default);
    }

    /**
     * Return the first matching key's value or the default value wrapped as a new collection instance.
     *
     * @param mixed $default
     * @param string ...$keys
     * @return static
     * @final
     */
    public function getOrWithDefaultAsCollection(mixed $default, string ...$keys): static
    {
        return $this->newInstance($this->getOrWithDefault($default, ...$keys));
    }

    /**
     * Sum each given column and return an associative array keyed by column name.
     *
     * @param string ...$columns
     * @return array<string, int|float>
     * @final
     */
    public function sumAndCompact(string ...$columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            $result[$column] = $this->sum($column);
        }
        return $result;
    }

    /**
     * Concatenate item values into a Kompact `Str` instance.
     *
     * @param callable|string|null $value
     * @param string|null $glue
     * @return Str
     * @final
     */
    public function implodeAsStr(callable|string|null $value = '', $glue = null): Str
    {
        return Str::of($this->implode($value, $glue));
    }

    /**
     * Create a new collection instance with the given items.
     *
     * @param mixed $items
     * @return static
     */
    protected function newInstance($items = []): static
    {
        return new static($items);
    }

    /**
     * Wrap arrays into a collection instance and strings into `Str`, returning other types unchanged.
     *
     * @param mixed $data
     * @return mixed|static|Str
     */
    private function return(mixed $data): mixed
    {
        return match (true) {
            is_array($data) => $this->newInstance($data),
            is_string($data) => Str::of($data),
            default => $data
        };
    }
}
