# Upgrading

English | [Bahasa Indonesia](UPGRADE.id.md)

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
| `is_zero($value)` | `is_numeric($value) && (float) $value === 0.0` |

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
