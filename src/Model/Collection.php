<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Model;
use JundFauz\EloquentKompact\Builder\Trait\Kollection;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
    use Kollection;

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

    public function toBase(): \JundFauz\EloquentKompact\Builder\Collection
    {
        return new \JundFauz\EloquentKompact\Builder\Collection($this);
    }
}