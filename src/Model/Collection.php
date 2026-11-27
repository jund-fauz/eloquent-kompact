<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Model;
use JundFauz\EloquentKompact\Builder\Trait\Kollection;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
    use Kollection;

    /**
     * Associative array of `$columns` extracted from the first model in the collection (or nulls when empty).
     *
     * @param string ...$columns
     * @return array<string, mixed>
     * @final
     */
    public function firstAndCompact(string ...$columns): array
    {
        $result = [];
        /** @var ?Model $first */
        $first = $this->first();
        foreach ($columns as $column) {
            $result[$column] = $first?->{$column};
        }
        return $result;
    }

    /**
     * Base Kompact `Builder\Collection` containing the items from this Eloquent collection.
     *
     * @return \JundFauz\EloquentKompact\Builder\Collection
     */
    public function toBase(): \JundFauz\EloquentKompact\Builder\Collection
    {
        return new \JundFauz\EloquentKompact\Builder\Collection($this);
    }
}