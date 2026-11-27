<?php

namespace JundFauz\EloquentKompact;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Stringable;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Internal\Helper\Caster;
use function JundFauz\EloquentKompact\Array\merge_mixed;

/**
 * The value cast with a cast of Laravel, e.g. 'integer', 'decimal:2' or an enum class; the value as it is without a cast
 *
 * @param mixed $value
 * @param string|null $cast
 * @return mixed
 */
function cast_as(mixed $value, ?string $cast = null): mixed
{
    if (is_null($cast)) {
        return $value;
    }

    static $caster;
    $caster ??= new Caster;

    return $caster->castValue($value, $cast);
}

/**
 * Property => value of the object, e.g. a model's column => value after casting; a property that is not
 * set is null. A property can be given in an array, and a string key, in an array or as a named argument,
 * renames it, e.g. object_compact($user, 'id', ['mail' => 'email']) or object_compact($user, nama: 'name')
 *
 * @param object $object
 * @param string|array ...$variables
 * @return array
 */
function object_compact(object $object, string|array ...$variables): array
{
    $result = [];
    foreach (merge_mixed($variables) as $key => $value) {
        $result[is_string($key) ? $key : $value] = ($tempResult = $object->{$value} ?? null) instanceof Arrayable
            ? $tempResult->toArray()
            : $tempResult;
    }
    return $result;
}

/**
 * A Collection of this package: the items of one argument, or every argument as an item
 *
 * @param mixed ...$data
 * @return Collection
 */
function collect(mixed ...$data): Collection
{
    return new Collection(match (count($data)) {
        1 => $data[0],
        0 => [],
        default => $data
    });
}

/**
 * Chains the methods on a Collection of $array via chain(), returning a plain array for a Collection,
 * a plain string for a Stringable, or what the last method returns otherwise
 *
 * @param array|SupportCollection $array
 * @param mixed ...$methods A Collection method name, or method name => its parameter(s) (blank for none)
 * @return mixed
 * @see chain()
 */
function collects(array|SupportCollection $array, mixed ...$methods): mixed
{
    $result = chain(collect($array), ...$methods);
    return match (true) {
        $result instanceof SupportCollection => $result->toArray(),
        $result instanceof Stringable => $result->value(),
        default => $result
    };
}

/**
 * Whether the app runs locally or runs its unit tests
 *
 * @return bool
 */
function is_app_local_or_test(): bool
{
    return App::isLocal() || App::runningUnitTests();
}
