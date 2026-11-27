<?php

namespace JundFauz\EloquentKompact\Math;

use BadMethodCallException;
use DivisionByZeroError;
use InvalidArgumentException;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use function JundFauz\EloquentKompact\Array\flat;
use function JundFauz\EloquentKompact\Array\is_count_zero;
use function JundFauz\EloquentKompact\is_any;
use function JundFauz\EloquentKompact\is_any_not_type_of;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\String\concat;

/**
 * @method static string plus(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static string minus(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static string multiply(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static string divide(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static string sum(float|int|string|array ...$numbers)
 * @method static string diff(float|int|string|array ...$numbers)
 * @method static string product(float|int|string|array ...$numbers)
 * @method static string quotient(float|int|string|array ...$numbers)
 * @method static bool higherThan(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static bool lowerThan(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static bool equalTo(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static bool higherThanOrEqualTo(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static bool lowerThanOrEqualTo(float|int|string $firstNumber, float|int|string $secondNumber)
 */
class Math
{
    use ErrorThrow;

    protected static ?int $precision = null;
    private static array $single = ['plus', 'minus', 'multiply', 'divide'];
    private static array $batch = ['sum', 'diff', 'product', 'quotient'];
    private static array $compare = ['higherThan', 'lowerThan', 'equalTo', 'higherThanOrEqualTo', 'lowerThanOrEqualTo'];

    /**
     * Calculates or compares numbers with bcmath, with the precision of the class, or the config
     * eloquent-kompact.decimal.default-precision when it is null. Decimals after the precision are cut off,
     * only on the final result for sum(), diff(), product() and quotient()
     *
     * @param string $name
     * @param array $arguments Two numbers for single and comparison methods, or any amount of numbers for batch methods
     * @return string|bool A numeric string for arithmetic methods, otherwise a bool
     * @throws InvalidArgumentException When the argument count is invalid, a number is not numeric or the precision is negative
     * @throws DivisionByZeroError When divide() or quotient() divides by zero
     * @throws BadMethodCallException When the method does not exist
     * @final
     */
    public static function __callStatic(string $name, array $arguments): string|bool
    {
        if (in_array($name, self::$batch)) {
            return self::batch($name, $arguments);
        }

        if (not_same_with(count($arguments), 2)) {
            throw new InvalidArgumentException("You need to pass exact two argument");
        }

        if (array_any($arguments, fn ($value) => is_array($value)) || is_any_not_type_of(['float', 'int', 'string'], $arguments)) {
            throw new InvalidArgumentException("Parameter type is invalid. Ensure that you pass either float, int, or string as parameter");
        }

        [$firstNumber, $secondNumber] = $arguments;
        $precision = static::$precision;

        if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
            throw new InvalidArgumentException('Non-numeric passed as argument or negative number as precision parameter');
        }

        normalize_numbers($precision, $firstNumber, $secondNumber);

        if (in_array($name, self::$single)) {
            return bc(self::$single, $name)($firstNumber, $secondNumber, $precision);
        } elseif (in_array($name, self::$compare)) {
            $result = bccomp($firstNumber, $secondNumber, $precision);
            return match ($name) {
                'higherThan' => $result > 0,
                'lowerThan' => $result < 0,
                'equalTo' => same_with($result, 0),
                'higherThanOrEqualTo' => $result >= 0,
                'lowerThanOrEqualTo' => $result <= 0
            };
        }

        self::methodNotFound($name);
    }

    /**
     * Execute a variadic batch arithmetic operation (`sum`, `diff`, `product`, or `quotient`) using bcmath.
     *
     * @param string $name
     * @param array $numbers
     * @return string
     */
    private static function batch(string $name, array $numbers): string
    {
        flat($numbers);
        if (is_count_zero($numbers)) {
            return '0';
        }
        if (is_any_not_type_of(['float', 'int', 'string'], $numbers)) {
            throw new InvalidArgumentException("Parameter type is invalid. Ensure that you pass either float, int, or string as parameter");
        }
        $precision = static::$precision;
        normalize_numbers($precision, ...$numbers);
        if (is_any('not_numeric', $numbers) || $precision < 0) {
            throw new InvalidArgumentException('Non-numeric passed as argument or negative number as precision parameter');
        }
        if (same_with(count($numbers), 1)) {
            return bcadd($numbers[0], '0', $precision);
        }
        if (same_with($name, 'quotient')) {
            return bcdiv(array_shift($numbers), exact_product($numbers), $precision);
        }
        if (same_with($name, 'product')) {
            return bcadd(exact_product($numbers), '0', $precision);
        }
        $scale = max(array_map(decimal_scale(...), $numbers));
        $result = array_shift($numbers);
        $function = bc(self::$batch, $name);
        foreach ($numbers as $number) {
            $result = $function($result, $number, $scale);
        }

        return bcadd($result, '0', $precision);
    }
}

/**
 * Resolve the corresponding `bc*` function name for a math method index.
 *
 * @param array<int, string> $payload
 * @param string $name
 * @return string
 * @internal
 */
function bc(array $payload, string $name): string
{
    return concat('bc', match (array_search($name, $payload, true)) {
        0 => 'add',
        1 => 'sub',
        2 => 'mul',
        3 => 'div'
    });
}

/**
 * Count the number of decimal digits after the decimal point in a numeric string.
 *
 * @param string $number
 * @return int
 * @internal
 */
function decimal_scale(string $number): int
{
    return str_contains($number, '.') ? strlen($number) - strpos($number, '.') - 1 : 0;
}

/**
 * Multiply a list of normalized numeric strings without intermediate rounding loss.
 *
 * @param array<int, string> $numbers
 * @return string
 * @internal
 */
function exact_product(array $numbers): string
{
    $result = array_shift($numbers);
    foreach ($numbers as $number) {
        $scale = decimal_scale($result) + decimal_scale($number);
        $result = bcmul($result, $number, $scale);
        if (str_contains($result, '.')) {
            $result = rtrim(rtrim($result, '0'), '.');
        }
    }

    return $result;
}

/**
 * Determine whether either operand or the precision is invalid for bcmath comparison/arithmetic.
 *
 * @param mixed $firstNumber
 * @param mixed $secondNumber
 * @param mixed $precision
 * @return bool
 * @internal
 */
function check_for_number_comparison($firstNumber, $secondNumber, $precision = null): bool
{
    return is_any('not_numeric', $firstNumber, $secondNumber) || (
            is_not_null($precision) && (! is_numeric($precision) || is_float($precision) || $precision < 0)
        );
}

/**
 * Fills in the default precision and turns the numbers into strings bcmath reads, with exponents written out
 *
 * @param int|null $precision
 * @param mixed ...$numbers
 * @return void
 * @internal
 */
function normalize_numbers(?int &$precision = null, &...$numbers): void
{
    assign_default_precision($precision);
    foreach ($numbers as &$number) {
        $number = expand_exponent(is_float($number)
            ? json_encode($number, JSON_PRESERVE_ZERO_FRACTION)
            : trim((string)$number));
    }
}

/**
 * Expand scientific notation in a numeric string into plain decimal form.
 *
 * @param string $number
 * @return string
 * @internal
 */
function expand_exponent(string $number): string
{
    if (! str_contains(strtolower($number), 'e')) {
        return $number;
    }

    if (! preg_match('/^([+-]?)(\d*)(?:\.(\d*))?[eE]([+-]?\d+)$/', $number, $matches)) {
        return $number;
    }

    $sign = $matches[1];
    $integer = $matches[2] === '' ? '0' : $matches[2];
    $fraction = $matches[3] ?? '';
    $digits = $integer . $fraction;
    $decimalPosition = strlen($integer) + (int)$matches[4];

    if ($decimalPosition <= 0) {
        $decimal = '0.' . str_repeat('0', -$decimalPosition) . $digits;
    } elseif ($decimalPosition >= strlen($digits)) {
        $decimal = $digits . str_repeat('0', $decimalPosition - strlen($digits));
    } else {
        $decimal = substr($digits, 0, $decimalPosition)
            . '.'
            . substr($digits, $decimalPosition);
    }

    return $sign . $decimal;
}

/**
 * Populate `$precision` from the package configuration (falling back to `3`) when `null`.
 *
 * @param int|null $precision
 * @return void
 * @internal
 */
function assign_default_precision(?int &$precision = null): void
{
    $precision ??= config('eloquent-kompact.decimal.default-precision') ?? 3;
}
