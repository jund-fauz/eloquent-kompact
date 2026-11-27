<?php

namespace JundFauz\EloquentKompact\Regex;

use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Internal\Concerns\Builder;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\String\concat;

/**
 * Fluent builder for PCRE regular expression patterns.
 *
 * @final
 */
class Regex extends Builder
{
    public const string DELIMITER = '/';

    protected string $result = '';

    /**
     * Create a new `Regex` builder initialized with an optional pattern.
     *
     * @param string|self $pattern
     * @return static
     */
    public static function create(string|self $pattern = ''): static
    {
        return (new static)->set(static::raw($pattern));
    }

    /**
     * Anchor the current and given pattern fragments between `^` and `$`.
     *
     * @param string|self ...$sentenceOrWord
     * @return static
     */
    public function exact(string|self ...$sentenceOrWord): static
    {
        return $this->set(concat(
            '^',
            $this->withResult(...$sentenceOrWord),
            '$'
        ));
    }

    /**
     * Anchor the current and given pattern fragments at the start with `^`.
     *
     * @param string|self ...$sentenceOrWord
     * @return static
     */
    public function startsWith(string|self ...$sentenceOrWord): static
    {
        return $this->set(concat('^', $this->withResult(...$sentenceOrWord)));
    }

    /**
     * Anchor the current and given pattern fragments at the end with `$`.
     *
     * @param string|self ...$sentenceOrWord
     * @return static
     */
    public function endsWith(string|self ...$sentenceOrWord): static
    {
        return $this->set(concat($this->withResult(...$sentenceOrWord), '$'));
    }

    /**
     * Wrap the current and given pattern fragments in a capturing group `(...)`.
     *
     * @param string|self ...$regex
     * @return static
     */
    public function group(string|self ...$regex): static
    {
        return $this->set(concat('(', $this->withResult(...$regex), ')'));
    }

    /**
     * Prepend the given pattern fragments before the current pattern.
     *
     * @param string|self ...$regex
     * @return static
     */
    public function before(string|self ...$regex): static
    {
        return collect($regex)
            ->flatten()
            ->map(static::raw(...))
            ->implodeAsStr()
            ->add($this->result)
            ->asRegex();
    }

    /**
     * Append a negative lookahead `(?!$word)` to exclude the given word or pattern.
     *
     * @param string|self $word
     * @return static
     */
    public function exceptWord(string|self $word): static
    {
        return $this->set(concat($this->withResult(concat('(?!', static::raw($word), ')'))));
    }

    /**
     * Wrap the current and given character requirements in a character class `[...]` with an optional quantifier.
     *
     * @param Quantifier|string $conditionCount
     * @param string|self ...$requirements
     * @return static
     */
    public function contain(Quantifier|string $conditionCount = '', string|self ...$requirements): static
    {
        return $this->set(concat(
            '[',
            $this->withResult(...$requirements),
            ']',
            is_string($conditionCount) ? $conditionCount : $conditionCount->value
        ));
    }

    /**
     * Append a character range `$from-$until` to the current pattern.
     *
     * @param string $from
     * @param string $until
     * @return static
     */
    public function range(string $from, string $until): static
    {
        return $this->set(concat($this->withResult("$from-$until")));
    }

    /**
     * Convert the delimited regular expression into a Kompact `Str` instance.
     *
     * @return Str
     */
    public function asStr(): Str
    {
        return Str::of($this->build());
    }

    /**
     * Returns the pattern wrapped in delimiters, ready to be used by preg_* functions
     *
     * @return string
     */
    public function build(): string
    {
        return concat(static::DELIMITER, $this->result, static::DELIMITER);
    }

    /**
     * Restore a `Regex` instance exported by `var_export()`.
     *
     * @param array{result?: string} $properties
     * @return static
     */
    public static function __set_state(array $properties): static
    {
        return (new static)->set($properties['result'] ?? '');
    }

    /**
     * Puts the current pattern in front of the given operands, unless it is still empty
     *
     * @param string|self ...$patterns
     * @return array<int, string>
     */
    protected function withResult(string|self ...$patterns): array
    {
        $patterns = array_map(fn (string|self $pattern) => static::raw($pattern), $patterns);
        return $this->result === '' ? $patterns : [$this->result, ...$patterns];
    }

    /**
     * Takes the undelimited pattern, so a nested Regex does not carry its delimiters along
     *
     * @param string|self $pattern
     * @return string
     */
    protected static function raw(string|self $pattern): string
    {
        return $pattern instanceof self ? $pattern->result : $pattern;
    }
}
