<?php

namespace JundFauz\EloquentKompact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use JundFauz\EloquentKompact\Builder\Collection;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Helper\Arr;

function is_not_null($value): bool
{
    return ! is_null($value);
}

function is_all_null(mixed ...$values): bool
{
    array_flat($values);
    return array_all($values, fn (mixed $value) => is_null($value));
}

function is_not_array($value): bool
{
    return ! is_array($value);
}

function str_concat(string|array ...$values): string
{
    array_flat($values);
    return join('', $values);
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
        (string) Str::of($path)
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

function same_with(mixed $firstValue, mixed $secondValue): bool
{
    return $firstValue === $secondValue;
}

function same_with_one_of(mixed $toCompareValue, mixed ...$anotherValues): bool
{
    return in_array($toCompareValue, $anotherValues, true);
}

function not_same_with(mixed $firstValue, mixed $secondValue): bool
{
    return $firstValue !== $secondValue;
}

function higher_than(int|float|string $firstNumber, int|float|string $secondNumber): bool
{
    return $firstNumber > $secondNumber;
}

function lower_than(int|float|string $firstNumber, int|float|string $secondNumber): bool
{
    return $firstNumber < $secondNumber;
}

function not(bool $condition): bool
{
    return ! $condition;
}

function model_compact(Model $model, string ...$columns): array
{
    return collect($columns)
        ->mapWithKeys(fn (string $column) => [$column => $model->{$column}])
        ->toArray();
}

function stringify(array $datas): array
{
    return array_map(fn (mixed $data) => is_array($data) ? stringify($data) : (string) $data, $datas);
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

function collect(mixed $data): Collection
{
    return new Collection($data);
}