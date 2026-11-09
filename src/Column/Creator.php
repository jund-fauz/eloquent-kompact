<?php

namespace JundFauz\EloquentKompact\Column;

use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use JundFauz\EloquentKompact\Regex\Regex;
use ReflectionClass;
use ReflectionClassConstant;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\not_defined;
use function JundFauz\EloquentKompact\not_in;
use function JundFauz\EloquentKompact\str_concat;

/**
 * @method static string enum(string $column)
 * @method static string array(string $column)
 * @method static string gram(string $column)
 * @method static string pcs(string $column)
 * @method static string percentage(string $column)
 * @method static string timestamp(string $column)
 * @method static string time(string $column)
 * @method static string boolean(string $column)
 * @method static string dropdown(string $column)
 * @method static string relation(string $column)
 * @method static string type(string $column)
 */
final class Creator
{
    use ErrorThrow;

    public const string Enum = '_enum';
    public const string Array = '_array';
    public const string Gram = '_gram';
    public const string Pcs = '_pcs';
    public const string Percentage = '_percentage';
    public const string Timestamp = '_at';
    public const string Time = '_time';
    public const string Boolean = '_is';
    public const string Dropdown = '_dropdown';
    public const string Relation = '_id';
    public const string Type = '_type';

    /**
     * Column token => Eloquent cast type.
     *
     * Intentionally marked as `protected` so it isn’t caught by reflection in `tokens()`.
     * Tokens not listed here do not require a cast.
     * Enums are not listed here because their target classes depend on the
     * model table, so they are resolved by `AutoCast::resolveEnumCast()`.
     */
    protected const array Casts = [
        self::Array => 'array',
        self::Boolean => 'boolean',
        self::Gram => 'decimal:7',
        self::Percentage => 'decimal:2'
    ];

    /** @var Collection<int, string>|null */
    protected static ?Collection $tokens = null;

    protected static ?string $matcher = null;

    public static function __callStatic(string $method, array $parameters): string
    {
        $constant = (string)Str::of($method)->pascal()->addAtStart(self::class, '::');

        if (not_defined($constant) || not_in(constant($constant), self::tokens(), true)) {
            self::methodNotFound($method);
        }

        return str_concat($parameters[0], constant($constant));
    }

    public static function quantity(): array
    {
        return [
            Creator::gram('quantity'),
            Creator::pcs('quantity'),
        ];
    }

    /**
     * All column-type tokens, with the longest one first, so that
     * tokens that happen to be the suffixes of other tokens do not win first.
     *
     * @return Collection<int, string>
     */
    public static function tokens(): Collection
    {
        return self::$tokens ??= collect((new ReflectionClass(self::class))->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC))
            ->map(static fn (ReflectionClassConstant $constant) => $constant->getValue())
            ->normalFilter('is_string')
            ->sort(static fn (string $a, string $b): int => strlen($b) <=> strlen($a))
            ->values();
    }

    /**
     * Token-matching regex; its position is derived directly from the config.
     */
    protected static function matcher(): string
    {
        return self::$matcher ??= Regex::create(
            self::tokens()
            ->map(static fn (string $token) => preg_quote($token, '/'))
            ->implode('|')
        )
            ->group()
            ->endsWith()
            ->build();
    }

    public static function getPlainColumnName(string $column): Str
    {
        return Str::of($column)->removeByRegex(self::matcher());
    }

    /**
     * A column's type token, or ‘’ if the column doesn't have token.
     */
    public static function getColumnType(string $column, bool $clean = false): string
    {
        $result = Str::of($column)->match(self::matcher());
        if ($clean) {
            $result = $result->removeStart('_');
        }

        return (string)$result;
    }

    /**
     * An Eloquent cast for a token, or null if no cast is needed.
     */
    public static function castOf(string $token): ?string
    {
        return self::Casts[$token] ?? null;
    }
}
