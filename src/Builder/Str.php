<?php

namespace JundFauz\EloquentKompact\Builder;

use Illuminate\Support\Stringable;
use JundFauz\EloquentKompact\Column\Creator;
use JundFauz\EloquentKompact\Query\RawQuery;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\str_concat;
use function JundFauz\EloquentKompact\word_concat;
use const JundFauz\EloquentKompact\NEW_LINE;

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

    public function addWord(string ...$otherWords): static
    {
        return new static(word_concat($this->value, $otherWords));
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

    public function addWordAtStart(string ...$otherWords): static
    {
        return new static(word_concat($otherWords, $this->value));
    }

    public function addNamespaceAtStart(string ...$otherNamespace): static
    {
        return new static(namespace_concat($otherNamespace, $this->value));
    }

    public function addPathAtStart(string ...$otherPath): static
    {
        return new static(path_concat($otherPath, $this->value));
    }

    public function addAtNewLine(string $sentence, int $lineCount = 1): static
    {
        return (new static($this->value))->newLine($lineCount)->add($sentence);
    }

    public function addIf(bool $condition, string $addition): static
    {
        $result = new static($this->value);
        if ($condition) {
            $result = $result->add($addition);
        }
        return $result;
    }

    public function addWordIf(bool $condition, string $addition): static
    {
        $result = new static($this->value);
        if ($condition) {
            $result = $result->addWord($addition);
        }
        return $result;
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

    public function asRawQuery(): RawQuery
    {
        return RawQuery::create($this->value);
    }

    public function sameWith(mixed $otherValue): bool
    {
        return same_with($this->value, $otherValue instanceof Stringable ? $otherValue->value : $otherValue);
    }

    public function notSameWith(mixed $otherValue): bool
    {
        return not_same_with($this->value, $otherValue instanceof Stringable ? $otherValue->value : $otherValue);
    }

    public function newLineIfNotEmpty(int $count = 1): static
    {
        return $this->isNotEmpty() ? $this->newLine($count) : $this;
    }

    public function newLine($count = 1): static
    {
        return $this->add(str_repeat(NEW_LINE, max($count, 0)));
    }
}