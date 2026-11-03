<?php

namespace JundFauz\EloquentKompact\Builder\Trait;

use JundFauz\EloquentKompact\Builder\Str;
use function JundFauz\EloquentKompact\array_filter_filled;
use function JundFauz\EloquentKompact\array_get_or;
use function JundFauz\EloquentKompact\is_not_null;

trait Kollection
{
    public function filterFilled(): static
    {
        return $this->newInstance(array_filter_filled($this->items));
    }

    public function deleteNull(): static
    {
        return $this->filter(fn ($value) => is_not_null($value));
    }

    public function get($key, $default = null)
    {
        return $this->return(parent::get($key, $default));
    }

    public function before($value, $strict = false)
    {
        return $this->return($this->toBase()->before($value, $strict));
    }

    public function after($value, $strict = false)
    {
        return $this->return($this->toBase()->after($value, $strict));
    }

    public function getOr(string ...$keys): mixed
    {
        return $this->return(array_get_or($this->items, ...$keys));
    }

    public function getOrWithDefault(mixed $default, string ...$keys): mixed
    {
        return $this->getOr(...$keys) ?? $this->return($default);
    }

    public function getOrWithDefaultAsCollection(mixed $default, string ...$keys): static
    {
        return $this->newInstance($this->getOrWithDefault($default, ...$keys));
    }

    public function sumAndCompact(string ...$columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            $result[$column] = $this->sum($column);
        }
        return $result;
    }

    protected function newInstance($items = []): static
    {
        return new static($items);
    }

    /**
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
