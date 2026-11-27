<?php

namespace JundFauz\EloquentKompact;

use function JundFauz\EloquentKompact\Array\flat;
use function JundFauz\EloquentKompact\String\concat;

const path = '/';
const word = ' ';
const schema = ':';
const relation = '.';
const snake = '_';
const strip = '-';

/**
 * The values joined with the delimiter, nested arrays flattened, e.g. concat_by(relation, 'posts', ['user'])
 * for 'posts.user'. The delimiters of the other *_concat() functions are the constants path, word, schema,
 * relation, snake and strip
 *
 * @param string $by The delimiter
 * @param string|array ...$values
 * @return string
 */
function concat_by(string $by, string|array ...$values): string
{
    flat($values);
    return join($by, $values);
}

/**
 * Joins values with a delimiter and surrounds the result with that delimiter.
 *
 * @param string $between
 * @param string|array ...$values
 * @return string
 */
function concat_between(string $between, string|array ...$values): string
{
    flat($values);
    return concat($between, concat_by($between, ...$values), $between);
}

/**
 * The values joined with '-', nested arrays flattened
 *
 * @param string|array ...$values
 * @return string
 */
function strip_concat(string|array ...$values): string
{
    return concat_by(strip, $values);
}

/**
 * The values joined with '_', nested arrays flattened
 *
 * @param string|array ...$values
 * @return string
 */
function snake_concat(string|array ...$values): string
{
    return concat_by(snake, $values);
}

/**
 * The values joined with a space, nested arrays flattened
 *
 * @param string|array ...$values
 * @return string
 */
function word_concat(string|array ...$values): string
{
    return concat_by(word, ...$values);
}

/**
 * The values joined with ':', e.g. a validation rule like 'decimal:2'
 *
 * @param string|array ...$values
 * @return string
 */
function schema_concat(string|array ...$values): string
{
    return concat_by(schema, ...$values);
}

/**
 * The values joined with '.', e.g. a nested relation like 'posts.user'
 *
 * @param string|array ...$values
 * @return string
 */
function relation_concat(string|array ...$values): string
{
    return concat_by(relation, ...$values);
}
