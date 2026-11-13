<?php

namespace JundFauz\EloquentKompact;

use Countable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Helper\Arr;

/** Will be categorized per file in next major release */

function is_not_null($value): bool
{
    return ! is_null($value);
}

function is_all_null(mixed ...$values): bool
{
    array_flat($values);
    return array_all($values, fn (mixed $value) => is_null($value));
}

function is_one_of_them_null(mixed ...$values): bool
{
    return array_any($values, fn (mixed $value) => is_null($value));
}

function is_one_of_them_not_null(mixed ...$values): bool
{
    return ! is_all_null($values);
}

function is_all_not_null(mixed ...$values): bool
{
    array_flat($values);
    return array_all($values, fn (mixed $value) => is_not_null($value));
}

function is_not_array($value): bool
{
    return ! is_array($value);
}

function is_array_or_collection(mixed $data): bool
{
    return is_array($data) || $data instanceof SupportCollection;
}

function str_concat(string|array|Collection|SupportCollection ...$values): string
{
    return join('', collect($values)->map(fn ($value) => $value instanceof SupportCollection ? $value->toArray() : $value)->flatten()->toArray());
}

function word_concat(string|array ...$values): string
{
    array_flat($values);
    return join(' ', $values);
}

function schema_concat(string|array ...$values): string
{
    array_flat($values);
    return join(':', $values);
}

function relation_concat(string|array ...$values): string
{
    array_flat($values);
    return join('.', $values);
}

function write_line(string|array ...$lines): string
{
    array_flat($lines);
    return join(NEW_LINE, $lines);
}

function comment(string $sentence): string
{
    return word_concat('//', $sentence);
}

function add_tab(string $sentence, int $count = 1): string
{
    return str_concat(str_repeat(TAB, max($count, 0)), $sentence);
}

function namespace_concat(string|array ...$folderOrPackages): string
{
    array_flat($folderOrPackages);
    return join(NAMESPACE_DELIMITER, array_map(fn (string $part) => trim($part, NAMESPACE_DELIMITER), $folderOrPackages));
}

function path_concat(string|array ...$folderOrPackages): string
{
    array_flat($folderOrPackages);
    return join(PATH_DELIMITER, $folderOrPackages);
}

function full_path_on_app(string $path): string
{
    return app_path(
        (string)Str::of($path)
            ->replace(NAMESPACE_DELIMITER, PATH_DELIMITER)
            ->replaceByRegex('/^app(\/|$)/i', '')
            ->trim(PATH_DELIMITER)
    );
}

function array_flat(array &$array): void
{
    $array = Arr::flatten($array);
}

function array_pull(array &$array, string ...$keys): mixed
{
    return Arr::pullMany($array, ...$keys);
}

function array_pull_list(array &$array, string ...$keys): mixed
{
    $result = array_pull($array, ...$keys);
    return count($keys) > 1 ? array_values($result) : $result;
}

function array_get_or(array $array, string ...$keys): mixed
{
    return array_reduce($keys, fn ($acc, $key) => $acc ?? Arr::get($array, $key));
}

function array_wrap(mixed $data): array
{
    return Arr::wrap($data);
}

function array_filter_filled(array $data): array
{
    return array_filter($data, 'filled');
}

function array_key_change(array &$array, string $oldKey, string $newKey): void
{
    if ($oldKey === $newKey) {
        return;
    }
    $array[$newKey] = $array[$oldKey];
    unset($array[$oldKey]);
}

function array_key_values($array): array
{
    $keys = array_keys($array);
    $values = array_values($array);

    return merge_as_array(
        array_normalize($keys),
        array_normalize($values)
    );
}

function array_normalize(array $data): mixed
{
    return count($data) === 1 ? $data[0] : $data;
}

function array_cast(mixed $data): array
{
    return match (true) {
        $data instanceof SupportCollection => $data->toArray(),
        default => array_wrap($data)
    };
}

function array_get(mixed &$data, mixed $defaultValue = null, ?string $cast = null)
{
    $result = $data ?? $defaultValue;
    return is_not_null($cast) ? cast_as($result, $cast) : $result;
}

function array_get_as_collection(mixed &$data, mixed $defaultValue = null): Collection
{
    return collect(array_get($data, $defaultValue));
}

function get_array_list(array $from): array
{
    return array_filter($from, 'is_int', ARRAY_FILTER_USE_KEY);
}

function array_compact(array $array, string ...$keys): array
{
    $result = [];
    foreach ($keys as $key => $keyValue) {
        if (is_string($key)) {
            $result[$key] = array_get($array[$keyValue]);
        } else {
            $result[$keyValue] = array_get($array[$keyValue]);
        }
    }
    return $result;
}

function array_compact_and_unset(array &$array, string ...$keys): array
{

    $result = [];
    foreach ($keys as $key => $keyValue) {
        if (is_string($key)) {
            $result[$key] = array_get($array[$keyValue]);
        } else {
            $result[$keyValue] = array_get($array[$keyValue]);
        }
        unset($array[$keyValue]);
    }
    return $result;
}

function assign_as(mixed $data, string ...$keys): array
{
    $result = [];
    foreach ($keys as $key) {
        $result[$key] = $data;
    }
    return $result;
}

function is_associative_array(mixed $data): bool
{
    return is_array($data) && ! array_is_list($data);
}

function is_count_zero(array|Countable $data): bool
{
    return same_with(count($data), 0);
}

function is_count_one(array|Countable $data): bool
{
    return same_with(count($data), 1);
}

function is_count_many(array|Countable $data): bool
{
    return higher_than(count($data), 1);
}

function is_not_dir(string $filename): bool
{
    return not(is_dir($filename));
}

function str_before_last(string $subject, string $search): string
{
    return \Illuminate\Support\Str::beforeLast($subject, $search);
}

function str_lower(string $subject): string
{
    return \Illuminate\Support\Str::lower($subject);
}

function str_upper(string $subject): string
{
    return \Illuminate\Support\Str::upper($subject);
}

function str_empty_if_false(mixed $condition, string $value): string
{
    return $condition ? $value : '';
}

function str_constant(string $className, string $constantName): string
{
    return "$className::$constantName";
}

function cast_as(mixed $value, ?string $cast = null): mixed
{
    if (is_null($cast)) {
        return $value;
    }

    static $caster;
    $caster ??= new class extends Model { };

    return (clone $caster)->mergeCasts(['value' => $cast])->setRawAttributes(['value' => $value])->value;
}

function same_with(mixed $firstValue, mixed $secondValue, ?string $cast = null): bool
{
    return cast_as($firstValue, $cast) === cast_as($secondValue, $cast);
}

function same_with_one_of(mixed $toCompareValue, mixed ...$anotherValues): bool
{
    return in_array($toCompareValue, $anotherValues, true);
}

function not_same_with(mixed $firstValue, mixed $secondValue, ?string $cast = null): bool
{
    return ! same_with($firstValue, $secondValue, $cast);
}

/**
 * Whether every value is numeric
 *
 * @param mixed ...$value
 * @return bool
 */
function is_all_numeric(...$value): bool
{
    return array_all($value, fn ($value) => is_numeric($value));
}

/**
 * Whether at least one value is numeric
 *
 * @param mixed ...$value
 * @return bool
 */
function is_any_numeric(...$value): bool
{
    return array_any($value, fn ($value) => is_numeric($value));
}

/**
 * ! is_numeric()
 *
 * @param mixed $value
 * @return bool
 */
function is_not_numeric($value): bool
{
    return not(is_numeric($value));
}

/**
 * Whether no value is numeric
 *
 * @param mixed ...$value
 * @return bool
 */
function is_all_not_numeric(...$value): bool
{
    return array_all($value, fn ($value) => is_not_numeric($value));
}

/**
 * Whether at least one value is not numeric
 *
 * @param mixed ...$value
 * @return bool
 */
function is_any_not_numeric(...$value): bool
{
    return array_any($value, fn ($value) => is_not_numeric($value));
}

/**
 * $firstNumber + $secondNumber with bcmath, as a numeric string
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return string
 * @throws InvalidArgumentException When a number is not numeric or the precision is negative
 */
function plus($firstNumber, $secondNumber, ?int $precision = null): string
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        throw new InvalidArgumentException('Non-number passed as argument or negative number as precision parameter');
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return bcadd($firstNumber, $secondNumber, $precision);
}

/**
 * $firstNumber - $secondNumber with bcmath, as a numeric string
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return string
 * @throws InvalidArgumentException When a number is not numeric or the precision is negative
 */
function minus($firstNumber, $secondNumber, ?int $precision = null): string
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        throw new InvalidArgumentException('Non-number passed as argument or negative number as precision parameter');
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return bcsub($firstNumber, $secondNumber, $precision);
}

/**
 * $firstNumber * $secondNumber with bcmath, as a numeric string
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return string
 * @throws InvalidArgumentException When a number is not numeric or the precision is negative
 */
function multiply($firstNumber, $secondNumber, ?int $precision = null): string
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        throw new InvalidArgumentException('Non-number passed as argument or negative number as precision parameter');
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return bcmul($firstNumber, $secondNumber, $precision);
}

/**
 * $firstNumber / $secondNumber with bcmath, as a numeric string
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return string
 * @throws InvalidArgumentException When a number is not numeric or the precision is negative
 * @throws \DivisionByZeroError When $secondNumber is zero
 */
function divide($firstNumber, $secondNumber, ?int $precision = null): string
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        throw new InvalidArgumentException('Non-number passed as argument or negative number as precision parameter');
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return bcdiv($firstNumber, $secondNumber, $precision);
}

/**
 * $firstNumber > $secondNumber with bcmath; false when a number is not numeric or the precision is negative
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return bool
 */
function higher_than($firstNumber, $secondNumber, ?int $precision = null): bool
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        return false;
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return same_with(bccomp($firstNumber, $secondNumber, $precision), 1);
}

/**
 * $firstNumber == $secondNumber as numbers with bcmath; false when a number is not numeric or the precision is negative
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return bool
 */
function num_same_with($firstNumber, $secondNumber, ?int $precision = null): bool
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        return false;
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return same_with(bccomp($firstNumber, $secondNumber, $precision), 0);
}

/**
 * $firstNumber < $secondNumber with bcmath; false when a number is not numeric or the precision is negative
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return bool
 */
function lower_than($firstNumber, $secondNumber, ?int $precision = null): bool
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        return false;
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return same_with(bccomp($firstNumber, $secondNumber, $precision), -1);
}

/**
 * $firstNumber >= $secondNumber with bcmath; false when a number is not numeric or the precision is negative
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return bool
 */
function higher_than_or_same_with($firstNumber, $secondNumber, ?int $precision = null): bool
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        return false;
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return same_with_one_of(bccomp($firstNumber, $secondNumber, $precision), 1, 0);
}

/**
 * $firstNumber <= $secondNumber with bcmath; false when a number is not numeric or the precision is negative
 *
 * @param int|float|string $firstNumber
 * @param int|float|string $secondNumber
 * @param int|null $precision The number of decimals; the config eloquent-kompact.decimal.default-precision (3) when null. Decimals after it are cut off
 * @return bool
 */
function lower_than_or_same_with($firstNumber, $secondNumber, ?int $precision = null): bool
{
    if (check_for_number_comparison($firstNumber, $secondNumber, $precision)) {
        return false;
    }
    normalize_numbers($precision, $firstNumber, $secondNumber);
    return same_with_one_of(bccomp($firstNumber, $secondNumber, $precision), -1, 0);
}

/**
 * @param $firstNumber
 * @param $secondNumber
 * @param int|null $precision
 * @return bool
 * @internal
 */
function check_for_number_comparison($firstNumber, $secondNumber, ?int $precision = null): bool
{
    return is_any_not_numeric($firstNumber, $secondNumber)
        || (is_not_null($precision) && is_negative_number($precision));
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

function in(mixed $needle, array|SupportCollection|Collection $haystack, bool $strict = false): bool
{
    return in_array($needle, array_cast($haystack), $strict);
}

function not(bool $condition): bool
{
    return ! $condition;
}

function not_in(mixed $needle, array|SupportCollection|Collection $haystack, bool $strict = false): bool
{
    return ! in($needle, $haystack, $strict);
}

function not_in_array(mixed $needle, array $haystack, bool $strict = false): bool
{
    return ! in_array($needle, $haystack, $strict);
}

function not_defined(string $constant_name): bool
{
    return ! defined($constant_name);
}

function merge_mixed_array(array $data): array
{
    return is_count_zero($data)
        ? $data
        : merge(...array_map(
            fn (string|array $data, string|int $key) => match (true) {
                is_array($data) => $data,
                is_string($key) => [$key => $data],
                default => [$data]
            },
            $data,
            array_keys($data) ?: []
        ));
}

function ternary(mixed $condition, mixed $trueValue, mixed $falseValue)
{
    return $condition
        ? $trueValue
        : $falseValue;
}

function elvis(mixed $firstValue, mixed $defaultValue)
{
    return $firstValue ?: $defaultValue;
}

function null_coalescing(mixed $firstValue, mixed $defaultValue)
{
    return $firstValue ?? $defaultValue;
}

/**
 * @experimental
 * @param mixed $value
 * @param mixed $ifNotNullValue
 * @return mixed|null
 */
function if_not_null(mixed $value, mixed $ifNotNullValue): mixed
{
    return is_not_null($value)
        ? $ifNotNullValue
        : null;
}

function model_compact(Model $model, string|array ...$columns): array
{
    return collect(merge_mixed_array($columns))
        ->mapWithKeys(fn (string $column, mixed $key) => [(is_string($key) ? $key : $column) => $model->{$column}])
        ->toArray();
}

function stringify(array $data): array
{
    return array_map(fn (mixed $data) => is_array($data) ? stringify($data) : (string)$data, $data);
}

function merge(array ...$values): array
{
    return array_merge(...$values);
}

function merge_as_array(mixed ...$values): array
{
    $values = array_map(fn ($value) => $value instanceof SupportCollection ? $value->all() : $value, $values);
    $filled = array_filter_filled($values);

    if ($filled && array_all($filled, fn ($value) => is_associative_array($value))) {
        return array_merge(...array_values($filled));
    }

    array_flat($values);
    return $values;
}

function collect(mixed ...$data): Collection
{
    return new Collection(match (count($data)) {
        1 => $data[0],
        0 => [],
        default => $data
    });
}

/**
 * Whether the int is < 0
 *
 * @param int $number
 * @return bool
 */
function is_negative_number(int $number): bool
{
    return $number < 0;
}

/**
 * Whether the int is > 0
 *
 * @param int $number
 * @return bool
 */
function is_positive_number(int $number): bool
{
    return $number > 0;
}

function is_natural_number(int $number): bool
{
    return is_positive_number($number);
}

function is_whole_number(int $number): bool
{
    return $number >= 0;
}

function is_zero(mixed $data): bool
{
    return is_numeric($data) && same_with($data, 0.0, 'float');
}

function is_app_local_or_test(): bool
{
    return App::isLocal() || App::runningUnitTests();
}