<?php

namespace JundFauz\EloquentKompact\Internal\Concerns;

use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use Stringable;

/**
 * @internal
 */
class Builder implements Stringable
{
    use ErrorThrow;

    protected string $result = '';
    protected static ?string $initiationFunction = null;

    public static function __callStatic(string $name, array $arguments)
    {
        if (is_not_null(static::$initiationFunction) && $name === static::$initiationFunction) {
            return (new static)->set((string) ($arguments[0] ?? ''));
        }

        self::methodNotFound($name);
    }

    protected function set(string $newResult): static
    {
        $newClass = new static;
        $newClass->result = $newResult;
        return $newClass;
    }

    public function build(): string
    {
        return $this->result;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
