<?php

namespace JundFauz\EloquentKompact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use JundFauz\EloquentKompact\Builder\Str;

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
    return Helper\Arr::pullMany($array, ...$keys);
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

function not_same_with(mixed $firstValue, mixed $secondValue): bool
{
    return $firstValue !== $secondValue;
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