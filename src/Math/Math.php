<?php

namespace JundFauz\EloquentKompact\Math;

use BadMethodCallException;
use DivisionByZeroError;
use InvalidArgumentException;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use function JundFauz\EloquentKompact\Array\is_count_zero;
use function JundFauz\EloquentKompact\is_any_not;
use function JundFauz\EloquentKompact\is_any_not_type_of;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\not_same_with;
use function JundFauz\EloquentKompact\same_with;

/**
 * @method static string plus(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static string minus(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static string multiply(float|int|string $firstNumber, float|int|string $secondNumber)
 * @method static string divide(float|int|string $firstNumber, float|int|string $secondNumber)
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

    /**
     * Calculates or compares two numbers with bcmath, with the precision of the class, or the config
     * eloquent-kompact.decimal.default-precision when it is null. Decimals after the precision are cut off
     *
     * @param string $name
     * @param array $arguments Exactly two numbers, each a float, an int or a numeric string
     * @return string|bool A numeric string for plus(), minus(), multiply() and divide(), otherwise a bool
     * @throws InvalidArgumentException When not exactly two numbers are given, a number is not numeric or the precision is negative
     * @throws DivisionByZeroError When divide() is called with zero as the second number
     * @throws BadMethodCallException When the method does not exist
     */
    public static function __callStatic(string $name, array $arguments): string|bool
    {
        if (not_same_with(count($arguments), 2)) {
            throw new InvalidArgumentException("You need to pass exact two argument");
        }

        if (is_any_not_type_of(['float', 'int', 'string'], $arguments)) {
            throw new InvalidArgumentException("Parameter type is invalid. Ensure that you pass either float, int, or string as parameter");
        }

        [$firstNumber, $secondNumber] = $arguments;
        $precision = static::$precision;

        if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
            throw new InvalidArgumentException('Non-numeric passed as argument or negative number as precision parameter');
        }

        normalize_numbers($precision, $firstNumber, $secondNumber);

        if (in_array($name, ['plus', 'minus', 'multiply', 'divide'])) {
            $method = match ($name) {
                'plus' => 'bcadd',
                'minus' => 'bcsub',
                'multiply' => 'bcmul',
                'divide' => 'bcdiv',
            };
            return $method($firstNumber, $secondNumber, $precision);
        } elseif (in_array($name, ['higherThan', 'lowerThan', 'equalTo', 'higherThanOrEqualTo', 'lowerThanOrEqualTo'])) {
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
     * Adds any amount of numbers with bcmath, cutting off the decimals after the precision of the class,
     * or the config eloquent-kompact.decimal.default-precision when it is null, only on the total
     *
     * @param float|int|string ...$numbers Each a float, an int or a numeric string
     * @return string A numeric string with the precision as its number of decimals, or '0' without a number
     * @throws InvalidArgumentException When a number is not numeric or the precision is negative
     */
    public static function sum(float|int|string ...$numbers): string
    {
        if (is_count_zero($numbers)) {
            return '0';
        }
        if (is_any_not_type_of(['float', 'int', 'string'], $numbers)) {
            throw new InvalidArgumentException("Parameter type is invalid. Ensure that you pass either float, int, or string as parameter");
        }
        $precision = static::$precision;
        normalize_numbers($precision, ...$numbers);
        if (is_any_not('numeric', $numbers) || $precision < 0) {
            throw new InvalidArgumentException('Non-numeric passed as argument or negative number as precision parameter');
        }
        $scale = max(array_map(fn (string $number) => str_contains($number, '.') ? strlen($number) - strpos($number, '.') - 1 : 0, $numbers));
        $result = '0';
        foreach ($numbers as $number) {
            $result = bcadd($result, $number, $scale);
        }

        return bcadd($result, '0', $precision);
    }
}

/**
 * @param $firstNumber
 * @param $secondNumber
 * @param $precision
 * @return bool
 * @internal
 */
function check_for_number_comparison($firstNumber, $secondNumber, $precision = null): bool
{
    return is_any_not('numeric', $firstNumber, $secondNumber) || (
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
 * @param int|null $precision
 * @return void
 * @internal
 */
function assign_default_precision(?int &$precision = null): void
{
    $precision ??= config('eloquent-kompact.decimal.default-precision') ?? 3;
}
