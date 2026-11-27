# Upgrading

English | [Bahasa Indonesia](UPGRADE.id.md)

## From 2.x to 3.0

```bash
composer require jund-fauz/eloquent-kompact:^3.0
```

### Artisan commands renamed to `make:*`

The generating Artisan commands now use the `make:` prefix:

| 2.x | 3.0 |
|---|---|
| `php artisan kompact:column-builder` | `php artisan make:column-builder` |
| `php artisan kompact:ide-helper` | `php artisan make:ide-helper` |
| `php artisan make:kompact-math` | `php artisan make:math` |

Column builder classes generated in `2.x` keep working and are still updated automatically after migrations.

### `Schema\Blueprint` container binding

`EloquentKompactServiceProvider` now binds `Illuminate\Database\Schema\Blueprint` to [`JundFauz\EloquentKompact\Schema\Blueprint`](README.md#migration-helpers). Existing migrations continue to work without changing their `Blueprint` import.

If your application previously bound its own custom `Blueprint` subclass in `AppServiceProvider` (or `DB::connection()->setSchemaGrammar()` / `Schema::blueprintResolver()`), have that subclass extend `JundFauz\EloquentKompact\Schema\Blueprint` to keep Kompact's schema helpers and plural calls.

### Removed deprecated helper functions

All helper functions deprecated during `2.x` have been removed:

| 2.x | 3.0 |
|---|---|
| `model_compact($model, ...$columns)` | `object_compact($model, ...$columns)` |
| `String\before_last($subject, $search)` | `String\str('before_last', $subject, $search)` |
| `String\lower($subject)` / `String\upper($subject)` | `String\str('lower', $subject)` / `String\str('upper', $subject)` |
| `not_in($needle, $haystack, $strict = false)` | `is_not('in', $needle, $haystack, $strict)` |
| `not_in_array($needle, $haystack, $strict = false)` | `is_not('in_array', $needle, $haystack, $strict)` |
| `is_all_not($check, ...$values)` / `is_any_not($check, ...$values)` | `is_all("not_$check", ...$values)` / `is_any("not_$check", ...$values)` |

### Removed deprecated `AdditionalQueries` methods

All `AdditionalQueries` methods deprecated during `2.x` have been removed:

| 2.x | 3.0 |
|---|---|
| `$model->matchAndDirty($conditions)` | `$model->isAll('dirty', array_keys($conditions)) && $model->match($conditions)` |
| `$model->sameWithAndDirty($col, $val, $cast = null)` | `$model->isAllOf($col, 'dirty', sameWith: [$val, $cast])` |
| `$model->notSameWithAndDirty($col, $val, $cast = null)` | `$model->isAllOf($col, 'dirty', notSameWith: [$val, $cast])` |
| `$model->sameWithOtherAndDirty($other, ...$cols)` | `$model->isAll('dirty', ...$cols) && $model->sameWithOther($other, ...$cols)` |
| `$model->sameWithOtherOrAndDirty($other, ...$cols)` | `array_any($cols, fn ($col) => $model->isAllOf($col, 'dirty', sameWith: [$other->{$col}]))` |
| `$model->notSameWithOtherAndDirty($other, ...$cols)` | `$model->isAll('dirty', ...$cols) && ! $model->sameWithOther($other, ...$cols, ...['or'])` |
| `$model->notSameWithOtherOrAndDirty($other, ...$cols)` | `array_any($cols, fn ($col) => $model->isAllOf($col, 'dirty', notSameWith: [$other->{$col}]))` |
| `$model->isMorphModelSameAndDirty($morphName, $modelClass)` | `$model->isAllOf(ColumnCreator::type($morphName), 'dirty', sameWith: [Relation::getMorphAlias($modelClass)])` |
| `$model->isBlankAndDirty($col)` / `$model->isFilledAndDirty($col)` / `$model->isNullAndDirty($col)` / `$model->isNotNullAndDirty($col)` | `$model->isAllOf($col, 'dirty', 'blank')` / `'filled'` / `'null'` / `'not_null'` |
| `$model->wereChanged(...$cols)` | `$cols ? $model->isAll('was_changed', ...$cols) : $model->wasChanged()` |
| `$model->wereNotChanged(...$cols)` | `$cols ? $model->isAllNot('was_changed', ...$cols) : ! $model->wasChanged()` |
| `$model->sameWithOtherOr($other, ...$cols)` | `$model->sameWithOther($other, ...$cols, ...['or'])` |
| `$model->notSameWithOther($other, ...$cols)` | `! $model->sameWithOther($other, ...$cols, ...['or'])` (when at least one column is given) |
| `$model->notSameWithOtherOr($other, ...$cols)` | `! $model->sameWithOther($other, ...$cols)` (when at least one column is given) |
| `$model->notSameWith($col, $val, $cast = null)` | `$model->not('same_with', $col, $val, $cast)` or `not_same_with: [$val, $cast]` in `isAllOf()` |
| `$model->getManyVia($columnNames, ...$relationClasses)` | `$model->getVia($columnNames, ...$relationClasses)` |
| `$model->assignIfBlank($cols, $val = null)` / `$model->assignIfNull($cols, $val = null)` | `$model->assign($cols, $val, 'blank')` (or `$model->assign($cols, 'blank')` for an array) / `'null'` |
| `$model->assignIfBlankFrom($other, ...$cols)` / `$model->assignIfNullFrom($other, ...$cols)` | `$model->assignFromIf('blank', $other, ...$cols)` / `'null'` |

Note: `getVia($columnNames, ...$relationClasses)` preserves the keys of `$columnNames` instead of reindexing them; wrap the result with `array_values()` if a list is needed. Also remember that `sameWithOther()` returns `false` without columns, so negating it with `!` matches `notSameWithOther()` / `notSameWithOtherOr()` when at least one column is given.

## From 1.x to 2.0

```bash
composer require jund-fauz/eloquent-kompact:^2.0
```

### Requirements

The package now also requires the `mbstring` extension, next to `bcmath`.

### Config

The config has a new key `path.math` (`App\Logic`), the default namespace of [`make:kompact-math`](README.md#math). If you published the config and kept the `path` key, add it there, since only the top-level keys are merged with the package defaults:

```php
'path' => [
    'model' => namespace_concat('App', 'Models'),
    'database_enum' => namespace_concat('App', 'Enums'),
    'math' => namespace_concat('App', 'Logic'),
],
```

### Helpers moved to the `Array` and `String` namespaces

The array helpers moved to `JundFauz\EloquentKompact\Array` and the string helpers to `JundFauz\EloquentKompact\String`, most of them without their `array_` or `str_` prefix. Change the import and the call:

```php
// 1.x
use function JundFauz\EloquentKompact\array_pull;
use function JundFauz\EloquentKompact\str_concat;

array_pull($data, 'name');
str_concat('a', 'b');

// 2.0
use function JundFauz\EloquentKompact\Array\pull;
use function JundFauz\EloquentKompact\String\concat;

pull($data, 'name');
concat('a', 'b');
```

| 1.x | 2.0 |
|---|---|
| `array_cast()` | `Array\cast()` |
| `array_compact()` | `Array\compact()` |
| `array_compact_and_unset()` | `Array\compact_and_unset()` |
| `array_filter_filled()` | `Array\filter_filled()` |
| `array_flat()` | `Array\flat()` |
| `array_get()` | `Array\get()` |
| `array_get_as_collection()` | `Array\array_get_as_collection()` |
| `array_get_or()` | `Array\get_or()` |
| `array_key_change()` | `Array\key_change()` |
| `array_key_values()` | `Array\key_values()` |
| `array_normalize()` | `Array\normalize()` |
| `array_pull()` | `Array\pull()` |
| `array_pull_list()` | `Array\pull_list()` |
| `array_wrap()` | `Array\wrap()` |
| `assign_as()` | `Array\assign_as()` |
| `get_array_list()` | `Array\get_array_list()` |
| `in()` | `Array\in()` |
| `is_array_or_collection()` | `Array\is_array_or_collection()` |
| `is_associative_array()` | `Array\is_associative()` |
| `is_count_zero()` / `is_count_one()` / `is_count_many()` | `Array\is_count_zero()` / `Array\is_count_one()` / `Array\is_count_many()` |
| `merge()` | `Array\merge()` |
| `merge_as_array()` | `Array\merge_as_array()` |
| `merge_mixed_array()` | `Array\merge_mixed()` |
| `str_concat()` | `String\concat()` |
| `str_before_last()` | `String\before_last()` |
| `str_lower()` / `str_upper()` | `String\lower()` / `String\upper()` |
| `str_empty_if_false()` | `String\empty_if_false()` |
| `str_constant()` | `String\constant()` |
| `stringify()` | `String\stringify()` |

The other helpers, such as `word_concat()`, `namespace_concat()`, `same_with()`, `cast_as()`, `collect()` and `model_compact()`, stay in `JundFauz\EloquentKompact`.

### Checks by name instead of separate helpers

The `null`, numeric, array, directory and constant checks are replaced by [`is_not()`, `is_all()`, `is_any()`, `is_all_not()` and `is_any_not()`](README.md#checks), which take the check by its name:

| 1.x | 2.0 |
|---|---|
| `is_all_null(...$values)` | `is_all('null', ...$values)` |
| `is_all_not_null(...$values)` | `is_all_not('null', ...$values)` |
| `is_one_of_them_null(...$values)` | `is_any('null', ...$values)` |
| `is_one_of_them_not_null(...$values)` | `is_any_not('null', ...$values)` |
| `is_all_numeric(...$values)` | `is_all('numeric', ...$values)` |
| `is_any_numeric(...$values)` | `is_any('numeric', ...$values)` |
| `is_all_not_numeric(...$values)` | `is_all_not('numeric', ...$values)` |
| `is_any_not_numeric(...$values)` | `is_any_not('numeric', ...$values)` |
| `is_not_numeric($value)` | `is_not('numeric', $value)` |
| `is_not_array($value)` | `is_not('array', $value)` |
| `is_not_dir($path)` | `is_not('dir', $path)` |
| `not_defined($constant)` | `is_not('defined', $constant)` |

`is_not_null()` stays.

These checks flatten nested arrays, also where the old helper did not: `is_one_of_them_null([null])` was `false`, `is_any('null', [null])` is `true`, and `is_all_numeric([1])` was `false`, `is_all('numeric', [1])` is `true`.

### Number helpers replaced by `Math`

The bcmath helpers are replaced by the static methods of [`Math\Math`](README.md#math):

| 1.x | 2.0 |
|---|---|
| `plus($a, $b)` / `minus($a, $b)` / `multiply($a, $b)` / `divide($a, $b)` | `Math::plus($a, $b)` / `Math::minus($a, $b)` / `Math::multiply($a, $b)` / `Math::divide($a, $b)` |
| `higher_than($a, $b)` / `lower_than($a, $b)` | `Math::higherThan($a, $b)` / `Math::lowerThan($a, $b)` |
| `higher_than_or_same_with($a, $b)` / `lower_than_or_same_with($a, $b)` | `Math::higherThanOrEqualTo($a, $b)` / `Math::lowerThanOrEqualTo($a, $b)` |
| `num_same_with($a, $b)` | `Math::equalTo($a, $b)` |

The precision is no longer an argument. Without one, `Math` uses the config `decimal.default-precision` (`3`) as before. For another precision, extend `Math`, or generate a class with `php artisan make:kompact-math`:

```php
// 1.x
plus('1.555', '1', 2); // '2.55'

// 2.0
class Money extends \JundFauz\EloquentKompact\Math\Math
{
    protected static ?int $precision = 2;
}

Money::plus('1.555', '1'); // '2.55'
```

The comparisons now throw an `InvalidArgumentException` for a value that is not numeric, where the old helpers returned `false`: `higher_than('abc', 1)` was `false`, `Math::higherThan('abc', 1)` throws. `null` is rejected the same way.

### Other removed helpers

| 1.x | 2.0 |
|---|---|
| `ternary($condition, $a, $b)` | `$condition ? $a : $b` |
| `elvis($value, $default)` | `$value ?: $default` |
| `null_coalescing($value, $default)` | `$value ?? $default`, or `Array\get($value, $default)` for an array key that may be missing |
| `is_positive_number($n)` / `is_natural_number($n)` | `$n > 0` |
| `is_negative_number($n)` | `$n < 0` |
| `is_whole_number($n)` | `$n >= 0` |
| `is_zero($value)` | `same_with($value, 0.0, 'float')` |

### Removed classes

| 1.x | 2.0 |
|---|---|
| `Builder\BoolB` | The PHP operators with the helpers, e.g. `same_with($user->role, 'admin') \|\| not_same_with($user->status, 'banned')` |
| `Helper\Arr::pullMany($array, ...$keys)` | `Array\pull($array, ...$keys)` |
| `Helper\Arr::isKeysDontExist($array, ...$keys)` | `array_all($keys, fn ($key) => ! array_key_exists($key, $array))` |
| `Column\Payload::allNullable` | `Column\ALL_NULLABLE` |
| `Column\Payload::build(...$payloads)` | `Array\merge(...$payloads)` |

### Generated column builder classes

`kompact:column-builder` no longer ends the files it generates with a new line. Your classes keep working; running the command again only removes that new line.
