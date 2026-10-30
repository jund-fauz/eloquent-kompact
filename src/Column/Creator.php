<?php

namespace JundFauz\EloquentKompact\Column;

use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use ReflectionClass;
use ReflectionClassConstant;

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

    /** @var list<string>|null */
    protected static ?array $tokens = null;

    protected static ?string $matcher = null;

    public static function __callStatic(string $method, array $parameters): string
    {
        $constant = (string) Str::of($method)->pascal()->addAtStart(self::class, '::');

        if (! defined($constant) || ! in_array(constant($constant), self::tokens(), true)) {
            self::methodNotFound($method);
        }

        return preg_replace(config('eloquent-kompact.column.to_be_replaced'), constant($constant), $parameters[0]);
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
     * @return list<string>
     */
    public static function tokens(): array
    {
        return self::$tokens ??= (static function (): array {
            $values = array_map(
                static fn (ReflectionClassConstant $constant) => $constant->getValue(),
                (new ReflectionClass(self::class))->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC)
            );

            $tokens = array_values(array_filter($values, is_string(...)));

            usort($tokens, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

            return $tokens;
        })();
    }

    /**
     * Token-matching regex; its position is derived directly from the config.
     */
    protected static function matcher(): string
    {
        return self::$matcher ??= (static function (): string {
            $alternation = implode('|', array_map(
                static fn (string $token): string => preg_quote($token, '/'),
                self::tokens()
            ));

            $toBeReplaceRegex = config('eloquent-kompact.column.to_be_replaced');

            $anchor = trim($toBeReplaceRegex, '/');

            // “ToBeReplaced” is a zero-width character, so insert a test marker once
            // to determine on which side it places the token.
            return Str::of('a')->replaceByRegex($toBeReplaceRegex, 'X')->startsWith('X')
                ? "/$anchor($alternation)/"  // skema prefix  → /^(enum_|array_|…)/
                : "/($alternation)$anchor/"; // skema suffix  → /(_enum|_array|…)$/
        })();
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
            $result = $result->remove('_');
        }

        return (string) $result;
    }

    /**
     * An Eloquent cast for a token, or null if no cast is needed.
     */
    public static function castOf(string $token): ?string
    {
        return self::Casts[$token] ?? null;
    }
}
