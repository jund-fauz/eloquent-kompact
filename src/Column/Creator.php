<?php

namespace JundFauz\EloquentKompact\Column;

use Illuminate\Support\Str;
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

    // Satu-satunya tempat skema penamaan kolom ditentukan.
    // Ganti ke '/^/' untuk beralih ke skema prefix; penyisipan, pencocokan,
    // dan pelucutan token semuanya ikut menyesuaikan sendiri.
    protected const string ToBeReplaced = '/$/';

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
     * Token kolom => tipe cast Eloquent.
     *
     * Sengaja protected supaya tidak ikut terjaring reflection di tokens().
     * Token yang tidak terdaftar di sini memang tidak butuh cast.
     * Enum tidak ada di sini karena kelas tujuannya bergantung pada tabel
     * model, jadi di-resolve oleh AutoCast::resolveEnumCast().
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
        $constant = self::class.'::'.Str::pascal($method);

        if (! defined($constant) || ! in_array(constant($constant), self::tokens(), true)) {
            self::methodNotFound($method);
        }

        return preg_replace(self::ToBeReplaced, constant($constant), $parameters[0]);
    }

    public static function quantity(): array
    {
        return [
            Creator::gram('quantity'),
            Creator::pcs('quantity'),
        ];
    }

    /**
     * Seluruh token penanda tipe kolom, yang terpanjang lebih dulu supaya
     * token yang kebetulan jadi akhiran token lain tidak menang duluan.
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
     * Regex pencocok token; posisinya diturunkan langsung dari ToBeReplaced.
     */
    protected static function matcher(): string
    {
        return self::$matcher ??= (static function (): string {
            $alternation = implode('|', array_map(
                static fn (string $token): string => preg_quote($token, '/'),
                self::tokens()
            ));

            $anchor = trim(self::ToBeReplaced, '/');

            // ToBeReplaced itu zero-width, jadi sisipkan penanda uji sekali
            // untuk tahu di sisi mana dia menaruh token.
            return Str::startsWith(preg_replace(self::ToBeReplaced, 'X', 'a'), 'X')
                ? "/$anchor($alternation)/"  // skema prefix  → /^(enum_|array_|…)/
                : "/($alternation)$anchor/"; // skema suffix  → /(_enum|_array|…)$/
        })();
    }

    public static function getPlainColumnName(string $column): string
    {
        return preg_replace(self::matcher(), '', $column);
    }

    /**
     * Token tipe milik sebuah kolom, atau '' kalau kolomnya polos.
     */
    public static function getColumnType(string $column, bool $clean = false): string
    {
        $result = Str::match(self::matcher(), $column);
        if ($clean) {
            $result = Str::replace('_', '', $result);
        }

        return $result;
    }

    /**
     * Tipe cast Eloquent untuk sebuah token, atau null kalau tidak butuh cast.
     */
    public static function castOf(string $token): ?string
    {
        return self::Casts[$token] ?? null;
    }
}
