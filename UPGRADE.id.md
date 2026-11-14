# Upgrade

[English](UPGRADE.md) | Bahasa Indonesia

## Dari 1.x ke 2.0

```bash
composer require jund-fauz/eloquent-kompact:^2.0
```

### Kebutuhan

Package ini sekarang juga membutuhkan extension `mbstring`, selain `bcmath`.

### Config

Config punya key baru `path.math` (`App\Logic`), namespace default [`make:kompact-math`](README.id.md#math). Kalau kamu mem-publish config dan mempertahankan key `path`, tambahkan di sana, karena hanya key teratas yang digabung dengan default package:

```php
'path' => [
    'model' => namespace_concat('App', 'Models'),
    'database_enum' => namespace_concat('App', 'Enums'),
    'math' => namespace_concat('App', 'Logic'),
],
```

### Helper pindah ke namespace `Array` dan `String`

Helper array pindah ke `JundFauz\EloquentKompact\Array` dan helper string ke `JundFauz\EloquentKompact\String`, kebanyakan tanpa prefix `array_` atau `str_`. Ubah import dan pemanggilannya:

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

Helper lain, seperti `word_concat()`, `namespace_concat()`, `same_with()`, `cast_as()`, `collect()` dan `model_compact()`, tetap di `JundFauz\EloquentKompact`.

### Pengecekan lewat nama, bukan helper terpisah

Pengecekan `null`, numerik, array, direktori dan konstanta digantikan oleh [`is_not()`, `is_all()`, `is_any()`, `is_all_not()` dan `is_any_not()`](README.id.md#pengecekan), yang menerima pengecekan lewat namanya:

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

`is_not_null()` tetap ada.

Pengecekan-pengecekan ini meratakan array bersarang, juga di tempat yang dulu tidak diratakan helper lama: `is_one_of_them_null([null])` dulu `false`, `is_any('null', [null])` bernilai `true`, dan `is_all_numeric([1])` dulu `false`, `is_all('numeric', [1])` bernilai `true`.

### Helper angka digantikan `Math`

Helper bcmath digantikan oleh method static milik [`Math\Math`](README.id.md#math):

| 1.x | 2.0 |
|---|---|
| `plus($a, $b)` / `minus($a, $b)` / `multiply($a, $b)` / `divide($a, $b)` | `Math::plus($a, $b)` / `Math::minus($a, $b)` / `Math::multiply($a, $b)` / `Math::divide($a, $b)` |
| `higher_than($a, $b)` / `lower_than($a, $b)` | `Math::higherThan($a, $b)` / `Math::lowerThan($a, $b)` |
| `higher_than_or_same_with($a, $b)` / `lower_than_or_same_with($a, $b)` | `Math::higherThanOrEqualTo($a, $b)` / `Math::lowerThanOrEqualTo($a, $b)` |
| `num_same_with($a, $b)` | `Math::equalTo($a, $b)` |

Precision bukan lagi argumen. Tanpa precision, `Math` memakai config `decimal.default-precision` (`3`) seperti sebelumnya. Untuk precision lain, extend `Math`, atau buat class-nya dengan `php artisan make:kompact-math`:

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

Perbandingannya sekarang melempar `InvalidArgumentException` untuk nilai yang tidak numerik, padahal helper lama mengembalikan `false`: `higher_than('abc', 1)` dulu `false`, `Math::higherThan('abc', 1)` melempar exception. `null` ditolak dengan cara yang sama.

### Helper lain yang dihapus

| 1.x | 2.0 |
|---|---|
| `ternary($condition, $a, $b)` | `$condition ? $a : $b` |
| `elvis($value, $default)` | `$value ?: $default` |
| `null_coalescing($value, $default)` | `$value ?? $default`, atau `Array\get($value, $default)` untuk key array yang mungkin tidak ada |
| `is_positive_number($n)` / `is_natural_number($n)` | `$n > 0` |
| `is_negative_number($n)` | `$n < 0` |
| `is_whole_number($n)` | `$n >= 0` |
| `is_zero($value)` | `is_numeric($value) && (float) $value === 0.0` |

### Class yang dihapus

| 1.x | 2.0 |
|---|---|
| `Builder\BoolB` | Operator PHP bersama helper-helpernya, misalnya `same_with($user->role, 'admin') \|\| not_same_with($user->status, 'banned')` |
| `Helper\Arr::pullMany($array, ...$keys)` | `Array\pull($array, ...$keys)` |
| `Helper\Arr::isKeysDontExist($array, ...$keys)` | `array_all($keys, fn ($key) => ! array_key_exists($key, $array))` |
| `Column\Payload::allNullable` | `Column\ALL_NULLABLE` |
| `Column\Payload::build(...$payloads)` | `Array\merge(...$payloads)` |

### Class column builder yang di-generate

`kompact:column-builder` tidak lagi mengakhiri file yang di-generate dengan baris baru. Class-mu tetap berjalan; menjalankan command-nya lagi hanya menghapus baris baru itu.
