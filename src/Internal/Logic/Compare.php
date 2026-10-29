<?php

namespace JundFauz\EloquentKompact\Internal\Logic;

use Closure;
use JundFauz\EloquentKompact\Builder\BoolB;

/**
 * @internal
 */
final readonly class Compare
{
    private function __construct(private mixed $firstValue, private Closure $resolve) { }

    public static function of(mixed $value, Closure $resolve): self
    {
        return new self($value, $resolve);
    }

    public function sameWith(mixed $otherValue): BoolB
    {
        return ($this->resolve)($this->firstValue === $otherValue);
    }

    public function notSameWith(mixed $otherValue): BoolB
    {
        return ($this->resolve)($this->firstValue !== $otherValue);
    }
}