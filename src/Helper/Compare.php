<?php

namespace JundFauz\EloquentKompact\Helper;

/**
 * @deprecated since 0.30.0, use BoolB::compare() instead
 */
final readonly class Compare
{
    private mixed $value;

    private function __construct() {}

    public static function of(mixed $value): self
    {
        $result = new self;
        $result->value = $value;
        return $result;
    }

    public function sameWith(mixed $otherValue): bool
    {
        return $this->value === $otherValue;
    }

    public function notSameWith(mixed $otherValue): bool
    {
        return $this->value !== $otherValue;
    }
}