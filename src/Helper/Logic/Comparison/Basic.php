<?php

namespace JundFauz\EloquentKompact;

use BadMethodCallException;
use ReflectionException;
use function JundFauz\EloquentKompact\Array\flat;

/**
 * @param string|null $what
 * @return string
 * @internal
 */
function is(?string $what): string
{
    return "is_$what";
}

/**
 * @param string $function
 * @return bool
 * @internal
 */
function is_function_eligible(string $function): bool
{
    try {
        $returnType = ((new \ReflectionFunction($function))->getReturnType());
    } catch (ReflectionException) {
        return false;
    }
    return function_exists($function) && same_with(not($returnType instanceof \ReflectionNamedType) ?: $returnType->getName(), 'bool');
}

/**
 * @param $what
 * @return string
 * @internal
 */
function what_to_function($what): string
{
    raw($what);
    return match (true) {
        is_function_eligible($what) => $what,
        is_function_eligible($function = is($what)) => $function,
        is_function_eligible($function = namespace_concat(LIB_NAMESPACE, is($what))) => $function,
        is_function_eligible($function = namespace_concat(LIB_NAMESPACE, $what)) => $function,
        default => throw new BadMethodCallException("Function $function doesn't exists.")
    };
}

/**
 * @param $value
 * @return void
 * @internal
 */
function raw(&$value): void
{
    $value = (string)$value;
}

/**
 * Whether the check is false for the value, e.g. is_not('array', $value) for ! is_array($value).
 * The check is a function returning a bool: $what itself, is_$what, or one of this package
 *
 * @param string $what E.g. 'null', 'array', 'numeric', 'dir' or 'defined'
 * @param mixed $value
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_not($what, $value): bool
{
    return not(what_to_function($what)($value));
}

/**
 * Whether the check is true for every value, nested arrays flattened; true without a value.
 * E.g. is_all('null', $a, $b) for is_null($a) && is_null($b)
 *
 * @param string|null $what See is_not()
 * @param mixed ...$values
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_all(?string $what, ...$values): bool
{
    $function = what_to_function($what);
    flat($values);
    return array_all($values, fn ($value) => $function($value));
}

/**
 * Whether the check is true for at least one value, nested arrays flattened; false without a value
 *
 * @param string|null $what See is_not()
 * @param mixed ...$values
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_any(?string $what, ...$values): bool
{
    $function = what_to_function($what);
    flat($values);
    return array_any($values, fn ($value) => $function($value));
}

/**
 * Whether the check is false for every value, nested arrays flattened
 *
 * @param string|null $what See is_not()
 * @param mixed ...$values
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_all_not(?string $what, ...$values): bool
{
    return not(is_any($what, ...$values));
}

/**
 * Whether the check is false for at least one value, nested arrays flattened
 *
 * @param string|null $what See is_not()
 * @param mixed ...$values
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 */
function is_any_not(?string $what, ...$values): bool
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
    return array_any($whats, fn ($what) => what_to_function($what)($value));
}
