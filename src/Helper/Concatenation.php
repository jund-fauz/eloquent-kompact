<?php

namespace JundFauz\EloquentKompact;

use function JundFauz\EloquentKompact\Array\flat;

/**
 * The values joined with a space, nested arrays flattened
 *
 * @param string|array ...$values
 * @return string
 */
function word_concat(string|array ...$values): string
{
    flat($values);
    return join(' ', $values);
}

/**
 * The values joined with ':', e.g. a validation rule like 'decimal:2'
 *
 * @param string|array ...$values
 * @return string
 */
function schema_concat(string|array ...$values): string
{
    flat($values);
    return join(':', $values);
}

/**
 * The values joined with '.', e.g. a nested relation like 'posts.user'
 *
 * @param string|array ...$values
 * @return string
 */
function relation_concat(string|array ...$values): string
{
    flat($values);
    return join('.', $values);
}
