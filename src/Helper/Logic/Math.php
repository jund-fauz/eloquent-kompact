<?php

namespace JundFauz\EloquentKompact;

use DivisionByZeroError;
use InvalidArgumentException;

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
        $number = expand_exponent(
            is_float($number)
                ? json_encode($number, JSON_PRESERVE_ZERO_FRACTION)
                : trim((string)$number)
        );
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