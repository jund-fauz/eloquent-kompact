<?php

namespace JundFauz\EloquentKompact\Cast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Eloquent attribute cast between UUID strings in the database and `Ramsey\Uuid\UuidInterface` instances.
 *
 * @implements CastsAttributes<UuidInterface, UuidInterface|string>
 * @final
 */
class UuidCast implements CastsAttributes
{
    /**
     * Cast the given database value into a `UuidInterface` instance, or `null` when empty.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return UuidInterface|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?UuidInterface
    {
        return $value ? Uuid::fromString($value) : null;
    }

    /**
     * Prepare the given `UuidInterface` or scalar value for storage.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value instanceof UuidInterface ? (string)$value : $value;
    }
}
