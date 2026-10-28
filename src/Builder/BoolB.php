<?php

namespace JundFauz\EloquentKompact\Builder;

final readonly class BoolB
{
    public bool $result;

    private function __construct() {}

    public static function create(bool $condition): self
    {
        return (new self)->set($condition);
    }

    public function and(bool $condition): self
    {
        return $this->set($this->result && $condition);
    }

    public function or(bool $condition): self
    {
        return $this->set($this->result || $condition);
    }

    public function set(bool $newCondition): self
    {
        $newClass = new self;
        $newClass->result = $newCondition;
        return $newClass;
    }
}