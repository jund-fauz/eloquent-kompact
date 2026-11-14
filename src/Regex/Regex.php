<?php

namespace JundFauz\EloquentKompact\Regex;

use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Internal\Concerns\Builder;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\String\concat;

class Regex extends Builder
{
    public const string DELIMITER = '/';

    protected string $result = '';

    public static function create(string|self $pattern = ''): static
    {
        return (new static)->set(static::raw($pattern));
    }

    public function exact(string|self ...$sentenceOrWord): static
    {
        return $this->set(concat(
            '^',
            $this->withResult(...$sentenceOrWord),
            '$'
        ));
    }

    public function startsWith(string|self ...$sentenceOrWord): static
    {
        return $this->set(concat('^', $this->withResult(...$sentenceOrWord)));
    }

    public function endsWith(string|self ...$sentenceOrWord): static
    {
        return $this->set(concat($this->withResult(...$sentenceOrWord), '$'));
    }

    public function group(string|self ...$regex): static
    {
        return $this->set(concat('(', $this->withResult(...$regex), ')'));
    }

    public function before(string|self ...$regex): static
    {
        return collect($regex)
            ->flatten()
            ->map(static::raw(...))
            ->implodeAsStr()
            ->add($this->result)
            ->asRegex();
    }

    public function exceptWord(string|self $word): static
    {
        return $this->set(concat($this->withResult(concat('(?!', static::raw($word), ')'))));
    }

    public function contain(Quantifier|string $conditionCount = '', string|self ...$requirements): static
    {
        return $this->set(concat(
            '[',
            $this->withResult(...$requirements),
            ']',
            is_string($conditionCount) ? $conditionCount : $conditionCount->value
        ));
    }

    public function range(string $from, string $until): static
    {
        return $this->set(concat($this->withResult("$from-$until")));
    }

    public function asStr(): Str
    {
        return Str::of($this->build());
    }

    /**
     * Returns the pattern wrapped in delimiters, ready to be used by preg_* functions
     */
    public function build(): string
    {
        return concat(static::DELIMITER, $this->result, static::DELIMITER);
    }

    /**
     * @param array{result?: string} $properties
     * @return static
     */
    public static function __set_state(array $properties): static
    {
        return (new static)->set($properties['result'] ?? '');
    }

    /**
     * Puts the current pattern in front of the given operands, unless it is still empty
     */
    protected function withResult(string|self ...$patterns): array
    {
        $patterns = array_map(fn (string|self $pattern) => static::raw($pattern), $patterns);
        return $this->result === '' ? $patterns : [$this->result, ...$patterns];
    }

    /**
     * Takes the undelimited pattern, so a nested Regex does not carry its delimiters along
     */
    protected static function raw(string|self $pattern): string
    {
        return $pattern instanceof self ? $pattern->result : $pattern;
    }
}
