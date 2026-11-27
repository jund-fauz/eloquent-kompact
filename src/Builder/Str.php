<?php

namespace JundFauz\EloquentKompact\Builder;

use Illuminate\Support\Stringable;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Query\RawQuery;
use JundFauz\EloquentKompact\Regex\Regex;
use function JundFauz\EloquentKompact\Array\flat;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\same_with_one_of;
use function JundFauz\EloquentKompact\String\concat;
use function JundFauz\EloquentKompact\String\constant;
use function JundFauz\EloquentKompact\word_concat;
use const JundFauz\EloquentKompact\NEW_LINE;
use const JundFauz\EloquentKompact\path;

/**
 * Fluent string builder extending Laravel's `Stringable` with optional mutability and Kompact helpers.
 */
class Str extends Stringable
{
    private bool $isMutable;

    /**
     * Create a new `Str` instance.
     *
     * @param string $value
     * @param bool $isMutable
     */
    public function __construct($value = '', bool $isMutable = false)
    {
        parent::__construct($value);
        $this->isMutable = $isMutable;
    }

    /**
     * Create a new `Str` instance from the given initial value.
     *
     * @param string $initialValue
     * @param bool $isMutable
     * @return static
     * @final
     */
    public static function of(string $initialValue = '', bool $isMutable = false): static
    {
        return new static($initialValue, $isMutable);
    }

    /**
     * Replace matches of the given regular expression pattern(s) with the replacement(s).
     *
     * @param array<string>|string $search
     * @param array<string>|string $replace
     * @return static
     * @final
     */
    public function replaceByRegex(array|string $search, array|string $replace): static
    {
        return $this->set(preg_replace($search, $replace, $this->value));
    }

    /**
     * Remove all matches of the given regular expression pattern(s).
     *
     * @param array<string>|string $search
     * @return static
     * @final
     */
    public function removeByRegex(array|string $search): static
    {
        return $this->replaceByRegex($search, '');
    }

    /**
     * Join the current value with the given strings using `$delimiter`.
     *
     * @param string $delimiter
     * @param string|array<int, mixed> ...$otherStrings
     * @return static
     * @final
     */
    public function join(string $delimiter, string|array ...$otherStrings): static
    {
        flat($otherStrings);
        return $this->set(join($delimiter, [$this->value, ...$otherStrings]));
    }

    /**
     * Concatenate the given strings onto the end of the current value.
     *
     * @param string|array<int, mixed> ...$otherStrings
     * @return static
     * @final
     */
    public function concat(string|array ...$otherStrings): static
    {
        flat($otherStrings);
        return $this->add(...$otherStrings);
    }

    /**
     * Append the given strings onto the end of the current value without a delimiter.
     *
     * @param string|array<int, mixed> ...$otherStrings
     * @return static
     * @final
     */
    public function add(string|array ...$otherStrings): static
    {
        flat($otherStrings);
        return $this->join('', ...$otherStrings);
    }

    /**
     * Append non-empty words separated by a single space.
     *
     * @param string|array<int, mixed> ...$otherWords
     * @return static
     * @final
     */
    public function addWord(string|array ...$otherWords): static
    {
        flat($otherWords);
        return $this->set(self::joinWords([$this->value, ...$otherWords]));
    }

    /**
     * Append `::$constantName` to the current class name value.
     *
     * @param string $constantName
     * @return static
     * @final
     */
    public function addConstant(string $constantName): static
    {
        return $this->set(constant($this->value, $constantName));
    }

    /**
     * Append namespace segment(s) separated by `\`.
     *
     * @param string|array<int, mixed> ...$otherNamespace
     * @return static
     * @final
     */
    public function addNamespace(string|array ...$otherNamespace): static
    {
        flat($otherNamespace);
        return $this->set(namespace_concat($this->value, $otherNamespace));
    }

    /**
     * Append path segment(s) separated by `/`.
     *
     * @param string|array<int, mixed> ...$otherPath
     * @return static
     * @final
     */
    public function addPath(string|array ...$otherPath): static
    {
        flat($otherPath);
        return $this->set(self::joinPaths([$this->value, ...$otherPath]));
    }

    /**
     * Prepend the given strings to the beginning of the current value.
     *
     * @param string|array<int, mixed> ...$otherStrings
     * @return static
     * @final
     */
    public function addAtStart(string|array ...$otherStrings): static
    {
        flat($otherStrings);
        return $this->set(concat($otherStrings, $this->value));
    }

    /**
     * Prepend non-empty words separated by a single space before the current value.
     *
     * @param string|array<int, mixed> ...$otherWords
     * @return static
     * @final
     */
    public function addWordAtStart(string|array ...$otherWords): static
    {
        flat($otherWords);
        return $this->set(self::joinWords([...$otherWords, $this->value]));
    }

    /**
     * Prepend `$className::` before the current constant name value.
     *
     * @param string $className
     * @return static
     * @final
     */
    public function addConstantAtStart(string $className): static
    {
        return $this->set(constant($className, $this->value));
    }

    /**
     * Prepend namespace segment(s) separated by `\` before the current value.
     *
     * @param string|array<int, mixed> ...$otherNamespace
     * @return static
     * @final
     */
    public function addNamespaceAtStart(string|array ...$otherNamespace): static
    {
        flat($otherNamespace);
        return $this->set(namespace_concat($otherNamespace, $this->value));
    }

    /**
     * Prepend path segment(s) separated by `/` before the current value.
     *
     * @param string|array<int, mixed> ...$otherPath
     * @return static
     * @final
     */
    public function addPathAtStart(string|array ...$otherPath): static
    {
        flat($otherPath);
        return $this->set(self::joinPaths([...$otherPath, $this->value]));
    }

    /**
     * Append `$lineCount` newline(s) followed by `$sentence`.
     *
     * @param string $sentence
     * @param int $lineCount
     * @return static
     * @final
     */
    public function addAtNewLine(string $sentence, int $lineCount = 1): static
    {
        return ($this->set($this->value))->newLine($lineCount)->add($sentence);
    }

    /**
     * Append `$addition` to the current value when `$condition` is `true`.
     *
     * @param bool $condition
     * @param string $addition
     * @return static
     * @final
     */
    public function addIf(bool $condition, string $addition): static
    {
        $result = $this->set($this->value);
        if ($condition) {
            $result = $result->add($addition);
        }
        return $result;
    }

    /**
     * Append `$addition` as a space-separated word when `$condition` is `true`.
     *
     * @param bool $condition
     * @param string $addition
     * @return static
     * @final
     */
    public function addWordIf(bool $condition, string $addition): static
    {
        $result = $this->set($this->value);
        if ($condition) {
            $result = $result->addWord($addition);
        }
        return $result;
    }

    /**
     * Replace the value with an empty string when `$condition` is falsy.
     *
     * @param mixed $condition
     * @return static
     * @final
     */
    public function emptyIfFalse(mixed $condition): static
    {
        return $this->set($condition ? $this->value : '');
    }

    /**
     * Append the relation foreign-key suffix token to the current column name.
     *
     * @return static
     * @final
     */
    public function relation(): static
    {
        return $this->set(ColumnCreator::relation($this->value));
    }

    /**
     * Replace all occurrences of the given value in the string.
     *
     * @param iterable<string>|string $search
     * @param iterable<string>|string $replace
     * @param bool $caseSensitive
     * @return static
     */
    public function replace($search, $replace, $caseSensitive = true): static
    {
        return $this->set(parent::replace($search, $replace, $caseSensitive));
    }

    /**
     * Replace the first occurrence of `$search` if it appears at the start of the string.
     *
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replaceStart($search, $replace): static
    {
        return $this->set(parent::replaceStart($search, $replace));
    }

    /**
     * Replace the last occurrence of `$search` if it appears at the end of the string.
     *
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replaceEnd($search, $replace): static
    {
        return $this->set(parent::replaceEnd($search, $replace));
    }

    /**
     * Remove any occurrence of the given string(s) in the subject.
     *
     * @param iterable<string>|string $search
     * @param bool $caseSensitive
     * @return static
     */
    public function remove($search, $caseSensitive = true): static
    {
        return $this->set(parent::remove($search, $caseSensitive));
    }

    /**
     * Return the remainder of the string after the first occurrence of `$search`.
     *
     * @param string $search
     * @return static
     */
    public function after($search): static
    {
        return $this->set(parent::after($search));
    }

    /**
     * Return the remainder of the string after the last occurrence of `$search`.
     *
     * @param string $search
     * @return static
     */
    public function afterLast($search): static
    {
        return $this->set(parent::afterLast($search));
    }

    /**
     * Append the given values to the string.
     *
     * @param array<int, mixed>|string ...$values
     * @return static
     */
    public function append(...$values): static
    {
        return $this->set(parent::append(...$values));
    }

    /**
     * Transliterate a UTF-8 value to its closest 7-bit ASCII representation.
     *
     * @param string $language
     * @return static
     */
    public function ascii($language = 'en'): static
    {
        return $this->set(parent::ascii($language));
    }

    /**
     * Get the trailing name component of the path.
     *
     * @param string $suffix
     * @return static
     */
    public function basename($suffix = ''): static
    {
        return $this->set(parent::basename($suffix));
    }

    /**
     * Remove the given string(s) if present at the start of the string.
     *
     * @param array<int, string>|string $needle
     * @return static
     */
    public function chopStart($needle): static
    {
        return $this->set(parent::chopStart($needle));
    }

    /**
     * Remove the given string(s) if present at the end of the string.
     *
     * @param array<int, string>|string $needle
     * @return static
     */
    public function chopEnd($needle): static
    {
        return $this->set(parent::chopEnd($needle));
    }

    /**
     * Get the basename of the class path.
     *
     * @return static
     */
    public function classBasename(): static
    {
        return $this->set(parent::classBasename());
    }

    /**
     * Get the portion of the string before the first occurrence of `$search`.
     *
     * @param string $search
     * @return static
     */
    public function before($search): static
    {
        return $this->set(parent::before($search));
    }

    /**
     * Get the portion of the string before the last occurrence of `$search`.
     *
     * @param string $search
     * @return static
     */
    public function beforeLast($search): static
    {
        return $this->set(parent::beforeLast($search));
    }

    /**
     * Get the portion of the string between `$from` and `$to`.
     *
     * @param string $from
     * @param string $to
     * @return static
     */
    public function between($from, $to): static
    {
        return $this->set(parent::between($from, $to));
    }

    /**
     * Get the smallest possible portion of the string between `$from` and `$to`.
     *
     * @param string $from
     * @param string $to
     * @return static
     */
    public function betweenFirst($from, $to): static
    {
        return $this->set(parent::betweenFirst($from, $to));
    }

    /**
     * Convert the value to camelCase.
     *
     * @return static
     */
    public function camel(): static
    {
        return $this->set(parent::camel());
    }

    /**
     * Perform case conversion on the string according to `$mode`.
     *
     * @param int $mode
     * @param string|null $encoding
     * @return static
     */
    public function convertCase(int $mode = MB_CASE_FOLD, ?string $encoding = 'UTF-8'): static
    {
        return $this->set(parent::convertCase($mode, $encoding));
    }

    /**
     * Pluralize the string and prepend the formatted count.
     *
     * @param array<array-key, mixed>|\Countable|int $count
     * @return static
     */
    public function counted($count): static
    {
        return $this->set(parent::counted($count));
    }

    /**
     * Replace consecutive instances of a given character with a single character.
     *
     * @param array<int, string>|string $characters
     * @return static
     */
    public function deduplicate(array|string $characters = ' '): static
    {
        return $this->set(parent::deduplicate($characters));
    }

    /**
     * Get the parent directory path up `$levels` levels.
     *
     * @param int $levels
     * @return static
     */
    public function dirname($levels = 1): static
    {
        return $this->set(parent::dirname($levels));
    }

    /**
     * Cap the string with a single instance of `$cap`.
     *
     * @param string $cap
     * @return static
     */
    public function finish($cap): static
    {
        return $this->set(parent::finish($cap));
    }

    /**
     * Convert the string to kebab-case.
     *
     * @return static
     */
    public function kebab(): static
    {
        return $this->set(parent::kebab());
    }

    /**
     * Limit the number of characters in the string.
     *
     * @param int $limit
     * @param string $end
     * @param bool $preserveWords
     * @return static
     */
    public function limit($limit = 100, $end = '...', $preserveWords = false): static
    {
        return $this->set(parent::limit($limit, $end, $preserveWords));
    }

    /**
     * Convert the given string to lower-case.
     *
     * @return static
     */
    public function lower(): static
    {
        return $this->set(parent::lower());
    }

    /**
     * Convert GitHub Flavored Markdown into HTML.
     *
     * @param array<string, mixed> $options
     * @param array<int, mixed> $extensions
     * @return static
     */
    public function markdown(array $options = [], array $extensions = []): static
    {
        return $this->set(parent::markdown($options, $extensions));
    }

    /**
     * Convert inline Markdown into HTML.
     *
     * @param array<string, mixed> $options
     * @param array<int, mixed> $extensions
     * @return static
     */
    public function inlineMarkdown(array $options = [], array $extensions = []): static
    {
        return $this->set(parent::inlineMarkdown($options, $extensions));
    }

    /**
     * Mask a portion of the string with a repeated character.
     *
     * @param string $character
     * @param int $index
     * @param int|null $length
     * @param string $encoding
     * @return static
     */
    public function mask($character, $index, $length = null, $encoding = 'UTF-8'): static
    {
        return $this->set(parent::mask($character, $index, $length, $encoding));
    }

    /**
     * Get the substring matching the given regular expression pattern.
     *
     * @param string $pattern
     * @return static
     */
    public function match($pattern): static
    {
        return $this->set(parent::match($pattern));
    }

    /**
     * Remove all non-numeric characters from the string.
     *
     * @return static
     */
    public function numbers(): static
    {
        return $this->set(parent::numbers());
    }

    /**
     * Pad both sides of the string with another string.
     *
     * @param int $length
     * @param string $pad
     * @return static
     */
    public function padBoth($length, $pad = ' '): static
    {
        return $this->set(parent::padBoth($length, $pad));
    }

    /**
     * Pad the left side of the string with another string.
     *
     * @param int $length
     * @param string $pad
     * @return static
     */
    public function padLeft($length, $pad = ' '): static
    {
        return $this->set(parent::padLeft($length, $pad));
    }

    /**
     * Pad the right side of the string with another string.
     *
     * @param int $length
     * @param string $pad
     * @return static
     */
    public function padRight($length, $pad = ' '): static
    {
        return $this->set(parent::padRight($length, $pad));
    }

    /**
     * Pass the string to the given callback and return the resulting `Str` instance.
     *
     * @param callable $callback
     * @return static
     */
    public function pipe(callable $callback): static
    {
        return $this->set(parent::pipe($callback));
    }

    /**
     * Get the plural form of an English word.
     *
     * @param array<array-key, mixed>|\Countable|int $count
     * @param bool $prependCount
     * @return static
     */
    public function plural($count = 2, $prependCount = false): static
    {
        return $this->set(parent::plural($count, $prependCount));
    }

    /**
     * Pluralize the last word of an English StudlyCaps string.
     *
     * @param array<array-key, mixed>|\Countable|int $count
     * @return static
     */
    public function pluralStudly($count = 2): static
    {
        return $this->set(parent::pluralStudly($count));
    }

    /**
     * Pluralize the last word of an English PascalCase string.
     *
     * @param array<array-key, mixed>|\Countable|int $count
     * @return static
     */
    public function pluralPascal($count = 2): static
    {
        return $this->set(parent::pluralPascal($count));
    }

    /**
     * Prepend the given values to the string.
     *
     * @param array<int, mixed>|string ...$values
     * @return static
     */
    public function prepend(...$values): static
    {
        return $this->set(parent::prepend(...$values));
    }

    /**
     * Reverse the string.
     *
     * @return static
     */
    public function reverse(): static
    {
        return $this->set(parent::reverse());
    }

    /**
     * Repeat the string `$times` times.
     *
     * @param int $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->set(parent::repeat($times));
    }

    /**
     * Replace a given value in the string sequentially with an array of replacements.
     *
     * @param string $search
     * @param iterable<string> $replace
     * @return static
     */
    public function replaceArray($search, $replace): static
    {
        return $this->set(parent::replaceArray($search, $replace));
    }

    /**
     * Replace the first occurrence of `$search` in the string.
     *
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replaceFirst($search, $replace): static
    {
        return $this->set(parent::replaceFirst($search, $replace));
    }

    /**
     * Replace the last occurrence of `$search` in the string.
     *
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replaceLast($search, $replace): static
    {
        return $this->set(parent::replaceLast($search, $replace));
    }

    /**
     * Replace the patterns matching the given regular expression.
     *
     * @param array<int, string>|string $pattern
     * @param \Closure|array<int, string>|string $replace
     * @param int $limit
     * @return static
     */
    public function replaceMatches($pattern, $replace, $limit = -1): static
    {
        return $this->set(parent::replaceMatches($pattern, $replace, $limit));
    }

    /**
     * Remove all extra whitespace from both ends and between words in the string.
     *
     * @return static
     */
    public function squish(): static
    {
        return $this->set(parent::squish());
    }

    /**
     * Begin the string with a single instance of `$prefix`.
     *
     * @param string $prefix
     * @return static
     */
    public function start($prefix): static
    {
        return $this->set(parent::start($prefix));
    }

    /**
     * Strip HTML and PHP tags from the string.
     *
     * @param array<int, string>|string|null $allowedTags
     * @return static
     */
    public function stripTags($allowedTags = null): static
    {
        return $this->set(parent::stripTags($allowedTags));
    }

    /**
     * Convert the given string to upper-case.
     *
     * @return static
     */
    public function upper(): static
    {
        return $this->set(parent::upper());
    }

    /**
     * Convert the given string to Title Case.
     *
     * @return static
     */
    public function title(): static
    {
        return $this->set(parent::title());
    }

    /**
     * Convert the string into Title Case for each word separated by case changes, hyphens, or underscores.
     *
     * @return static
     */
    public function headline(): static
    {
        return $this->set(parent::headline());
    }

    /**
     * Extract the initials from each word in the string.
     *
     * @param bool $capitalize
     * @return static
     */
    public function initials($capitalize = false): static
    {
        return $this->set(parent::initials($capitalize));
    }

    /**
     * Convert the given string to APA-style title case.
     *
     * @return static
     */
    public function apa(): static
    {
        return $this->set(parent::apa());
    }

    /**
     * Transliterate a string to its closest ASCII representation.
     *
     * @param string|null $unknown
     * @param bool|null $strict
     * @return static
     */
    public function transliterate($unknown = '?', $strict = false): static
    {
        return $this->set(parent::transliterate($unknown, $strict));
    }

    /**
     * Get the singular form of an English word.
     *
     * @return static
     */
    public function singular(): static
    {
        return $this->set(parent::singular());
    }

    /**
     * Generate a URL-friendly slug from the string.
     *
     * @param string $separator
     * @param string|null $language
     * @param array<string, string> $dictionary
     * @return static
     */
    public function slug($separator = '-', $language = 'en', $dictionary = ['@' => 'at']): static
    {
        return $this->set(parent::slug($separator, $language, $dictionary));
    }

    /**
     * Convert the string to snake_case.
     *
     * @param string $delimiter
     * @return static
     */
    public function snake($delimiter = '_'): static
    {
        return $this->set(parent::snake($delimiter));
    }

    /**
     * Convert the value to StudlyCaps.
     *
     * @param bool $normalize
     * @return static
     */
    public function studly(bool $normalize = false): static
    {
        return $this->set(parent::studly($normalize));
    }

    /**
     * Convert the value to PascalCase.
     *
     * @param bool $normalize
     * @return static
     */
    public function pascal(bool $normalize = false): static
    {
        return $this->set(parent::pascal($normalize));
    }

    /**
     * Return the portion of the string specified by `$start` and `$length`.
     *
     * @param int $start
     * @param int|null $length
     * @param string $encoding
     * @return static
     */
    public function substr($start, $length = null, $encoding = 'UTF-8'): static
    {
        return $this->set(parent::substr($start, $length, $encoding));
    }

    /**
     * Replace text within a portion of the string.
     *
     * @param array<int, string>|string $replace
     * @param array<int, int>|int $offset
     * @param array<int, int>|int|null $length
     * @return static
     */
    public function substrReplace($replace, $offset = 0, $length = null): static
    {
        return $this->set(parent::substrReplace($replace, $offset, $length));
    }

    /**
     * Swap multiple keywords in the string with other keywords.
     *
     * @param array<string, string> $map
     * @return static
     */
    public function swap(array $map): static
    {
        return $this->set(parent::swap($map));
    }

    /**
     * Trim the string of the given characters.
     *
     * @param string|null $characters
     * @return static
     */
    public function trim($characters = null): static
    {
        return $this->set(parent::trim($characters));
    }

    /**
     * Left-trim the string of the given characters.
     *
     * @param string|null $characters
     * @return static
     */
    public function ltrim($characters = null): static
    {
        return $this->set(parent::ltrim($characters));
    }

    /**
     * Right-trim the string of the given characters.
     *
     * @param string|null $characters
     * @return static
     */
    public function rtrim($characters = null): static
    {
        return $this->set(parent::rtrim($characters));
    }

    /**
     * Make the string's first character lowercase.
     *
     * @return static
     */
    public function lcfirst(): static
    {
        return $this->set(parent::lcfirst());
    }

    /**
     * Make the string's first character uppercase.
     *
     * @return static
     */
    public function ucfirst(): static
    {
        return $this->set(parent::ucfirst());
    }

    /**
     * Capitalize the first character of each word in the string.
     *
     * @param string $separators
     * @return static
     */
    public function ucwords($separators = " \t\r\n\f\v"): static
    {
        return $this->set(parent::ucwords($separators));
    }

    /**
     * Limit the number of words in the string.
     *
     * @param int $words
     * @param string $end
     * @return static
     */
    public function words($words = 100, $end = '...'): static
    {
        return $this->set(parent::words($words, $end));
    }

    /**
     * Wrap the string to a given number of characters.
     *
     * @param int $characters
     * @param string $break
     * @param bool $cutLongWords
     * @return static
     */
    public function wordWrap($characters = 75, $break = "\n", $cutLongWords = false): static
    {
        return $this->set(parent::wordWrap($characters, $break, $cutLongWords));
    }

    /**
     * Wrap the string with the given `$before` and `$after` strings.
     *
     * @param string $before
     * @param string|null $after
     * @return static
     */
    public function wrap($before, $after = null): static
    {
        return $this->set(parent::wrap($before, $after));
    }

    /**
     * Unwrap the string with the given `$before` and `$after` strings.
     *
     * @param string $before
     * @param string|null $after
     * @return static
     */
    public function unwrap($before, $after = null): static
    {
        return $this->set(parent::unwrap($before, $after));
    }

    /**
     * Convert the string to Base64 encoding.
     *
     * @return static
     */
    public function toBase64(): static
    {
        return $this->set(parent::toBase64());
    }

    /**
     * Decode the Base64-encoded string.
     *
     * @param bool $strict
     * @return static
     */
    public function fromBase64($strict = false): static
    {
        return $this->set(parent::fromBase64($strict));
    }

    /**
     * Hash the string using the given algorithm.
     *
     * @param string $algorithm
     * @return static
     */
    public function hash(string $algorithm): static
    {
        return $this->set(parent::hash($algorithm));
    }

    /**
     * Encrypt the string using Laravel's encrypter.
     *
     * @param bool $serialize
     * @return static
     */
    public function encrypt(bool $serialize = false): static
    {
        return $this->set(parent::encrypt($serialize));
    }

    /**
     * Decrypt the encrypted string using Laravel's encrypter.
     *
     * @param bool $serialize
     * @return static
     */
    public function decrypt(bool $serialize = false): static
    {
        return $this->set(parent::decrypt($serialize));
    }

    /**
     * Remove `$toRemoved` if it appears at the start of the string.
     *
     * @param string $toRemoved
     * @return static
     * @final
     */
    public function removeStart(string $toRemoved): static
    {
        return $this->replaceStart($toRemoved, '');
    }

    /**
     * Remove `$toRemoved` if it appears at the end of the string.
     *
     * @param string $toRemoved
     * @return static
     * @final
     */
    public function removeEnd(string $toRemoved): static
    {
        return $this->replaceEnd($toRemoved, '');
    }

    /**
     * Resolve the current string as a path inside Laravel's `app` directory.
     *
     * @return static
     * @final
     */
    public function appPath(): static
    {
        return $this->set(app_path($this->value));
    }

    /**
     * Convert the current string into a `RawQuery` SQL expression builder.
     *
     * @return RawQuery
     * @final
     */
    public function asRawQuery(): RawQuery
    {
        return RawQuery::create($this->value);
    }

    /**
     * Convert the current string into a `Regex` pattern builder.
     *
     * @return Regex
     * @final
     */
    public function asRegex(): Regex
    {
        return Regex::create($this->value);
    }

    /**
     * Determine whether the current string is strictly identical (`===`) to `$otherValue`.
     *
     * @param mixed $otherValue
     * @return bool
     * @final
     */
    public function sameWith(mixed $otherValue): bool
    {
        return same_with($this->value, $otherValue instanceof Stringable ? $otherValue->value : $otherValue);
    }

    /**
     * Determine whether the current string is strictly not identical (`!==`) to `$otherValue`.
     *
     * @param mixed $otherValue
     * @return bool
     * @final
     */
    public function notSameWith(mixed $otherValue): bool
    {
        return not_same_with($this->value, $otherValue instanceof Stringable ? $otherValue->value : $otherValue);
    }

    /**
     * Whether the value is identical (===) to one of the values, nested arrays flattened;
     * a Laravel Stringable is compared by its value, like sameWith()
     *
     * @param mixed ...$values
     * @return bool False without a value
     * @final
     */
    public function sameWithOneOf(mixed ...$values): bool
    {
        flat($values);
        return same_with_one_of($this->value, ...array_map(fn ($value) => $value instanceof Stringable ? $value->value : $value, $values));
    }

    /**
     * Append `$count` newline character(s) only when the current string is not empty.
     *
     * @param int $count
     * @return static
     * @final
     */
    public function newLineIfNotEmpty(int $count = 1): static
    {
        return $this->isNotEmpty() ? $this->newLine($count) : $this;
    }

    /**
     * Append `$count` newline character(s) to the current string.
     *
     * @param int $count
     * @return static
     * @final
     */
    public function newLine($count = 1): static
    {
        return $this->add(str_repeat(NEW_LINE, max($count, 0)));
    }

    /**
     * Checks whether a given named constant exists using `defined()` function from php
     *
     * @return bool
     * @final
     */
    public function isDefined(): bool
    {
        return defined($this->value);
    }

    /**
     * Joins the non-empty words with a space
     *
     * @param array<int, string> $words
     * @return string
     */
    private static function joinWords(array $words): string
    {
        return word_concat(array_filter($words, fn (string $word) => $word !== ''));
    }

    /**
     * Joins the non-empty paths with a single `/`, keeping the slashes at the start and the end of the whole path
     *
     * @param array<int, string> $paths
     * @return string
     */
    private static function joinPaths(array $paths): string
    {
        $paths = array_values(array_filter($paths, fn (string $path) => $path !== ''));
        $lastIndex = count($paths) - 1;

        return path_concat(array_map(
            fn (string $path, int $index) => match (true) {
                $index === 0 && $index === $lastIndex => $path,
                $index === 0 => rtrim($path, path),
                $index === $lastIndex => ltrim($path, path),
                default => trim($path, path),
            },
            $paths,
            array_keys($paths)
        ));
    }

    /**
     * Update the current value in place when mutable, or return a new `Str` instance otherwise.
     *
     * @param string|Stringable $newValue
     * @return static
     */
    private function set(string|Stringable $newValue): static
    {
        if ($newValue instanceof Stringable) {
            $newValue = $newValue->value();
        }
        if ($this->isMutable) {
            $this->value = $newValue;
            return $this;
        }
        return new static($newValue);
    }
}
