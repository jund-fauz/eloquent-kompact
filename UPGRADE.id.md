# Upgrade

[English](UPGRADE.md) | Bahasa Indonesia

## Dari 2.x ke 3.0

```bash
composer require jund-fauz/eloquent-kompact:^3.0
```

### Command Artisan berganti nama ke `make:*`

Command Artisan pembuat class sekarang memakai prefix `make:`:

| 2.x | 3.0 |
|---|---|
| `php artisan kompact:column-builder` | `php artisan make:column-builder` |
| `php artisan kompact:ide-helper` | `php artisan make:ide-helper` |
| `php artisan make:kompact-math` | `php artisan make:math` |

Class column builder yang dibuat di `2.x` tetap berjalan dan tetap diperbarui otomatis setelah migration.

### Binding container `Schema\Blueprint`

`EloquentKompactServiceProvider` sekarang melakukan binding `Illuminate\Database\Schema\Blueprint` ke [`JundFauz\EloquentKompact\Schema\Blueprint`](README.id.md#helper-migration). Migration yang sudah ada tetap berjalan tanpa perlu mengubah import `Blueprint`.

Kalau aplikasimu sebelumnya melakukan binding subclass `Blueprint` kustom sendiri di `AppServiceProvider` (atau lewat `DB::connection()->setSchemaGrammar()` / `Schema::blueprintResolver()`), ubah subclass tersebut agar meng-extend `JundFauz\EloquentKompact\Schema\Blueprint` supaya helper schema dan pemanggilan jamak milik Kompact tetap tersedia.

### Fungsi helper deprecated yang dihapus

Semua fungsi helper yang ditandai deprecated selama `2.x` telah dihapus:

| 2.x | 3.0 |
|---|---|
| `model_compact($model, ...$columns)` | `object_compact($model, ...$columns)` |
| `String\before_last($subject, $search)` | `String\str('before_last', $subject, $search)` |
| `String\lower($subject)` / `String\upper($subject)` | `String\str('lower', $subject)` / `String\str('upper', $subject)` |
| `not_in($needle, $haystack, $strict = false)` | `is_not('in', $needle, $haystack, $strict)` |
| `not_in_array($needle, $haystack, $strict = false)` | `is_not('in_array', $needle, $haystack, $strict)` |
| `is_all_not($check, ...$values)` / `is_any_not($check, ...$values)` | `is_all("not_$check", ...$values)` / `is_any("not_$check", ...$values)` |

### Method `AdditionalQueries` deprecated yang dihapus

Semua method `AdditionalQueries` yang ditandai deprecated selama `2.x` telah dihapus:

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
| `$model->notSameWithOther($other, ...$cols)` | `! $model->sameWithOther($other, ...$cols, ...['or'])` (saat minimal satu kolom diberikan) |
| `$model->notSameWithOtherOr($other, ...$cols)` | `! $model->sameWithOther($other, ...$cols)` (saat minimal satu kolom diberikan) |
| `$model->notSameWith($col, $val, $cast = null)` | `$model->not('same_with', $col, $val, $cast)` atau `not_same_with: [$val, $cast]` di `isAllOf()` |
| `$model->getManyVia($columnNames, ...$relationClasses)` | `$model->getVia($columnNames, ...$relationClasses)` |
| `$model->assignIfBlank($cols, $val = null)` / `$model->assignIfNull($cols, $val = null)` | `$model->assign($cols, $val, 'blank')` (atau `$model->assign($cols, 'blank')` untuk array) / `'null'` |
| `$model->assignIfBlankFrom($other, ...$cols)` / `$model->assignIfNullFrom($other, ...$cols)` | `$model->assignFromIf('blank', $other, ...$cols)` / `'null'` |

Catatan: `getVia($columnNames, ...$relationClasses)` mempertahankan key dari `$columnNames` alih-alih mengatur ulang index-nya; bungkus hasilnya dengan `array_values()` kalau membutuhkan list. Ingat juga bahwa `sameWithOther()` mengembalikan `false` tanpa kolom, jadi menegasikannya dengan `!` sama dengan `notSameWithOther()` / `notSameWithOtherOr()` saat minimal satu kolom diberikan.

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
| `is_zero($value)` | `same_with($value, 0.0, 'float')` |

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
