<?php

namespace JundFauz\EloquentKompact\Cast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?UuidInterface
    {
        return $value ? Uuid::fromString($value) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value instanceof UuidInterface ? (string)$value : $value;
    }
}
