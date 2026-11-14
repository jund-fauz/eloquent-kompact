<?php

namespace JundFauz\EloquentKompact\String;

use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str as SupportStr;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;

/**
 * The values joined without a delimiter; arrays and collections are flattened
 *
 * @param string|array|Collection|SupportCollection ...$values
 * @return string
 */
function concat(string|array|Collection|SupportCollection ...$values): string
{
    return join('', collect($values)->map(fn ($value) => $value instanceof SupportCollection ? $value->toArray() : $value)->flatten()->toArray());
}

/**
 * Shorthand for Str::beforeLast()
 *
 * @param string $subject
 * @param string $search
 * @return string
 */
function before_last(string $subject, string $search): string
{
    return SupportStr::beforeLast($subject, $search);
}

/**
 * Shorthand for Str::lower()
 *
 * @param string $subject
 * @return string
 */
function lower(string $subject): string
{
    return SupportStr::lower($subject);
}

/**
 * Shorthand for Str::upper()
 *
 * @param string $subject
 * @return string
 */
function upper(string $subject): string
{
    return SupportStr::upper($subject);
}

/**
 * $value when the condition is truthy, otherwise ''
 *
 * @param mixed $condition
 * @param string $value
 * @return string
 */
function empty_if_false(mixed $condition, string $value): string
{
    return $condition ? $value : '';
}

/**
 * The name of a class constant, e.g. 'Foo\Bar::Baz'
 *
 * @param string $className
 * @param string $constantName
 * @return string
 */
function constant(string $className, string $constantName): string
{
    return "$className::$constantName";
}

/**
 * Every value cast to a string, nested arrays included, keeping the keys; null and false become ''
 *
 * @param array $data
 * @return array
 */
function stringify(array $data): array
{
    return \array_map(fn (mixed $data) => is_array($data) ? stringify($data) : (string) $data, $data);
}

/**
 * rtrim() on every line
 *
 * @param string $value
 * @param string $characters
 * @return string
 * @experimental
 */
function rtrim_multiline(string $value, string $characters = " \n\r\t\v\0"): string
{
    return Str::of($value)
        ->explode("\n")
        ->map(fn ($value) => rtrim($value, $characters))
        ->implode("\n");
}
