<?php

namespace JundFauz\EloquentKompact\Column;

use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use ReflectionClass;
use ReflectionClassConstant;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\in;
use function JundFauz\EloquentKompact\is_not_null;
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

    public const string Enum = 'enum';
    public const string Array = 'array';
    public const string Gram = 'gram';
    public const string Pcs = 'pcs';
    public const string Percentage = 'percentage';
    public const string Timestamp = 'at';
    public const string Time = 'time';
    public const string Boolean = 'is';
    public const string Dropdown = 'dropdown';
    public const string Relation = 'id';
    public const string Type = 'type';

    protected static string $delimiter = '_';

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
    /** @var array<string, array{token: string, cast: ?string}>|null Name (snake case) => definition. */
    protected static ?array $customTokens = null;
    /** The config the caches were built from; a different config rebuilds them. */
    protected static ?array $customTokensSource = null;
    /** @var array<string, string>|null Token => cast. */
    protected static ?array $casts = null;

    protected static ?string $matcher = null;

    public static function __callStatic(string $method, array $parameters): string
    {
        $constant = Str::of($method)->pascal()->addAtStart(self::class, '::');
        $customToken = self::getCustomToken($method);

        if ((defined($constant) && in(constant($constant), self::tokens(), true)) || $customToken) {
            return str_concat($parameters[0], self::$delimiter, match (true) {
                is_not_null($customToken) => $customToken,
                defined($constant) => constant($constant),
            });
        }

        self::methodNotFound($method);
    }

    public static function getDelimiter(): string
    {
        return self::$delimiter;
    }

    public static function configureDelimiter(string $delimiter): void
    {
        self::$delimiter = $delimiter;
        self::$matcher = null;
    }

    /**
     * `custom_tokens`, normalized from `'kg'` / `['kg']` / `['kg', 'decimal:3']`.
     * All caches are rebuilt whenever the config changes.
     *
     * @return array<string, array{token: string, cast: ?string}>
     */
    protected static function customTokens(): array
    {
        $source = config('eloquent-kompact.column.custom_tokens') ?? [];

        if (self::$customTokens === null || self::$customTokensSource !== $source) {
            self::$customTokensSource = $source;
            self::$tokens = self::$matcher = self::$casts = null;
            self::$customTokens = [];

            foreach ($source as $name => $definition) {
                [$token, $cast] = array_pad((array) $definition, 2, null);
                self::$customTokens[(string) Str::of($name)->snake()] = ['token' => $token, 'cast' => $cast];
            }
        }

        return self::$customTokens;
    }

    public static function getCustomToken(string $key): ?string
    {
        return self::customTokens()[(string) Str::of($key)->snake()]['token'] ?? null;
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
        $customTokens = array_column(self::customTokens(), 'token');

        return self::$tokens ??= collect((new ReflectionClass(self::class))->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC))
            ->map(static fn (ReflectionClassConstant $constant) => $constant->getValue())
            ->normalFilter('is_string')
            ->merge($customTokens)
            ->sort(static fn (string $a, string $b): int => strlen($b) <=> strlen($a))
            ->values();
    }

    /**
     * Token-matching regex; its position is derived directly from the config.
     */
    protected static function matcher(): string
    {
        // tokens() first, so that a config change resets $matcher before it's read.
        $tokens = self::tokens();

        return self::$matcher ??=
            $tokens
                ->map(static fn (string $token) => preg_quote($token, '/'))
                ->implodeAsStr('|')
                ->asRegex()
                ->group()
                ->before(preg_quote(self::$delimiter, '/'))
                ->endsWith()
                ->asStr()
                ->value();
    }

    public static function getPlainColumnName(string $column): Str
    {
        return Str::of($column)->removeByRegex(self::matcher());
    }

    /**
     * A column's type token, or ‘’ if the column doesn't have token.
     */
    public static function getColumnType(string $column, bool $withDelimiter = false): string
    {
        $result = Str::of($column)->match(self::matcher());
        if ($withDelimiter && $result->isNotEmpty()) {
            $result = $result->addAtStart(self::$delimiter);
        }

        return $result->value();
    }

    /**
     * An Eloquent cast for a token, or null if no cast is needed.
     */
    public static function castOf(string $token): ?string
    {
        $customTokens = self::customTokens();

        // Custom tokens come first; `+` keeps the left-hand key.
        self::$casts ??= array_filter(array_column($customTokens, 'cast', 'token'), is_not_null(...)) + self::Casts;

        return self::$casts[$token] ?? null;
    }
}
