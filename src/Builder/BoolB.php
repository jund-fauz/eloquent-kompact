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

    public static function initiate(self|bool $condition): self
    {
        return ! $condition instanceof self ? (new self)->set($condition) : $condition;
    }

    public function and(mixed $valueToCompare): Compare
    {
        return Compare::of($valueToCompare, fn (bool $secondCondition) => $this->set($this->result && $secondCondition));
    }

    public function or(mixed $valueToCompare): Compare
    {
        return Compare::of($valueToCompare, fn (bool $secondCondition) => $this->set($this->result || $secondCondition));
    }

    public function andGroup(self|bool $condition): self
    {
        return $this->set( $this->result && ($condition instanceof self
            ? $condition->result
            : $condition));
    }

    public function orGroup(self|bool $condition): self
    {
        return $this->set( $this->result || ($condition instanceof self
            ? $condition->result
            : $condition));
    }

    private function set(bool $newCondition): self
    {
        $newClass = new self;
        $newClass->result = $newCondition;
        return $newClass;
    }
}