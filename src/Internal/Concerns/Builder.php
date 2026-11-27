<?php

namespace JundFauz\EloquentKompact\Internal\Concerns;

use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use Stringable;
use function JundFauz\EloquentKompact\is_not_null;

/**
 * Base immutable string-expression builder.
 *
 * @internal
 */
class Builder implements Stringable
{
    use ErrorThrow;

    protected string $result = '';
    protected static ?string $initiationFunction = null;

    /**
     * Dynamically instantiate the builder when called via the configured `$initiationFunction` name.
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return static
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (is_not_null(static::$initiationFunction) && $name === static::$initiationFunction) {
            return (new static)->set((string) ($arguments[0] ?? ''));
        }

        self::methodNotFound($name);
    }

    /**
     * Return a new builder instance holding the given string result.
     *
     * @param string $newResult
     * @return static
     */
    protected function set(string $newResult): static
    {
        $newClass = new static;
        $newClass->result = $newResult;
        return $newClass;
    }

    /**
     * Return the built string representation.
     *
     * @return string
     */
    public function build(): string
    {
        return $this->result;
    }

    /**
     * Cast the builder instance to a string via `build()`.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->build();
    }
}
