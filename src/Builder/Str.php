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
use function JundFauz\EloquentKompact\String\concat;
use function JundFauz\EloquentKompact\String\constant;
use function JundFauz\EloquentKompact\word_concat;
use const JundFauz\EloquentKompact\NEW_LINE;
use const JundFauz\EloquentKompact\PATH_DELIMITER;

class Str extends Stringable
{
    private bool $isMutable;

    public function __construct($value = '', bool $isMutable = false) {
        parent::__construct($value);
        $this->isMutable = $isMutable;
    }

    public static function of(string $initialValue = '', bool $isMutable = false): static
    {
        return new static($initialValue, $isMutable);
    }

    /**
     * @param array<string>|string $search
     * @param array<string>|string $replace
     * @return static
     */
    public function replaceByRegex(array|string $search, array|string $replace): static
    {
        return $this->set(preg_replace($search, $replace, $this->value));
    }

    /**
     * @param array<string>|string $search
     * @return static
     */
    public function removeByRegex(array|string $search): static
    {
        return $this->replaceByRegex($search, '');
    }

    public function join(string $delimiter, string|array ...$otherStrings): static
    {
        flat($otherStrings);
        return $this->set(join($delimiter, [$this->value, ...$otherStrings]));
    }

    public function concat(string|array ...$otherStrings): static
    {
        flat($otherStrings);
        return $this->add(...$otherStrings);
    }

    public function add(string|array ...$otherStrings): static
    {
        flat($otherStrings);
        return $this->join('', ...$otherStrings);
    }

    public function addWord(string|array ...$otherWords): static
    {
        flat($otherWords);
        return $this->set(self::joinWords([$this->value, ...$otherWords]));
    }

    public function addConstant(string $constantName): static
    {
        return $this->set(constant($this->value, $constantName));
    }

    public function addNamespace(string|array ...$otherNamespace): static
    {
        flat($otherNamespace);
        return $this->set(namespace_concat($this->value, $otherNamespace));
    }

    public function addPath(string|array ...$otherPath): static
    {
        flat($otherPath);
        return $this->set(self::joinPaths([$this->value, ...$otherPath]));
    }

    public function addAtStart(string|array ...$otherStrings): static
    {
        flat($otherStrings);
        return $this->set(concat($otherStrings, $this->value));
    }

    public function addWordAtStart(string|array ...$otherWords): static
    {
        flat($otherWords);
        return $this->set(self::joinWords([...$otherWords, $this->value]));
    }

    public function addConstantAtStart(string $className): static
    {
        return $this->set(constant($className, $this->value));
    }

    public function addNamespaceAtStart(string|array ...$otherNamespace): static
    {
        flat($otherNamespace);
        return $this->set(namespace_concat($otherNamespace, $this->value));
    }

    public function addPathAtStart(string|array ...$otherPath): static
    {
        flat($otherPath);
        return $this->set(self::joinPaths([...$otherPath, $this->value]));
    }

    public function addAtNewLine(string $sentence, int $lineCount = 1): static
    {
        return ($this->set($this->value))->newLine($lineCount)->add($sentence);
    }

    public function addIf(bool $condition, string $addition): static
    {
        $result = $this->set($this->value);
        if ($condition) {
            $result = $result->add($addition);
        }
        return $result;
    }

    public function addWordIf(bool $condition, string $addition): static
    {
        $result = $this->set($this->value);
        if ($condition) {
            $result = $result->addWord($addition);
        }
        return $result;
    }

    public function emptyIfFalse(mixed $condition): static
    {
        return $this->set($condition ? $this->value : '');
    }

    public function relation(): static
    {
        return $this->set(ColumnCreator::relation($this->value));
    }

    public function replace($search, $replace, $caseSensitive = true): static
    {
        return $this->set(parent::replace($search, $replace, $caseSensitive));
    }

    public function replaceStart($search, $replace): static
    {
        return $this->set(parent::replaceStart($search, $replace));
    }

    public function replaceEnd($search, $replace): static
    {
        return $this->set(parent::replaceEnd($search, $replace));
    }

    public function remove($search, $caseSensitive = true): static
    {
        return $this->set(parent::remove($search, $caseSensitive));
    }

    public function after($search): static
    {
        return $this->set(parent::after($search));
    }

    public function afterLast($search): static
    {
        return $this->set(parent::afterLast($search));
    }

    public function append(...$values): static
    {
        return $this->set(parent::append(...$values));
    }

    public function ascii($language = 'en'): static
    {
        return $this->set(parent::ascii($language));
    }

    public function basename($suffix = ''): static
    {
        return $this->set(parent::basename($suffix));
    }

    public function chopStart($needle): static
    {
        return $this->set(parent::chopStart($needle));
    }

    public function chopEnd($needle): static
    {
        return $this->set(parent::chopEnd($needle));
    }

    public function classBasename(): static
    {
        return $this->set(parent::classBasename());
    }

    public function before($search): static
    {
        return $this->set(parent::before($search));
    }

    public function beforeLast($search): static
    {
        return $this->set(parent::beforeLast($search));
    }

    public function between($from, $to): static
    {
        return $this->set(parent::between($from, $to));
    }

    public function betweenFirst($from, $to): static
    {
        return $this->set(parent::betweenFirst($from, $to));
    }

    public function camel(): static
    {
        return $this->set(parent::camel());
    }

    public function convertCase(int $mode = MB_CASE_FOLD, ?string $encoding = 'UTF-8'): static
    {
        return $this->set(parent::convertCase($mode, $encoding));
    }

    public function counted($count): static
    {
        return $this->set(parent::counted($count));
    }

    public function deduplicate(array|string $characters = ' '): static
    {
        return $this->set(parent::deduplicate($characters));
    }

    public function dirname($levels = 1): static
    {
        return $this->set(parent::dirname($levels));
    }

    public function finish($cap): static
    {
        return $this->set(parent::finish($cap));
    }

    public function kebab(): static
    {
        return $this->set(parent::kebab());
    }

    public function limit($limit = 100, $end = '...', $preserveWords = false): static
    {
        return $this->set(parent::limit($limit, $end, $preserveWords));
    }

    public function lower(): static
    {
        return $this->set(parent::lower());
    }

    public function markdown(array $options = [], array $extensions = []): static
    {
        return $this->set(parent::markdown($options, $extensions));
    }

    public function inlineMarkdown(array $options = [], array $extensions = []): static
    {
        return $this->set(parent::inlineMarkdown($options, $extensions));
    }

    public function mask($character, $index, $length = null, $encoding = 'UTF-8'): static
    {
        return $this->set(parent::mask($character, $index, $length, $encoding));
    }

    public function match($pattern): static
    {
        return $this->set(parent::match($pattern));
    }

    public function numbers(): static
    {
        return $this->set(parent::numbers());
    }

    public function padBoth($length, $pad = ' '): static
    {
        return $this->set(parent::padBoth($length, $pad));
    }

    public function padLeft($length, $pad = ' '): static
    {
        return $this->set(parent::padLeft($length, $pad));
    }

    public function padRight($length, $pad = ' '): static
    {
        return $this->set(parent::padRight($length, $pad));
    }

    public function pipe(callable $callback): static
    {
        return $this->set(parent::pipe($callback));
    }

    public function plural($count = 2, $prependCount = false): static
    {
        return $this->set(parent::plural($count, $prependCount));
    }

    public function pluralStudly($count = 2): static
    {
        return $this->set(parent::pluralStudly($count));
    }

    public function pluralPascal($count = 2): static
    {
        return $this->set(parent::pluralPascal($count));
    }

    public function prepend(...$values): static
    {
        return $this->set(parent::prepend(...$values));
    }

    public function reverse(): static
    {
        return $this->set(parent::reverse());
    }

    public function repeat(int $times): static
    {
        return $this->set(parent::repeat($times));
    }

    public function replaceArray($search, $replace): static
    {
        return $this->set(parent::replaceArray($search, $replace));
    }

    public function replaceFirst($search, $replace): static
    {
        return $this->set(parent::replaceFirst($search, $replace));
    }

    public function replaceLast($search, $replace): static
    {
        return $this->set(parent::replaceLast($search, $replace));
    }

    public function replaceMatches($pattern, $replace, $limit = -1): static
    {
        return $this->set(parent::replaceMatches($pattern, $replace, $limit));
    }

    public function squish(): static
    {
        return $this->set(parent::squish());
    }

    public function start($prefix): static
    {
        return $this->set(parent::start($prefix));
    }

    public function stripTags($allowedTags = null): static
    {
        return $this->set(parent::stripTags($allowedTags));
    }

    public function upper(): static
    {
        return $this->set(parent::upper());
    }

    public function title(): static
    {
        return $this->set(parent::title());
    }

    public function headline(): static
    {
        return $this->set(parent::headline());
    }

    public function initials($capitalize = false): static
    {
        return $this->set(parent::initials($capitalize));
    }

    public function apa(): static
    {
        return $this->set(parent::apa());
    }

    public function transliterate($unknown = '?', $strict = false): static
    {
        return $this->set(parent::transliterate($unknown, $strict));
    }

    public function singular(): static
    {
        return $this->set(parent::singular());
    }

    public function slug($separator = '-', $language = 'en', $dictionary = ['@' => 'at']): static
    {
        return $this->set(parent::slug($separator, $language, $dictionary));
    }

    public function snake($delimiter = '_'): static
    {
        return $this->set(parent::snake($delimiter));
    }

    public function studly(bool $normalize = false): static
    {
        return $this->set(parent::studly($normalize));
    }

    public function pascal(bool $normalize = false): static
    {
        return $this->set(parent::pascal($normalize));
    }

    public function substr($start, $length = null, $encoding = 'UTF-8'): static
    {
        return $this->set(parent::substr($start, $length, $encoding));
    }

    public function substrReplace($replace, $offset = 0, $length = null): static
    {
        return $this->set(parent::substrReplace($replace, $offset, $length));
    }

    public function swap(array $map): static
    {
        return $this->set(parent::swap($map));
    }

    public function trim($characters = null): static
    {
        return $this->set(parent::trim($characters));
    }

    public function ltrim($characters = null): static
    {
        return $this->set(parent::ltrim($characters));
    }

    public function rtrim($characters = null): static
    {
        return $this->set(parent::rtrim($characters));
    }

    public function lcfirst(): static
    {
        return $this->set(parent::lcfirst());
    }

    public function ucfirst(): static
    {
        return $this->set(parent::ucfirst());
    }

    public function ucwords($separators = " \t\r\n\f\v"): static
    {
        return $this->set(parent::ucwords($separators));
    }

    public function words($words = 100, $end = '...'): static
    {
        return $this->set(parent::words($words, $end));
    }

    public function wordWrap($characters = 75, $break = "\n", $cutLongWords = false): static
    {
        return $this->set(parent::wordWrap($characters, $break, $cutLongWords));
    }

    public function wrap($before, $after = null): static
    {
        return $this->set(parent::wrap($before, $after));
    }

    public function unwrap($before, $after = null): static
    {
        return $this->set(parent::unwrap($before, $after));
    }

    public function toBase64(): static
    {
        return $this->set(parent::toBase64());
    }

    public function fromBase64($strict = false): static
    {
        return $this->set(parent::fromBase64($strict));
    }

    public function hash(string $algorithm): static
    {
        return $this->set(parent::hash($algorithm));
    }

    public function encrypt(bool $serialize = false): static
    {
        return $this->set(parent::encrypt($serialize));
    }

    public function decrypt(bool $serialize = false): static
    {
        return $this->set(parent::decrypt($serialize));
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
        return $this->set(app_path($this->value));
    }

    public function asRawQuery(): RawQuery
    {
        return RawQuery::create($this->value);
    }

    public function asRegex(): Regex
    {
        return Regex::create($this->value);
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

    /**
     * Checks whether a given named constant exists using `defined()` function from php
     * @return bool
     */
    public function isDefined(): bool
    {
        return defined($this->value);
    }

    /**
     * Joins the non-empty words with a space
     */
    private static function joinWords(array $words): string
    {
        return word_concat(array_filter($words, fn (string $word) => $word !== ''));
    }

    /**
     * Joins the non-empty paths with a single `/`, keeping the slashes at the start and the end of the whole path
     */
    private static function joinPaths(array $paths): string
    {
        $paths = array_values(array_filter($paths, fn (string $path) => $path !== ''));
        $lastIndex = count($paths) - 1;

        return path_concat(array_map(
            fn (string $path, int $index) => match (true) {
                $index === 0 && $index === $lastIndex => $path,
                $index === 0 => rtrim($path, PATH_DELIMITER),
                $index === $lastIndex => ltrim($path, PATH_DELIMITER),
                default => trim($path, PATH_DELIMITER),
            },
            $paths,
            array_keys($paths)
        ));
    }

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
