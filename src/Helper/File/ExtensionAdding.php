<?php

namespace JundFauz\EloquentKompact;

use const JundFauz\EloquentKompact\Extension\PHP;

/**
 * The filename with the extension, e.g. 'config.json'
 *
 * @param string $filename
 * @param string $extension
 * @return string
 */
function add_extension(string $filename, string $extension): string
{
    return relation_concat($filename, $extension);
}

/**
 * The filename with the .php extension
 *
 * @param string $filename
 * @return string
 */
function add_php_extension(string $filename): string
{
    return add_extension($filename, PHP);
}
