<?php

namespace JundFauz\EloquentKompact\Helper;

use BadMethodCallException;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use function JundFauz\EloquentKompact\Array\flat;

/**
 * Static delimiter-based string joiner backed by `$tokens`.
 *
 * @method static string path(string|array ...$values)
 * @method static string word(string|array ...$values)
 * @method static string schema(string|array ...$values)
 * @method static string relation(string|array ...$values)
 * @method static string snake(string|array ...$values)
 * @method static string strip(string|array ...$values)
 * @final
 */
class Concat
{
    use ErrorThrow;

    /**
     * Map of static method names to delimiter strings.
     *
     * @var array<string, string>
     */
    public static array $tokens = [
        'path' => '/',
        'word' => ' ',
        'schema' => ':',
        'relation' => '.',
        'snake' => '_',
        'strip' => '-'
    ];

    /**
     * Joins `$arguments` (nested arrays flattened) with the delimiter registered under `$name` in `$tokens`.
     *
     * @param string $name
     * @param array $arguments
     * @return string
     * @throws BadMethodCallException When `$name` is not registered in `$tokens`
     */
    public static function __callStatic(string $name, array $arguments): string
    {
        if (array_key_exists($name, self::$tokens)) {
            flat($arguments);
            return join(self::$tokens[$name], $arguments);
        }

        self::methodNotFound($name);
    }
}