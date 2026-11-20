<?php

namespace JundFauz\EloquentKompact;

use BadMethodCallException;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use function JundFauz\EloquentKompact\Array\flat;
use function JundFauz\EloquentKompact\Array\merge_as_array;
use function JundFauz\EloquentKompact\String\str;

/**
 * is_$what, or $what itself when it already starts with is
 *
 * @param string|null $what
 * @return string
 * @internal
 */
function is(?string $what): string
{
    return str('startsWith', $what, 'is')
        ? $what
        : "is_$what";
}

/**
 * @param string $function
 * @return bool
 * @internal
 */
function is_function_eligible(string $function): bool
{
    try {
        $returnType = (new ReflectionFunction($function))->getReturnType();
    } catch (ReflectionException) {
        return false;
    }
    return function_exists($function) && same_with(not($returnType instanceof ReflectionNamedType) ?: $returnType->getName(), 'bool');
}

/**
 * Runs the function returning a bool that checks $what with the parameters. The function is found
 * once and then cached. A $what starting with not_ runs the check without it, negated,
 * e.g. execute_what_function('not_in', $needle, $haystack) for ! in($needle, $haystack)
 *
 * @param $what
 * @param mixed ...$parameters The arguments of the check
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 * @internal
 */
function execute_what_function($what, ...$parameters): bool
{
    static $cache = [];
    $what = (string)$what;
    $result = ($cache[$what] ??= resolve_what_function($what))(...$parameters);
    return str('startsWith', $what, 'not_')
        ? not($result)
        : $result;
}

/**
 * The name of the function returning a bool that checks $what (a leading not_ removed), without the
 * cache: $what, is_$what or array_is_$what, globally or in this package (its String and Array helpers included)
 *
 * @param string $what
 * @return string
 * @throws BadMethodCallException When no function returning a bool is found for $what
 * @internal
 */
function resolve_what_function(string $what): string
{
    $namespaceVariations = [null, '', 'String', 'Array'];
    if (str('startsWith', $what, 'not_')) {
        $what = str('removeStart', $what, 'not_');
    }
    $basicVariations = merge_as_array($what, not_same_with($isWhat = is($what), $what) ? $isWhat : [], "array_$isWhat");
    foreach ($namespaceVariations as $namespaceVariation) {
        foreach ($basicVariations as $basicVariation) {
            if (is_not_null($namespaceVariation) && str('startsWith', $what, 'array_')) {
                continue;
            }
            $function = is_not_null($namespaceVariation)
                ? namespace_concat(...merge_as_array(LIB_NAMESPACE, $namespaceVariation ?: [], $basicVariation))
                : $basicVariation;
            if (is_function_eligible($function)) {
                return $function;
            }
        }
    }
    throw new BadMethodCallException("$what doesn't found in any context.");
}

/**
 * Whether the check is false for the value, e.g. is_not('array', $value) for ! is_array($value).
 * The check is a function returning a bool: $what itself, is_$what, or one of this package
 * (its String and Array helpers included). Every value is passed to the check as its argument,
 * e.g. is_not('in', $needle, $haystack) for ! in($needle, $haystack)
 *
 * @param string $what E.g. 'null', 'array', 'numeric', 'dir', 'defined', 'count_one' or 'in'
 * @param mixed ...$value The arguments of the check
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_not(string $what, mixed ...$value): bool
{
    return not(execute_what_function($what, ...$value));
}

/**
 * Whether the check is true for every value, nested arrays flattened; true without a value.
 * E.g. is_all('null', $a, $b) for is_null($a) && is_null($b)
 *
 * @param string $what See is_not()
 * @param mixed ...$values
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_all(string $what, ...$values): bool
{
    flat($values);
    return array_all($values, fn ($value) => execute_what_function($what, $value));
}

/**
 * Whether the check is true for at least one value, nested arrays flattened; false without a value
 *
 * @param string $what See is_not()
 * @param mixed ...$values
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_any(string $what, ...$values): bool
{
    flat($values);
    return array_any($values, fn ($value) => execute_what_function($what, $value));
}

/**
 * Whether the check is false for every value, nested arrays flattened
 *
 * @param string $what See is_not()
 * @param mixed ...$values
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_all_not(string $what, ...$values): bool
{
    return not(is_any($what, ...$values));
}

/**
 * Whether the check is false for at least one value, nested arrays flattened
 *
 * @param string $what See is_not()
 * @param mixed ...$values
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_any_not(string $what, ...$values): bool
{
    return not(is_all($what, ...$values));
}

/**
 * Whether at least one of the checks is true for the value, stopping at the first true one.
 * E.g. is_any_of($value, 'int', 'string') for is_int($value) || is_string($value)
 *
 * @param mixed $value
 * @param string ...$whats See is_not()
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for a check that is reached
 */
function is_any_of(mixed $value, string ...$whats): bool
{
    return array_any($whats, fn ($what) => execute_what_function($what, $value));
}
