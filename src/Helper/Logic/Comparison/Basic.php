<?php

namespace JundFauz\EloquentKompact;

use BadMethodCallException;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use function JundFauz\EloquentKompact\Array\flat;
use function JundFauz\EloquentKompact\Array\is_count_many;
use function JundFauz\EloquentKompact\Array\merge;
use function JundFauz\EloquentKompact\Array\merge_as_array;
use function JundFauz\EloquentKompact\Array\simple_wrap;
use function JundFauz\EloquentKompact\Array\wrap;
use function JundFauz\EloquentKompact\String\str;
use function JundFauz\EloquentKompact\String\strs;

/**
 * is_$what, or $what itself when it already starts with is
 *
 * @param string $what
 * @return string
 * @internal
 */
function is(string $what): string
{
    $what = Str::of($what, true)->snake();
    if (not($what->startsWith('is_'))) {
        $what->addAtStart('is_');
    }
    return (string) $what;
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
 * once and then cached. A $what with the word not (in snake case) runs the check without it, negated,
 * e.g. execute_what_function('not_in', $needle, $haystack) for ! in($needle, $haystack),
 * or 'is_not_null' and 'notNull' for ! is_null()
 *
 * @param string $what
 * @param mixed ...$parameters The arguments of the check
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for $what
 * @internal
 */
function execute_what_function(string $what, ...$parameters): bool
{
    static $cache = [], $rawCache = [];
    if (! isset($cache[$what])) {
        [$isNegation, $rawFunction] = is_negation_and_get_raw_function($what);
        $cache[$what] = [$isNegation, $rawCache[$rawFunction] ??= resolve_what_function($rawFunction)];
    }
    [$isNegation, $function] = $cache[$what];
    $result = $function(...$parameters);
    return $isNegation
        ? not($result)
        : $result;
}

/**
 * Whether the name of a check has the word not (in snake case), and the name without every not,
 * e.g. [true, 'is_null'] for 'is_not_null', [false, 'in'] for 'in'
 *
 * @param string $function
 * @return array{bool, string}
 * @internal
 */
function is_negation_and_get_raw_function(string $function): array
{
    /** @var Collection $words */
    $words = strs($function, 'snake', explode: '_');
    $isNegativeFunction = $words->contains('not') && is_count_many($words);
    $rawFunction = $isNegativeFunction
        ? chain($words, diff: ['not'], implode: '_')
        : $function;
    return [$isNegativeFunction, $rawFunction];
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
 * One check of is_any_of(), is_all_of(), all() or any(): a named argument is the check with its extra
 * parameters, a positional one is the check alone; the value, when it is given, goes to the check first
 *
 * @param mixed $value The check, or the extra parameters of a named one
 * @param int|string $key The name of a named check
 * @param mixed $mainValue The value that is checked, null included; all() and any() give none
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for the check
 * @internal
 */
function execute(mixed $value, int|string $key, mixed $mainValue = null): bool
{
    $arguments = [($isKeyString = is_string($key)) ? $key : $value];
    if (same_with(func_num_args(), 3)) {
        $arguments[] = $mainValue;
    }
    if ($isKeyString) {
        $arguments = merge($arguments, simple_wrap($value));
    }
    return execute_what_function(...$arguments);
}

/**
 * Whether every check is true, stopping at the first false one; true without a check. A check is a named argument with its arguments,
 * e.g. all(method_exists: [$class, $method], same_with: [$a, $b]); see is_not() for the names
 *
 * @param mixed ...$methodAndParameters
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for a check that is reached
 */
function all(mixed ...$methodAndParameters): bool
{
    return array_all($methodAndParameters, execute(...));
}

/**
 * Whether at least one check is true, stopping at the first true one; false without a check. A check is a named argument with its arguments,
 * e.g. any(method_exists: [$class, $method], same_with: [$a, $b]); see is_not() for the names
 *
 * @param mixed ...$methodAndParameters
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for a check that is reached
 */
function any(mixed ...$methodAndParameters): bool
{
    return array_any($methodAndParameters, execute(...));
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
 * Whether at least one of the checks is true for the value, stopping at the first true one.
 * E.g. is_any_of($value, 'int', 'string') for is_int($value) || is_string($value). Extra parameters
 * go in a named argument, e.g. is_any_of($value, 'int', in: 'min,max') for ... || in($value, 'min,max')
 *
 * @param mixed $value
 * @param mixed ...$whats See is_not()
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for a check that is reached
 */
function is_any_of(mixed $value, mixed ...$whats): bool
{
    return array_any($whats, fn ($arrValue, $key) => execute($arrValue, $key, $value));
}

/**
 * Whether every check is true for the value, stopping at the first false one; true without a check.
 * E.g. is_all_of($value, 'string', 'numeric') for is_string($value) && is_numeric($value). Extra
 * parameters go in a named argument, like is_any_of()
 *
 * @param mixed $value
 * @param mixed ...$whats See is_not()
 * @return bool
 * @throws BadMethodCallException When no function returning a bool is found for a check that is reached
 */
function is_all_of(mixed $value, mixed ...$whats): bool
{
    return array_all($whats, fn ($arrValue, $key) => execute($arrValue, $key, $value));
}

/**
 * Calls the methods on $object one after another, each on what the previous one returned, and returns the last result.
 * - A positional string calls the method without arguments ('snake'; arrays passed without ... are not flattened).
 * - A named argument (or an unpacked [method => parameters] array) passes its parameters through wrap():
 *   one non-array value is one argument (startsWith: 'is'), a list passes several positional arguments
 *   (replace: ['-', '_']), an associative array passes named parameters to that method
 *   (replace: ['search' => '-', 'replace' => '_']), and an array parameter itself is wrapped once more
 *   (diff: [['not']], swap: [['a' => 'b']]).
 * - A blank() value ('', ' ', [], null) calls the method without arguments (lower: '', trim: []), so parameterless
 *   methods can follow named arguments; wrap a single blank argument as [' '].
 * - A method name cannot appear twice as a named argument in one call.
 *
 * @param object $object
 * @param mixed ...$methods A method name, or method name => its parameter(s) (blank for none)
 * @return mixed What the last method returns
 */
function chain(object $object, ...$methods): mixed
{
    foreach ($methods as $key => $value) {
        $object = match (true) {
            blank($value) => $object->{$key}(),
            is_string($key) => $object->{$key}(...wrap($value)),
            default => $object->{$value}()
        };
    }
    return $object;
}
