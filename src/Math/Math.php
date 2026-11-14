<?php

namespace JundFauz\EloquentKompact\Math;

use InvalidArgumentException;
use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
use function JundFauz\EloquentKompact\check_for_number_comparison;
use function JundFauz\EloquentKompact\is_any_not_type_of;
use function JundFauz\EloquentKompact\normalize_numbers;
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
     * @throws \DivisionByZeroError When divide() is called with zero as the second number
     * @throws \BadMethodCallException When the method does not exist
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
}