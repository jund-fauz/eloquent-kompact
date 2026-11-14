<?php

namespace JundFauz\EloquentKompact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use JundFauz\EloquentKompact\Builder\Collection;
use function JundFauz\EloquentKompact\Array\merge_mixed;

/**
 * The value cast with a cast of Laravel, e.g. 'integer', 'decimal:2' or an enum class; the value as it is without a cast
 *
 * @param mixed $value
 * @param string|null $cast
 * @return mixed
 */
function cast_as(mixed $value, ?string $cast = null): mixed
{
    if (is_null($cast)) {
        return $value;
    }

    static $caster;
    $caster ??= new class extends Model { };

    return (clone $caster)->mergeCasts(['value' => $cast])->setRawAttributes(['value' => $value])->value;
}

/**
 * Column => value of the model; a string key or a named argument renames the column, e.g. model_compact($user, nama: 'name')
 *
 * @param Model $model
 * @param string|array ...$columns
 * @return array
 */
function model_compact(Model $model, string|array ...$columns): array
{
    return collect(merge_mixed($columns))
        ->mapWithKeys(fn (string $column, mixed $key) => [(is_string($key) ? $key : $column) => $model->{$column}])
        ->toArray();
}

/**
 * A Collection of this package: the items of one argument, or every argument as an item
 *
 * @param mixed ...$data
 * @return Collection
 */
function collect(mixed ...$data): Collection
{
    return new Collection(match (count($data)) {
        1 => $data[0],
        0 => [],
        default => $data
    });
}

/**
 * Whether the app runs locally or runs its unit tests
 *
 * @return bool
 */
function is_app_local_or_test(): bool
{
    return App::isLocal() || App::runningUnitTests();
}
