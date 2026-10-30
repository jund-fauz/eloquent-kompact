<?php

namespace JundFauz\EloquentKompact\Builder;

use Illuminate\Support\Stringable;
use JundFauz\EloquentKompact\Column\Creator;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\str_concat;

class Str extends Stringable
{
    public static function of(string $initialValue = ''): self
    {
        return new self($initialValue);
    }

    /**
     * @param array<string>|string $search
     * @param array<string>|string $replace
     * @return self
     */
    public function replaceByRegex(array|string $search, array|string $replace): self
    {
        return new self(preg_replace($search, $replace, $this->value));
    }

    /**
     * @param array<string>|string $search
     * @return self
     */
    public function removeByRegex(array|string $search): self
    {
        return $this->replaceByRegex($search, '');
    }

    public function join(string $delimiter, string ...$otherStrings): self
    {
        return new self(join($delimiter, [$this->value, ...$otherStrings]));
    }

    public function concat(string ...$otherStrings): self
    {
        return $this->add(...$otherStrings);
    }

    public function add(string ...$otherStrings): self
    {
        return $this->join('', ...$otherStrings);
    }

    public function addAtStart(string ...$otherStrings): self
    {
        return new self(str_concat($otherStrings, $this->value));
    }

    public function addNamespaceAtStart(string ...$otherNamespace): self
    {
        return new self(namespace_concat($otherNamespace, $this->value));
    }

    public function emptyIfFalse(mixed $condition): self
    {
        return new self($condition ? $this->value : '');
    }

    public function relation(): self
    {
        return new self(Creator::relation($this->value));
    }

    public function sameWith(mixed $otherValue): bool
    {
        return same_with($this->value, $otherValue instanceof Stringable ? $otherValue->value : $otherValue);
    }

    public function notSameWith(mixed $otherValue): bool
    {
        return not_same_with($this->value, $otherValue instanceof Stringable ? $otherValue->value : $otherValue);
    }
}