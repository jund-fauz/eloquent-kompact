<?php

namespace JundFauz\EloquentKompact\Builder;

use Illuminate\Support\Stringable;
use JundFauz\EloquentKompact\Column\Creator;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\str_concat;
use const JundFauz\EloquentKompact\NAMESPACE_DELIMITER;

class Str extends Stringable
{
    public static function of(string $initialValue = ''): static
    {
        return new static($initialValue);
    }

    /**
     * @param array<string>|string $search
     * @param array<string>|string $replace
     * @return static
     */
    public function replaceByRegex(array|string $search, array|string $replace): static
    {
        return new static(preg_replace($search, $replace, $this->value));
    }

    /**
     * @param array<string>|string $search
     * @return static
     */
    public function removeByRegex(array|string $search): static
    {
        return $this->replaceByRegex($search, '');
    }

    public function join(string $delimiter, string ...$otherStrings): static
    {
        return new static(join($delimiter, [$this->value, ...$otherStrings]));
    }

    public function concat(string ...$otherStrings): static
    {
        return $this->add(...$otherStrings);
    }

    public function add(string ...$otherStrings): static
    {
        return $this->join('', ...$otherStrings);
    }

    public function addNamespace(string ...$otherNamespace): static
    {
        return new static(namespace_concat($this->value, $otherNamespace));
    }

    public function addPath(string ...$otherPath): static
    {
        return new static(path_concat($this->value, $otherPath));
    }

    public function addAtStart(string ...$otherStrings): static
    {
        return new static(str_concat($otherStrings, $this->value));
    }

    public function addNamespaceAtStart(string ...$otherNamespace): static
    {
        return new static(namespace_concat($otherNamespace, $this->value));
    }

    public function emptyIfFalse(mixed $condition): static
    {
        return new static($condition ? $this->value : '');
    }

    public function relation(): static
    {
        return new static(Creator::relation($this->value));
    }

    public function removeStart(string $toRemoved): static
    {
        return $this->replaceStart($toRemoved, '');
    }

    public function removeEnd(string $toRemoved): static
    {
        return $this->replaceEnd($toRemoved, '');
    }

    public function appPath(): static
    {
        return new static(app_path($this->value));
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