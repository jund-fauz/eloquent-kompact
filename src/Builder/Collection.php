<?php

namespace JundFauz\EloquentKompact\Builder;

use function JundFauz\EloquentKompact\array_filter_filled;
use function JundFauz\EloquentKompact\array_get_or;

class Collection extends \Illuminate\Support\Collection
{
    public function filterFilled(): static
    {
        return $this->newInstance(array_filter_filled($this->items));
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

    protected function newInstance($items = []): static
    {
        return new static($items);
    }

    private function return(mixed $data): mixed
    {
        return match (true) {
            is_array($data) => $this->newInstance($data),
            is_string($data) => Str::of($data),
            default => $data
        };
    }
}