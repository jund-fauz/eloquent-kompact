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
 * Calls a static method of Laravel's Str, the method name converted to camel case first,
 * e.g. str('replace_first', 'a', 'b', 'aa') for Str::replaceFirst('a', 'b', 'aa').
 * When Str has no such method, calls it on this package's Str of the first parameter and gives
 * back the string, e.g. str('add_at_start', 'b', 'a') for Str::of('b')->addAtStart('a')->value()
 *
 * @param string $methodName E.g. 'snake', 'replaceFirst' or 'replace_first'
 * @param mixed ...$parameters The subject first when the method is not one of Laravel's Str
 * @return mixed What the Str method returns, or the string
 */
function str(string $methodName, mixed ...$parameters): mixed
{
    $methodName = SupportStr::camel($methodName);
    return match (true) {
        method_exists(SupportStr::class, $methodName) => SupportStr::{$methodName}(...$parameters),
        ($result = Str::of(array_shift($parameters))->{$methodName}(...$parameters)) instanceof Str => $result->value(),
        default => $result
    };
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
    return array_map(fn (mixed $data) => is_array($data) ? stringify($data) : (string) $data, $data);
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

/**
 * Shorthand for Str::beforeLast()
 *
 * @deprecated Will be removed in 3.0. Use str('before_last', $subject, $search)
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
 * @deprecated Will be removed in 3.0. Use str('lower', $subject)
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
 * @deprecated Will be removed in 3.0. Use str('upper', $subject)
 * @param string $subject
 * @return string
 */
function upper(string $subject): string
{
    return SupportStr::upper($subject);
}
