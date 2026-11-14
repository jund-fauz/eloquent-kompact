<?php

namespace JundFauz\EloquentKompact;

use function JundFauz\EloquentKompact\Array\flat;

/**
 * Whether every value has the type, or one of the types; nested arrays are flattened.
 * The type is checked by the built-in is_$type function of PHP, e.g. 'int' for is_int()
 *
 * @param string|array $type E.g. 'int', or ['int', 'string']
 * @param mixed ...$values
 * @return bool
 */
function is_all_type_of(string|array $type, ...$values): bool
{
    flat($values);
    return array_all(
        $values,
        fn ($value) => is_array($type)
            ? array_any($type, fn ($type) => ($method = is($type)) && $method($value))
            : is($type)($value)
    );
}

/**
 * Whether at least one value does not have the type, or none of the types; see is_all_type_of()
 *
 * @param string|array $type
 * @param mixed ...$values
 * @return bool
 */
function is_any_not_type_of(string|array $type, ...$values): bool
{
    return not(is_all_type_of($type, ...$values));
}
