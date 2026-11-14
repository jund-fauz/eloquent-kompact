<?php

namespace JundFauz\EloquentKompact\Array;

use Countable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use JundFauz\EloquentKompact\Builder\Collection;
use function JundFauz\EloquentKompact\cast_as;
use function JundFauz\EloquentKompact\collect as collect;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\same_with;

/**
 * Flattens the array in place, nested arrays included
 *
 * @param array $array
 * @return void
 */
function flat(array &$array): void
{
    $array = Arr::flatten($array);
}

/**
 * array_map() that also passes the key to the callback, as fn ($value, $key). The keys are not kept
 *
 * @param callable|null $callable
 * @param array $array
 * @return array
 */
function map(?callable $callable, array $array): array
{
    return array_map($callable, $array, array_keys($array));
}

/**
 * Removes the keys from the array and returns their values: the value for one key, key => value pairs for several.
 * A missing key gives null; dot notation is supported
 *
 * @param array $array
 * @param string ...$keys
 * @return mixed
 */
function pull(array &$array, string ...$keys): mixed
{
    $values = [];

    foreach ($keys as $key) {
        $values[$key] = Arr::pull($array, $key);
    }

    return count($values) === 1 ? $values[$keys[0]] : $values;
}

/**
 * pull(), with the values of several keys as a list in the given order
 *
 * @param array $array
 * @param string ...$keys
 * @return mixed
 */
function pull_list(array &$array, string ...$keys): mixed
{
    $result = pull($array, ...$keys);
    return count($keys) > 1 ? array_values($result) : $result;
}

/**
 * The first value of the keys that is not null; dot notation is supported
 *
 * @param array $array
 * @param string ...$keys
 * @return mixed
 */
function get_or(array $array, string ...$keys): mixed
{
    return array_reduce($keys, fn ($acc, $key) => $acc ?? Arr::get($array, $key));
}

/**
 * The value wrapped in an array, an empty array for null
 *
 * @param mixed $data
 * @return array
 */
function wrap(mixed $data): array
{
    return Arr::wrap($data);
}

/**
 * The values that are filled(), with their keys
 *
 * @param array $data
 * @return array
 */
function filter_filled(array $data): array
{
    return array_filter($data, 'filled');
}

/**
 * Renames a key in place; the new key is moved to the end and replaces an existing key with the same name
 *
 * @param array $array
 * @param string $oldKey
 * @param string $newKey
 * @return void
 */
function key_change(array &$array, string $oldKey, string $newKey): void
{
    if ($oldKey === $newKey) {
        return;
    }
    $array[$newKey] = $array[$oldKey];
    unset($array[$oldKey]);
}

/**
 * Every key then every value, as one flat list; [key, value] for a single item
 *
 * @param array $array
 * @return array
 */
function key_values($array): array
{
    $keys = array_keys($array);
    $values = array_values($array);

    return merge_as_array(
        normalize($keys),
        normalize($values)
    );
}

/**
 * The item of a single item list, otherwise the list itself
 *
 * @param array $data
 * @return mixed
 */
function normalize(array $data): mixed
{
    return count($data) === 1 ? $data[0] : $data;
}

/**
 * The value as an array: a collection as its items, any other value wrapped by wrap()
 *
 * @param mixed $data
 * @return array
 */
function cast(mixed $data): array
{
    return match (true) {
        $data instanceof SupportCollection => $data->toArray(),
        default => wrap($data)
    };
}

/**
 * The value, or $defaultValue when it is null or missing, without a warning for a missing key.
 * With $cast, the result is cast by cast_as()
 *
 * @param mixed $data E.g. $array['key'], passed by reference so a missing key does not warn
 * @param mixed $defaultValue
 * @param string|null $cast A cast of Laravel, e.g. 'integer' or an enum class
 * @return mixed
 */
function get(mixed &$data, mixed $defaultValue = null, ?string $cast = null): mixed
{
    $result = $data ?? $defaultValue;
    return is_not_null($cast) ? cast_as($result, $cast) : $result;
}

/**
 * get() as a Collection of this package
 *
 * @param mixed $data E.g. $array['key'], passed by reference so a missing key does not warn
 * @param mixed $defaultValue
 * @return Collection
 */
function array_get_as_collection(mixed &$data, mixed $defaultValue = null): Collection
{
    return collect(get($data, $defaultValue));
}

/**
 * The items with an int key, with their keys
 *
 * @param array $from
 * @return array
 */
function get_array_list(array $from): array
{
    return array_filter($from, 'is_int', ARRAY_FILTER_USE_KEY);
}

/**
 * Key => value of the given keys, null for a missing key; a named argument renames the key, e.g. compact($array, x: 'b')
 *
 * @param array $array
 * @param string ...$keys
 * @return array
 */
function compact(array $array, string ...$keys): array
{
    $result = [];
    foreach ($keys as $key => $keyValue) {
        if (is_string($key)) {
            $result[$key] = get($array[$keyValue]);
        } else {
            $result[$keyValue] = get($array[$keyValue]);
        }
    }
    return $result;
}

/**
 * compact(), also removing the keys from the array
 *
 * @param array $array
 * @param string ...$keys
 * @return array
 */
function compact_and_unset(array &$array, string ...$keys): array
{
    $result = [];
    foreach ($keys as $key => $keyValue) {
        if (is_string($key)) {
            $result[$key] = get($array[$keyValue]);
        } else {
            $result[$keyValue] = get($array[$keyValue]);
        }
        unset($array[$keyValue]);
    }
    return $result;
}

/**
 * An array with the same value for every key
 *
 * @param mixed $data
 * @param string ...$keys
 * @return array
 */
function assign_as(mixed $data, string ...$keys): array
{
    $result = [];
    foreach ($keys as $key) {
        $result[$key] = $data;
    }
    return $result;
}

/**
 * Shorthand for array_merge()
 *
 * @param array ...$values
 * @return array
 */
function merge(array ...$values): array
{
    return array_merge(...$values);
}

/**
 * The associative arrays merged with their keys when every filled value is one,
 * otherwise every value as one flat list without the keys. Collections count as their items
 *
 * @param mixed ...$values
 * @return array
 */
function merge_as_array(mixed ...$values): array
{
    $values = array_map(fn ($value) => $value instanceof SupportCollection ? $value->all() : $value, $values);
    $filled = filter_filled($values);

    if ($filled && array_all($filled, fn ($value) => is_associative($value))) {
        return merge(...array_values($filled));
    }

    flat($values);
    return $values;
}

/**
 * Whether the value is an array that is not a list
 *
 * @param mixed $data
 * @return bool
 */
function is_associative(mixed $data): bool
{
    return is_array($data) && ! array_is_list($data);
}

/**
 * Whether the count is 0
 *
 * @param array|Countable $data
 * @return bool
 */
function is_count_zero(array|Countable $data): bool
{
    return same_with(count($data), 0);
}

/**
 * Whether the count is 1
 *
 * @param array|Countable $data
 * @return bool
 */
function is_count_one(array|Countable $data): bool
{
    return same_with(count($data), 1);
}

/**
 * Whether the count is more than 1
 *
 * @param array|Countable $data
 * @return bool
 */
function is_count_many(array|Countable $data): bool
{
    return count($data) > 1;
}

/**
 * Whether the value is an array or a collection
 *
 * @param mixed $data
 * @return bool
 */
function is_array_or_collection(mixed $data): bool
{
    return is_array($data) || $data instanceof SupportCollection;
}

/**
 * in_array() that also takes a collection
 *
 * @param mixed $needle
 * @param array|SupportCollection|Collection $haystack
 * @param bool $strict
 * @return bool
 */
function in(mixed $needle, array|SupportCollection|Collection $haystack, bool $strict = false): bool
{
    return in_array($needle, cast($haystack), $strict);
}

/**
 * Strings and arrays merged into one array: an array keeps its keys, a string with a string key becomes key => value
 *
 * @param array $data
 * @return array
 */
function merge_mixed(array $data): array
{
    return is_count_zero($data)
        ? $data
        : merge(...map(
            fn (string|array $data, string|int $key) => match (true) {
                is_array($data) => $data,
                is_string($key) => [$key => $data],
                default => [$data]
            },
            $data
        ));
}
