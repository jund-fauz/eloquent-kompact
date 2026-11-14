<?php

namespace JundFauz\EloquentKompact;

use JundFauz\EloquentKompact\Builder\Str;
use function JundFauz\EloquentKompact\Array\flat;

/**
 * The parts joined with '\', each part trimmed of '\'
 *
 * @param string|array ...$folderOrPackages
 * @return string
 */
function namespace_concat(string|array ...$folderOrPackages): string
{
    flat($folderOrPackages);
    return join(NAMESPACE_DELIMITER, array_map(fn (string $part) => trim($part, NAMESPACE_DELIMITER), $folderOrPackages));
}

/**
 * The parts joined with '/'
 *
 * @param string|array ...$folderOrPackages
 * @return string
 */
function path_concat(string|array ...$folderOrPackages): string
{
    flat($folderOrPackages);
    return join(PATH_DELIMITER, $folderOrPackages);
}

/**
 * The full path inside app/ of a namespace or a path, e.g. 'App\Models' becomes app_path('Models')
 *
 * @param string $path
 * @return string
 */
function full_path_on_app(string $path): string
{
    return app_path(
        Str::of($path)
            ->replace(NAMESPACE_DELIMITER, PATH_DELIMITER)
            ->replaceByRegex('/^app(\/|$)/i', '')
            ->trim(PATH_DELIMITER)
            ->value()
    );
}

/**
 * The string trimmed of '\' and '/'
 *
 * @param string $string
 * @return string
 */
function namespace_trim(string $string): string
{
    return trim($string, '\\/');
}
