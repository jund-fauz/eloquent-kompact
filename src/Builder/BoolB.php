<?php

namespace JundFauz\EloquentKompact\Builder;

final readonly class BoolB
{
    public bool $result;

    private function __construct() {}

    /**
     * @deprecated since 0.26.0, use BoolB::initiate() instead
     * @param bool $condition
     * @return self
     */
    public static function create(bool $condition): self
    {
        return self::initiate($condition);
    }

    public static function initiate(bool $condition): self
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