<?php

namespace JundFauz\EloquentKompact\String;

use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str as SupportStr;
use Illuminate\Support\Stringable;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use function JundFauz\EloquentKompact\all;
use function JundFauz\EloquentKompact\chain;

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
 * back the string, e.g. str('add_at_start', 'b', 'a') for Str::of('b')->addAtStart('a')->value().
 * A plural method name runs the singular one on every parameter, e.g. str('camels', 'a_b', 'c_d')
 * for [Str::camel('a_b'), Str::camel('c_d')]
 *
 * @param string $methodName E.g. 'snake', 'replaceFirst', 'replace_first' or 'camels'
 * @param mixed ...$parameters The subject first when the method is not one of Laravel's Str
 * @return mixed What the Str method returns, or the string; an array for a plural method name
 */
function str(string $methodName, mixed ...$parameters): mixed
{
    $methodName = SupportStr::camel($methodName);
    $execute = fn ($method, ...$parameters) => match (true) {
        method_exists(SupportStr::class, $method) => SupportStr::{$method}(...$parameters),
        ($result = Str::of(array_shift($parameters))->{$method}(...$parameters)) instanceof Str => $result->value(),
        default => $result
    };
    if (all(method_not_exists: [SupportStr::class, $methodName], same_with: [SupportStr::plural($methodName), $methodName])) {
        $singularMethod = SupportStr::singular($methodName);
        return array_map(fn ($parameter) => $execute($singularMethod, $parameter), $parameters);
    } else {
        return $execute($methodName, ...$parameters);
    }
}

/**
 * Chains the methods of this package's Str on $string via chain(), returning the plain string when the last
 * result is a Stringable, or what the last method returns otherwise. Method names are called directly on Str
 * (camelCase, unlike str()): positional strings take no arguments, named arguments (or unpacked
 * [method => parameters]) pass parameters through wrap() (wrap an array parameter once more, e.g.
 * swap: [['a' => 'b']]), and a blank() value ('', ' ', [], null) calls the method without arguments
 * (wrap a single blank argument as [' ']), e.g. strs('isDirty', 'snake', startsWith: 'is') or
 * strs($name, removeStart: 'has', lower: '', singular: '', sameWith: 'scrap')
 *
 * @see chain()
 * @param string $string
 * @param mixed ...$methods A Str method name (camelCase), or method name => its parameter(s) (blank for none)
 * @return mixed
 */
function strs(string $string, mixed ...$methods): mixed
{
    $result = chain(Str::of($string), ...$methods);
    return $result instanceof Stringable
        ? $result->value()
        : $result;
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
    return array_map(fn (mixed $data) => is_array($data) ? stringify($data) : (string)$data, $data);
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
