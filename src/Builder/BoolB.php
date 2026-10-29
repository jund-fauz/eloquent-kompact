<?php

namespace JundFauz\EloquentKompact\Builder;

use JundFauz\EloquentKompact\Internal\Logic\Compare;

final readonly class BoolB
{
    public bool $result;

    private function __construct() { }

    public static function compare(mixed $firstValue): Compare
    {
        return Compare::of($firstValue, fn (bool $condition) => (new self)->set($condition));
    }

    public static function initiate(bool $condition): self
    {
        return (new self)->set($condition);
    }

    public function and(mixed $valueToCompare): Compare
    {
        return Compare::of($valueToCompare, fn (bool $secondCondition) => $this->set($this->result && $secondCondition));
    }

    public function or(mixed $valueToCompare): Compare
    {
        return Compare::of($valueToCompare, fn (bool $secondCondition) => $this->set($this->result || $secondCondition));
    }

    private function set(bool $newCondition): self
    {
        $newClass = new self;
        $newClass->result = $newCondition;
        return $newClass;
    }
}