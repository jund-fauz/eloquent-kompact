# Eloquent Kompact

[![Tests](https://github.com/jund-fauz/eloquent-kompact/actions/workflows/tests.yml/badge.svg)](https://github.com/jund-fauz/eloquent-kompact/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/jund-fauz/eloquent-kompact)](https://packagist.org/packages/jund-fauz/eloquent-kompact)
[![PHP Version](https://img.shields.io/packagist/dependency-v/jund-fauz/eloquent-kompact/php)](https://packagist.org/packages/jund-fauz/eloquent-kompact)
[![License](https://img.shields.io/packagist/l/jund-fauz/eloquent-kompact)](LICENSE)

[English](README.md) | Bahasa Indonesia

Kumpulan alat otomatisasi & helper untuk meningkatkan pengalaman dan kenyamanan development saat membangun aplikasi dengan Eloquent ORM dari Laravel.

```bash
composer require jund-fauz/eloquent-kompact
```

Membutuhkan PHP 8.3+ dan Laravel 12 atau 13. Service provider didaftarkan lewat package discovery.

## Kenapa

Beri nama kolom sesuai [konvensi](#konvensi-penamaan-kolom), dan model membaca sisanya dari tabel saat runtime: daftar fillable, cast, dan relasi. Tidak ada yang di-generate ke file model, jadi tidak ada yang perlu diselaraskan setelah migration.

Sebelumnya, dengan model Eloquent biasa:

```php
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = ['customer_user_id', 'status_enum', 'weight_gram', 'paid_is', 'note'];

    protected $casts = [
        'status_enum' => OrderStatus::class,
        'weight_gram' => 'decimal:7',
        'paid_is' => 'boolean',
    ];

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }
}
```

Sesudahnya, dengan tabel `orders` yang sama:

```php
use JundFauz\EloquentKompact\Model\KompactModel;

class Order extends KompactModel
{
    protected static bool $useSoftDelete = false;
}

$order->customerUser;           // belongsTo(User::class, 'customer_user_id')
$order->status_enum;            // App\Enums\OrderStatus
Order::create($request->only('customer_user_id', 'status_enum', 'weight_gram', 'paid_is', 'note'));
```

Lihat [AutoFillable](#autofillable), [AutoCast](#autocast) dan [AutoRelation](#autorelation) untuk detailnya, dan [IDE helper](#ide-helper) untuk autocomplete relasi dinamis.

- [Versi](#versi)
- [Konfigurasi](#konfigurasi)
- [Konvensi penamaan kolom](#konvensi-penamaan-kolom)
- [KompactModel](#kompactmodel)
- [IDE helper](#ide-helper)
- [Helper migration](#helper-migration)
- [RawQuery](#rawquery)
- [Str](#str)
- [BoolB](#boolb)
- [Regex](#regex)
- [Fungsi helper](#fungsi-helper)
- [Helper lain](#helper-lain)
- [Pengembangan dengan bantuan AI](#pengembangan-dengan-bantuan-ai)

## Versi

Package ini mengikuti [Semantic Versioning](https://semver.org) dan stabil sejak `1.0.0`: breaking change hanya datang di rilis major (`2.0.0`), fitur baru di rilis minor (`1.x.0`) dan perbaikan di rilis patch (`1.x.y`). Pasang dengan `^1.0`.

Class dan method yang ditandai `@internal` atau `@experimental` tidak termasuk dalam janji ini dan bisa berubah di rilis mana pun.

## Konfigurasi

Package ini jalan tanpa perlu mem-publish config-nya. Untuk mengubah default, publish config-nya:

```bash
php artisan vendor:publish --tag=eloquent-kompact-config
```

| Key | Default | Dipakai untuk |
|---|---|---|
| `path.model` | `App\Models` | Namespace tempat relasi dinamis dan `StaticH::getModel()` mencari model |
| `path.database_enum` | `App\Enums` | Namespace tempat `AutoCast` mencari enum |
| `column.custom_tokens` | `[]` | Token kolom milikmu sendiri, lihat [Token kustom](#token-kustom) |
| `fillable.exclude_column_by.exact` | `['id']` | Nama kolom yang dikeluarkan dari daftar fillable dinamis |
| `fillable.exclude_column_by.contain` | `[]` | Potongan nama kolom: kolom yang mengandung salah satunya dikeluarkan dari daftar fillable dinamis |
| `fillable.exclude_column_by.prefix` | `[]` | Awalan nama kolom: kolom yang diawali salah satunya dikeluarkan dari daftar fillable dinamis |
| `fillable.exclude_column_by.postfix` | `[]` | Akhiran nama kolom: kolom yang diakhiri salah satunya dikeluarkan dari daftar fillable dinamis |
| `fillable.exclude_column_by.token` | `[]` | Nama token (`'relation'`, `'enum'`, nama token kustom, …) yang kolomnya dikeluarkan dari daftar fillable dinamis |
| `fillable.exclude_column_by.regex` | `[]` | Regex kolom yang dikeluarkan dari daftar fillable dinamis |
| `fillable.include_explicitly` | `[]` | Kolom yang selalu masuk daftar fillable dinamis pada model yang tabelnya punya kolom itu, walaupun dikeluarkan oleh `exclude_column_by` |
| `morph_model_use` | `''` | `'table'` atau `'model'` untuk mendaftarkan morph map dari model-model di `path.model`, lihat [Morph map](#morph-map) |
| `ide-helper.auto_generate` | `env('KOMPACT_IDE_HELPER', false)` | Menjalankan `kompact:ide-helper` setelah `php artisan migrate` di environment local |

Namespace boleh ditulis dengan atau tanpa backslash di akhir (`App\Models` atau `App\Models\`).

> **Catatan:** config yang kamu publish digabung dengan default package **hanya pada key level teratas** (`mergeConfigFrom()` dari Laravel). Kalau kamu menyimpan key level teratas seperti `path`, simpan **semua** key di dalamnya, karena default package untuk key itu tidak diisi. Misalnya, config yang di-publish hanya dengan `'path' => ['model' => ...]` membuat `path.database_enum` kosong.

### Cache kolom

Kolom model dibaca dari database sekali lalu di-cache (file cache, ditambah memori untuk proses yang sedang berjalan). Kolom ini dipakai oleh daftar fillable dinamis, auto cast, dan relasi dinamis.

Cache dibersihkan otomatis setelah `php artisan migrate` (termasuk `migrate:fresh` dan `migrate:rollback`). Untuk membersihkannya manual, misalnya setelah mengubah schema tanpa migration:

```php
\JundFauz\EloquentKompact\Column\ColumnListing::flush();
\JundFauz\EloquentKompact\Model\KompactModel::flushKompactCache();
\JundFauz\EloquentKompact\Model\KompactPivot::flushKompactCache();
\JundFauz\EloquentKompact\Model\KompactUser::flushKompactCache();
```

Proses yang berjalan lama (queue worker, Octane) menyimpan salinan di memori sampai di-restart, jadi jalankan `php artisan queue:restart` / `php artisan octane:reload` setelah migrate di production.

## Konvensi penamaan kolom

Sebagian besar otomatisasi membaca **tipe kolom dari namanya**: sebuah token di akhir nama kolom, setelah delimiter (`_`). Token selalu berupa akhiran, jadi token di bagian lain nama (`user_identity`, `created_at_backup`) atau tanpa delimiter (`paid`) tidak dihitung.

| Token | Konstanta | Contoh | Auto cast |
|---|---|---|---|
| `id` | `ColumnCreator::Relation` | `author_user_id` | – (dipakai relasi dinamis) |
| `enum` | `ColumnCreator::Enum` | `status_enum` | Class enum, lihat [AutoCast](#autocast) |
| `array` | `ColumnCreator::Array` | `tags_array` | `array` |
| `is` | `ColumnCreator::Boolean` | `active_is` | `boolean` |
| `gram` | `ColumnCreator::Gram` | `weight_gram` | `decimal:7` |
| `percentage` | `ColumnCreator::Percentage` | `tax_percentage` | `decimal:2` |
| `pcs` | `ColumnCreator::Pcs` | `stock_pcs` | – |
| `at` | `ColumnCreator::Timestamp` | `paid_at` | – |
| `time` | `ColumnCreator::Time` | `start_time` | – |
| `dropdown` | `ColumnCreator::Dropdown` | `size_dropdown` | – |
| `type` | `ColumnCreator::Type` | `commentable_type` | – (kolom morph type) |

`JundFauz\EloquentKompact\Column\ColumnCreator` membuat dan membaca nama-nama ini, jadi migration tidak perlu menulis token secara manual:

```php
use JundFauz\EloquentKompact\Column\ColumnCreator;

ColumnCreator::relation('author_user');                 // author_user_id
ColumnCreator::enum('status');                          // status_enum
ColumnCreator::quantity();                              // ['quantity_gram', 'quantity_pcs']

ColumnCreator::getPlainColumnName('author_user_id');    // Str: author_user
ColumnCreator::getColumnType('author_user_id');         // id
ColumnCreator::getColumnType('author_user_id', true);   // _id, dengan delimiter
ColumnCreator::castOf(ColumnCreator::Gram);             // decimal:7
ColumnCreator::tokens();                                // Builder\Collection berisi semua token, yang terpanjang lebih dulu
```

### Token kustom

Tambahkan token milikmu di `column.custom_tokens`, sebagai `nama => token` atau `nama => [token, cast]`:

```php
'column' => [
    'custom_tokens' => [
        'kg' => 'kg',
        'per_unit' => ['pu', 'decimal:3'],
    ],
],
```

```php
ColumnCreator::kg('weight');              // weight_kg
ColumnCreator::perUnit('price');          // price_pu
ColumnCreator::getColumnType('price_pu'); // pu
ColumnCreator::castOf('pu');              // decimal:3, jadi AutoCast meng-cast price_pu
ColumnCheck::isPerUnit('price_pu');       // true
```

Nama ditulis dalam snake case dan dipanggil dalam camel case. Token kustom dicek sebelum token bawaan, jadi nama atau token kustom yang juga ada di bawaan akan menang. Cache `ColumnCreator` dibangun ulang saat config berubah; cast sebuah model di-cache oleh `AutoCast` sampai `flushKompactCache()`, lihat [Cache kolom](#cache-kolom).

### Delimiter

Delimiter antara nama kolom dan token adalah `_`. Ubah sekali saja, sebelum model mana pun dipakai, misalnya di `register()` sebuah service provider:

```php
ColumnCreator::configureDelimiter('-');

ColumnCreator::relation('user');         // user-id
ColumnCreator::getColumnType('user-id'); // id
ColumnCreator::getDelimiter();           // -
```

Hanya token yang mendapat delimiter: namanya sendiri, termasuk prefix, tetap snake case, misalnya `Convert::modelToColumn(User::class, 'author')` → `author_user-id`.

### Column builder

Column builder adalah class per token yang static method-nya memberikan nama kolom, jadi nama kolom ditulis sekali dan bisa ditemukan oleh IDE: `Gram::netWeight()` → `net_weight_gram`. Generate dari kolom-kolom database-mu:

```bash
php artisan kompact:column-builder -o "App\Constant\Column"
```

Tanpa `-o` (`--output`), command akan menanyakan namespace-nya, di dalam `app/`. Command ini menulis satu class untuk setiap token kecuali `id` (`ArrayC`, `Boolean`, `Enum`, `Gram`, `Pcs`, …, dan satu per token kustom, misalnya `PerUnit`), dengan `@method` untuk setiap kolom token itu di database, serta class `Morph` dengan konstanta untuk setiap morph (kolom `*_type` beserta `*_id`-nya):

```php
namespace App\Constant\Column;

use JundFauz\EloquentKompact\Column\Builder\Concerns\ColumnBuilder;

/**
 * Generated by `php artisan kompact:column-builder`, run it again after changing the database.
 *
 * @method static string netWeight()
 */
class Gram extends ColumnBuilder {}

Gram::netWeight();         // net_weight_gram
Morph::sourceableId();     // sourceable_id
Morph::sourceableModel();  // sourceable_type
```

Token yang merupakan keyword PHP mendapat `C` di akhir nama class-nya, seperti `ArrayC`, dan `ColumnBuilder` membuangnya lagi untuk menemukan token. Setelah command ini pernah dijalankan, command ini berjalan lagi sendiri setelah setiap `php artisan migrate` di environment `local`, untuk setiap namespace yang berisi class hasil generate-nya (dicari dari docblock-nya di dalam `app/`). Command ini juga bisa dijalankan ulang manual: class yang di-generate-nya diperbarui ke kolom terbaru, dan konstanta yang di-rename di `Morph` (`public const string source = 'sourceable';`, lalu `Morph::sourceId()`) tetap dipertahankan. Class dengan nama yang sama yang tidak di-generate oleh command ini akan dilewati, kecuali diberi `--force`.

Command ini tidak membuat class untuk token `id`. Tulis sendiri dengan `Column\Builder\Concerns\RelationBuilder`, dengan daftar relasi di `$relation`: item tanpa key dipanggil dengan namanya sendiri, dan item dengan key dipanggil dengan key-nya:

```php
use JundFauz\EloquentKompact\Column\Builder\Concerns\RelationBuilder;

class Relation extends RelationBuilder
{
    protected static array $relation = ['customer_user', 'author' => 'author_user'];
}

Relation::customer_user(); // customer_user_id
Relation::author();        // author_user_id
```

Nama method-nya persis item atau key yang ditulis, dan nama lain akan melempar `BadMethodCallException`.

`ColumnCheck` memberi tahu apakah sebuah kolom (nama atau definisi kolom dengan `name`) diakhiri sebuah token. Hanya token terakhir yang dihitung:

```php
use JundFauz\EloquentKompact\Column\ColumnCheck;

ColumnCheck::isRelation('author_user_id'); // true
ColumnCheck::isEnum($table->string('status_enum')); // true
ColumnCheck::isRelation('user_identity'); // false
ColumnCheck::isRelation('user_id_enum'); // false, ini kolom enum
```

## KompactModel

Extend `JundFauz\EloquentKompact\Model\KompactModel` alih-alih `Model` milik Eloquent untuk mendapatkan semua trait di bawah sekaligus. `KompactUser` melakukan hal yang sama untuk model user, di atas `Illuminate\Foundation\Auth\User` milik Laravel. Keduanya hanya memakai trait `Kompact`, yang juga bisa dipakai model dengan parent class lain; model seperti itu harus mengisi `$collectionClass` dan `$builder` sendiri untuk mendapatkan [`Model\Collection`](#helper-lain) dan [`Model\Builder`](#builder). `KompactUser` bersifat abstract. `KompactPivot` melakukan hal yang sama untuk model pivot, dengan `AdditionalQueries`, `AutoFillable`, `AutoRelation`, `StaticH`, `Validation` dan soft delete.

```php
use JundFauz\EloquentKompact\Model\KompactModel;

class Post extends KompactModel
{
    // Soft delete aktif secara default
    protected static bool $useSoftDelete = false;
}

class User extends KompactUser
{
    //
}
```

Dengan `$useSoftDelete = false`, `delete()` menghapus baris, jadi tabel tidak butuh kolom `deleted_at`.

Setiap trait juga bisa dipakai sendiri-sendiri di model.

Hasil query model memakai [`Model\Collection`](#helper-lain), kecuali model punya `#[CollectedBy]` atau `$collectionClass` sendiri. Query memakai [`Model\Builder`](#builder), kecuali model punya `#[UseEloquentBuilder]` atau `$builder` sendiri.

### AutoFillable

`getFillable()` mengembalikan semua kolom tabel, digabung dengan `$fillable` milik model. Tidak perlu lagi menyelaraskan array `$fillable` dengan migration. Yang dikeluarkan adalah kolom timestamp (`ColumnCreator::Timestamp`, misalnya `paid_at`) dan kolom dari `fillable.exclude_column_by`:

```php
'fillable' => [
    'exclude_column_by' => [
        'exact' => ['id', 'remember_token'],
        'contain' => ['password'],                // password, password_hash, old_password, …
        'prefix' => ['internal_'],                // internal_note, internal_code, …
        'postfix' => ['_hash'],                   // password_hash, token_hash, …
        'token' => ['relation'],                  // semua kolom yang berakhiran _id
        'regex' => [Regex::create('secret_')->startsWith()],
    ],
],
```

`fillable.include_explicitly` berisi kolom yang fillable di setiap model yang tabelnya punya kolom itu, walaupun dikeluarkan oleh `exclude_column_by` atau aturan timestamp, misalnya `'prefix' => ['shrinkage']` dengan `'include_explicitly' => ['shrinkage_threshold_percentage']`. Kolom di daftar ini yang tidak ada di sebuah tabel tidak ditambahkan ke model itu.

Key `exclude_column_by` yang tidak ada berarti tidak ada yang dikeluarkan untuk key itu, jadi config yang di-publish sebelum `1.0.0`, dengan `excluded_columns_by_regex`, hanya mengeluarkan kolom timestamp: `id` menjadi fillable. Ganti nama key itu di sana.

Model yang juga harus mengisi kolom timestamp-nya cukup mengatur:

```php
protected static bool $includeTimestampColumn = true;
```

> **Catatan:** ini termasuk kolom seperti `password` dan `remember_token` di `KompactUser`. Tambahkan kolom yang tidak boleh di-mass assign ke `fillable.exclude_column_by`, dan jangan pernah memberikan input yang belum difilter ke `create()` atau `fill()`.

### Validasi

`Model::rules($record = null)` mengembalikan aturan validasi untuk setiap kolom fillable, jadi kolom tidak perlu dideklarasikan untuk mendapatkannya. Kolom dengan token mendapat aturan default:

| Token | Aturan default |
|---|---|
| `id` | `integer` |
| `gram` | `numeric`, `min:0.001`, `max:` `gramMaxRule()` (`99999.9999999`), `decimal:0,3` |
| `pcs` | `integer`, `min:0`, `max:` `pcsMaxRule()` (`4294967295`) |
| `percentage` | `numeric`, `min:0`, `max:100` |
| `is` | `boolean` |

Kolom dengan `string` dan tanpa `max` juga mendapat `max:255`.

Tambahkan aturan di `defineRules()`, sebuah instance method, di model dan di parent class mana pun. Semua `defineRules()` dalam hierarki digabung, tanpa memanggil `parent::`: model lebih dulu, lalu parent-nya, lalu default. Sebuah aturan hanya ditambahkan kalau layer yang lebih spesifik belum punya aturan dengan nama yang sama, jadi `max:15` dari model menang atas `max:255` dari default, sementara aturan lainnya saling menambah:

```php
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends KompactModel
{
    protected function defineRules(?Model $record = null): array
    {
        return [
            'name' => ['string', 'min:3'],
            'phone' => ['nullable', 'string', static::uniqueRule('phone', $record)],
        ];
    }
}

class Customer extends BaseModel
{
    protected static function pcsMaxRule(): int
    {
        return 1_000;
    }

    protected function defineRules(?Model $record = null): array
    {
        return [
            'name' => 'required|string|max:50',
            'password' => ['required', 'confirmed'],
        ];
    }
}

Customer::rules($customer);
// ['name' => ['required', 'string', 'max:50', 'min:3'], 'phone' => [..., Unique], 'password' => [...], ...]
```

Aturan dari parent class hanya diterapkan ke kolom-kolom model, jadi `phone` dikeluarkan dari model yang tidak punya kolom `phone`. Aturan yang dideklarasikan oleh model itu sendiri selalu diterapkan, termasuk untuk field yang bukan kolom, seperti `password`. `uniqueRule($column, $record)` adalah `Rule::unique()` pada tabel model, dengan mengabaikan `$record`. Untuk mengubah default, override `defaultRules($column, $record)`, `gramMaxRule()` atau `pcsMaxRule()`.

Aturan sebuah kolom, di `defineRules()` atau `defaultRules()`, juga bisa dibangun dengan `JundFauz\EloquentKompact\Column\Validation`:

```php
use JundFauz\EloquentKompact\Column\Validation as Rules;

protected function defineRules(?Model $record = null): array
{
    return [
        'price' => Rules::init()->numeric()->min(0)->max(1_000)->decimal(0, 2), // ['numeric', 'min:0', 'max:1000', 'decimal:0,2']
        'code' => Rules::init()->regex(Regex::create('[A-Z]+')->exact()),       // ['regex:/^[A-Z]+$/']
        'email' => Rules::init()->required()->unique('email', Customer::class, $record), // ['required', Unique]
    ];
}
```

Isinya `required()`, `integer()`, `numeric()`, `boolean()`, `min($number)`, `max($number)`, `decimal($min = null, $max = null)`, `regex($regex)` dan `unique($column = null, $model = null, $record = null)`, dan `build()` mengembalikan aturannya sebagai array. `unique()` dengan kolom dan model adalah `uniqueRule($column, $record)` dari model itu; tanpa salah satunya, yang ditambahkan adalah aturan `'unique'` polos. Setiap method mengembalikan instance baru, jadi satu base bisa dipakai ulang. `decimal()` butuh setidaknya satu argumen, atau akan melempar `BadMethodCallException`.

### AutoCast

`getCasts()` menambahkan cast untuk setiap kolom fillable dengan token yang punya cast (lihat [tabel di atas](#konvensi-penamaan-kolom)). Cast yang dideklarasikan di model menang atas cast otomatis.

Kolom `_enum` di-cast ke enum `{nama tabel tunggal dalam PascalCase}{kolom tanpa token dalam PascalCase}` di dalam `path.database_enum`, atau ke `string` kalau enum itu tidak ada:

```php
// orders.status_enum → App\Enums\OrderStatus
```

### AutoRelation

Relasi diselesaikan dari nama relasi dan kolom-kolomnya, tanpa mendeklarasikan method:

| Relasi | Diselesaikan sebagai | Syarat |
|---|---|---|
| `$post->authorUser` | `belongsTo(User::class, 'author_user_id')` | ada model `User`; nama dicoba sebagai `AuthorUser`, lalu `User` |
| `$user->posts` | `hasMany(Post::class, 'user_id')` | `posts.user_id` |
| `$user->authorPosts` | `hasMany(Post::class, 'author_user_id')` | `posts.author_user_id` |
| `$post->comments` | `morphMany(Comment::class, 'commentable')` | tidak ada `comments.post_id`, `Comment` punya satu public method yang mengembalikan `MorphTo` dan kolom `commentable_type` |

Nama jamak tanpa kolom atau morph yang cocok akan melempar `BadMethodCallException`. Relasi yang dideklarasikan sebagai method selalu menang. Relasi dinamis menghormati `Model::preventLazyLoading()` dan bisa di-eager load (`Post::with('authorUser')`).

Untuk mendapatkan autocomplete relasi-relasi ini, lihat [IDE helper](#ide-helper).

### Morph map

Atur `morph_model_use` untuk menyimpan alias pendek di kolom `*_type` relasi morph, alih-alih nama class lengkap. Package lalu mendaftarkan `Relation::enforceMorphMap()` milik Laravel dengan setiap model yang langsung berada di `path.model` (bukan di subdirektorinya), tanpa class abstract dan class yang bukan model:

| `morph_model_use` | Alias | Contoh |
|---|---|---|
| `'table'` | Tabel model | `blog_posts` → `App\Models\BlogPost` |
| `'model'` | Nama class tanpa namespace | `BlogPost` → `App\Models\BlogPost` |

Nilai lain akan melempar `Exception`, begitu juga `path.model` yang tidak punya direktori. Map ini di-enforce, jadi model di luar `path.model` yang dipakai di relasi morph akan melempar `ClassMorphViolationException` dari Laravel; tambahkan dengan `Relation::morphMap()` di service provider. Baris yang tersimpan sebelumnya tetap berisi nama class lengkap di kolom `*_type`: baris itu tetap bisa dimuat, tapi `whereMorphType()` dan `isMorphModelSame()` mencari alias-nya, jadi perbarui baris-baris itu saat mengaktifkan map.

### AdditionalQueries

```php
// Salin baris relasi ke model lain, hanya dengan kolom yang diberikan
$order->copyRelationsTo($newOrder, ['items' => ['product_id', 'qty_pcs']]);

// Apakah atribut morph type menunjuk ke class tersebut, memakai morph alias-nya kalau ada
$comment->isMorphModelSame('commentable', Post::class); // true

// Atribut pertama yang tidak null
$user->getOr('nickname', 'name');

// Beberapa atribut, sesuai urutan yang diberikan (yang tidak ada bernilai null)
$user->getMany('name', 'email'); // ['Ana', 'ana@example.com']

// Atribut dari relasi bersarang tanpa cek null, dengan relasi diberikan sebagai class model
$comment->getVia('name', Post::class, User::class);                // $comment->post?->user?->name
$comment->getManyVia(['name', 'email'], Post::class, User::class); // [$comment->post?->user?->name, $comment->post?->user?->email]
$comment->getVia('name', ['latest' => Post::class]);               // $comment->latestPost?->name

// Model terkait dari relasi bersarang, tanpa kolom
$comment->getRelVia(Post::class, User::class);                     // $comment->post?->user

// [kolom => nilai] untuk kolom yang diberikan, setelah casting (yang tidak ada bernilai null)
$user->compact('name', 'email'); // ['name' => 'Ana', 'email' => 'ana@example.com']
$user->compact('name', ['mail' => 'email']); // ['name' => 'Ana', 'mail' => 'ana@example.com'], juga compact('name', mail: 'email')

// update() dengan beberapa array digabung, key yang belakangan menang
$order->updateMany($data, $order->compact('note'), ['status' => 'paid']);

// Mengisi atribut, tanpa menyimpan
$order->assign('status', 'paid');
$order->assign(['status' => 'paid', 'note' => '-']);
$order->assignFrom($otherOrder, 'tenant_id', 'branch_id');
$order->assignIfBlank('note', '-');
$order->assignIfBlank(['note' => '-', 'status' => 'new']);
$order->assignIfBlankFrom($otherOrder, 'tenant_id', 'branch_id');
$order->assignIfNull('note', '-');
$order->assignIfNull(['note' => '-', 'status' => 'new']);
$order->assignIfNullFrom($otherOrder, 'tenant_id', 'branch_id');

// Apakah atribut blank, filled atau null
$order->isBlank('note');
$order->isFilled('note');
$order->isNull('note');
$order->isNotNull('note');

// Perbandingan ketat atribut, setelah casting
$user->sameWith('role', 'admin');
$user->notSameWith('role', 'admin');
$order->sameWith('qty', 5, 'integer');                           // dengan cast lain, hanya untuk perbandingan ini
$user->sameWithOther($otherUser, 'tenant_id', 'branch_id');      // semua kolom sama
$user->sameWithOtherOr($otherUser, 'tenant_id', 'branch_id');    // setidaknya satu kolom sama
$user->notSameWithOther($otherUser, 'tenant_id', 'branch_id');   // semua kolom berbeda
$user->notSameWithOtherOr($otherUser, 'tenant_id', 'branch_id'); // setidaknya satu kolom berbeda
$user->match(['role' => ['admin', 'editor'], 'active_is' => true]);

// Perbandingan yang sama, untuk kolom yang berubah dan belum disimpan
$user->sameWithAndDirty('role', 'admin');
$user->notSameWithAndDirty('role', 'admin');
$user->sameWithOtherAndDirty($otherUser, 'tenant_id', 'branch_id');      // semua kolom sama dan dirty
$user->sameWithOtherOrAndDirty($otherUser, 'tenant_id', 'branch_id');    // setidaknya satu kolom sama dan dirty
$user->notSameWithOtherAndDirty($otherUser, 'tenant_id', 'branch_id');   // semua kolom berbeda dan dirty
$user->notSameWithOtherOrAndDirty($otherUser, 'tenant_id', 'branch_id'); // setidaknya satu kolom berbeda dan dirty
$user->matchAndDirty(['role' => ['admin', 'editor'], 'active_is' => true]);
$comment->isMorphModelSameAndDirty('commentable', Post::class);
$order->isBlankAndDirty('note');
$order->isFilledAndDirty('note');
$order->isNullAndDirty('note');
$order->isNotNullAndDirty('note');

// Apakah kolom diubah oleh save terakhir
$order->wereChanged('status', 'note');    // semua kolom diubah
$order->wereNotChanged('status', 'note'); // semua kolom tidak diubah
$order->wereChanged();                    // ada kolom yang diubah
$order->wereNotChanged();                 // tidak ada kolom yang diubah

// id dari baris acak, atau null kalau tidak ada
Order::randomId();
Order::randomId('status', 'paid'); // argumen untuk where()
```

`assignIfBlank()`, `assignIfBlankFrom()` dan `isBlank()` memakai `blank()` dari Laravel, jadi `null`, string kosong (juga `'   '`) dan array kosong dihitung blank, sedangkan `0`, `'0'` dan `false` tidak. `isFilled()` memakai `filled()`, kebalikannya. `assignIfNull()` dan `assignIfNullFrom()` hanya mengganti `null`.

`match()` dan keluarga `sameWithOther()` mengembalikan `false` kalau tidak ada kondisi atau kolom yang diberikan. `notSameWithOther()` bukan kebalikan `sameWithOther()`: kebalikannya adalah `notSameWithOtherOr()`. Object seperti tanggal Carbon dari kolom `datetime` dibandingkan berdasarkan identitas, jadi dua model tidak pernah punya tanggal yang sama. Nilai array cocok kalau atributnya sama dengan salah satu item-nya, kecuali atributnya sendiri berupa array.

`sameWith()`, `notSameWith()`, `sameWithAndDirty()` dan `notSameWithAndDirty()` menerima cast Laravel sebagai argumen ketiga (`'integer'`, `'decimal:2'`, `'string'`, class enum, …), yang dipakai menggantikan cast kolom hanya untuk perbandingan itu; cast model tetap seperti semula. Accessor kolom tetap menang atasnya, dan cast tanggal tetap menghasilkan object yang dibandingkan berdasarkan identitas.

Method `AndDirty` memakai `isDirty()` dari Eloquent pada setiap kolom, jadi `sameWithOtherOrAndDirty()` butuh satu kolom yang dirty sekaligus sama, sedangkan `sameWithOtherAndDirty()` dan `matchAndDirty()` bernilai `false` kalau salah satu kolom tidak dirty. Setiap atribut yang diisi pada model baru itu dirty. Kolom yang berubah masih dirty di event `saving`, `saved`, `updating`, `updated`, `creating` dan `created`, dan tidak ada yang dirty setelah `save()` selesai.

`wereChanged()` dan `wereNotChanged()` memakai `wasChanged()` dari Eloquent pada setiap kolom. Berbeda dengan `wasChanged('a', 'b')` yang butuh satu kolom berubah, `wereChanged('a', 'b')` butuh semuanya. `wereNotChanged()` bukan kebalikan `wereChanged()` saat sebagian kolom berubah dan sebagian tidak: keduanya `false`. Seperti `wasChanged()`, keduanya tidak melihat perubahan tepat setelah `create()`, dan `save()` tanpa perubahan tetap menyimpan perubahan dari update terakhir.

`updateMany()` hanya menerima array dan mengabaikan yang kosong. Kalau salah satunya berupa list, method ini tidak mengubah apa pun dan mengembalikan `false` tanpa error, jadi cek hasilnya kalau datanya bisa berupa list. Seperti `update()`, method ini hanya mengisi kolom fillable dan mengembalikan `false` pada model yang belum disimpan.

`randomId()` membaca kolom `id`, jadi tidak bisa dipakai pada model dengan primary key lain.

### Builder

`KompactModel`, `KompactUser` dan `KompactPivot` memakai `JundFauz\EloquentKompact\Model\Builder`, yang meng-extend `Builder` milik Eloquent:

```php
// where() pada beberapa kolom, dengan nilai diambil dari model lain
Order::whereSameWith($otherOrder, 'tenant_id', 'branch_id')->get();

// where() pada kolom morph type, memakai morph alias class-nya kalau ada
Comment::whereMorphType('commentable', Post::class)->get(); // where commentable_type = 'App\Models\Post'
Comment::whereMorphTypeNot('commentable', Post::class)->get(); // where not commentable_type = 'App\Models\Post'

// whereHas() dengan whereIn() pada kolom relasi
Order::whereInRelation('items', 'status', ['paid', 'sent'])->get();

// Builder\Collection alih-alih Collection milik Laravel
Order::pluck('status');
```

> **Catatan:** model yang hanya memakai trait harus mengisi `protected static string $builder = Builder::class;` sendiri, begitu juga model terkait saat method ini dipanggil pada relasi. Tanpa itu **tidak ada error**: Laravel membaca pemanggilannya sebagai dynamic where, jadi `whereSameWith($other, 'id')` menjadi `where same_with = ...`.

Seperti scope, method-method ini mengelompokkan kondisi sebelumnya kalau ada `or` di dalamnya, jadi `Order::where('a', 1)->orWhere('b', 1)->whereMorphType(...)` menghasilkan `where (a = ? or b = ?) and ...`. Kelompok itu berisi semua kondisi sebelumnya, dan `orWhere()` sesudahnya tidak dikelompokkan, jadi tetap pakai closure untuk mengelompokkan kondisi sendiri.

`whereInRelation()` memakai kolom apa adanya, jadi pada relasi `belongsToMany`, kolom yang juga ada di tabel pivot harus ditulis dengan tabelnya (`'tags.id'`), atau query gagal dengan error kolom ambigu. List nilai yang kosong tidak menemukan apa pun.

Method-method ini bukan scope, jadi scope dengan nama yang sama di model tidak pernah dipanggil, `hasNamedScope()` mengembalikan `false` untuknya, dan method ini tidak bisa diberikan ke `scopes()`.

### StaticH

```php
Post::tableName();             // posts
Post::morphClass();            // App\Models\Post, atau alias-nya di Relation::morphMap()
BlogPost::baseName();          // BlogPost

// Nama relasi model
BlogPost::singular();          // blogPost
BlogPost::plural();            // blogPosts
BlogPost::singular('latest');  // latestBlogPost
BlogPost::plural('latest');    // latestBlogPosts

// Kolom foreign key model
BlogPost::foreign();           // blog_post_id
BlogPost::foreign('from');     // from_blog_post_id

// Kolom beserta tabelnya, untuk join atau chain RawQuery
User::foreignFrom(BlogPost::class);           // "blog_posts"."user_id"
User::foreignFrom(BlogPost::class, 'author'); // "blog_posts"."author_user_id"
BlogPost::qualify('title');                   // "blog_posts"."title"
BlogPost::qualify('qty')->zeroIfNull()->sum()->as('total'); // SUM(COALESCE("blog_posts"."qty", 0)) AS total

User::leftJoin('blog_posts', 'users.id', '=', User::foreignFrom(BlogPost::class));

// Path yang diawali model ini, untuk dipakai di model lain yang punya relasi ke model ini
User::relationTo('roles');                // user.roles
User::relationTo(BlogPost::class);        // user.blogPost
User::columnTo('name', 'roles');          // user.roles.name
User::columnTo('title', BlogPost::class); // user.blogPost.title
User::relationTo(['latest' => BlogPost::class]); // user.latestBlogPost

Order::with(User::relationTo('roles'));   // order → user → roles

// Di migration: model dari tabel yang sedang dibuat, atau null
Post::getModel($table);        // App\Models\Post
```

`singular()`, `plural()`, `foreign()`, `relationTo()` dan `columnTo()` memakai nama class tempat method dipanggil, jadi subclass memberikan namanya sendiri, dan morph alias tidak dipakai. Method-method ini tidak membuat model atau membaca database, jadi aman dipakai di migration. `foreign()` selalu diakhiri `_id`, bahkan untuk model dengan primary key lain, berbeda dengan `getForeignKey()` milik Eloquent. Dalam path, class menjadi nama tunggal dan string lain dipakai apa adanya, jadi tulis relasi jamak sebagai string (`'roles'`). Relasi dengan prefix ditulis sebagai `[prefix => class model]`, lihat [`Convert`](#helper-migration).

`foreignFrom()` dan `qualify()` mengembalikan [`RawQuery`](#rawquery) yang sudah di-quote oleh grammar koneksi model, beserta table prefix-nya, jadi tetap benar di join dan sepanjang chain. Keduanya memakai `$table` model, dan kolom yang sudah punya tabel (`other.name`) tetap memakai tabel itu. Keduanya membuat model, tanpa membaca database. Kolom kosong menghasilkan SQL yang tidak valid. `pluck()` mencari property dengan tanda kutip di namanya dan gagal, jadi tulis kolomnya sebagai string di sana: `pluck('blog_posts.title')`.

## IDE helper

Relasi dinamis bukan method, jadi IDE tidak bisa melihatnya. Generate file helper:

```bash
php artisan kompact:ide-helper
```

Command ini menulis `_ide_helper_kompact.php` (`@property-read` dan `@method` untuk setiap relasi dinamis dari model yang langsung berada di `path.model` dan memakai `AutoRelation`) lalu mendaftarkannya sebagai class mixin di `ide.json` untuk Laravel Idea. Setiap mixin adalah class berawalan `_EK_` di dalam `EloquentKompact\IdeHelper`, misalnya `EloquentKompact\IdeHelper\App\Models\_EK_User` untuk `App\Models\User`, jadi tidak pernah muncul sebagai class `User` kedua. Entri `classMixins` lain di `ide.json` tetap dipertahankan.

Kalau model terkait memakai builder-nya sendiri, seperti [`Model\Builder`](#builder) pada `KompactModel`, `@method` relasinya juga mengembalikan builder itu, jadi method-method-nya disarankan setelah `$post->comments()`.

Atur `KOMPACT_IDE_HELPER=true` di `.env` untuk menjalankannya otomatis setelah setiap `php artisan migrate` di environment `local`. Kemungkinan besar kamu ingin menambahkan `_ide_helper_kompact.php` ke `.gitignore`.

## Helper migration

`Config::apply()` menerapkan column modifier ke banyak kolom sekaligus. Key payload-nya, dengan urutan ini:

- `all`: semua kolom;
- nama kolom: kolom itu;
- regex: semua kolom yang cocok;
- `rest`: semua kolom yang tidak cocok dengan nama kolom atau regex mana pun.

Payload adalah nama modifier, list modifier, atau `[modifier => argumen]`:

```php
use JundFauz\EloquentKompact\Column\Config;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Column\Payload;

Schema::create('posts', function (Blueprint $table) {
    $table->id();

    Config::apply([
        $table->string('title'),
        $table->string(ColumnCreator::enum('status')),
        $table->foreignId(ColumnCreator::relation('author_user')),
        $table->text('body'),
    ], Payload::build(
        ['title' => ['unique', 'comment' => 'Shown in the list']],
        ['/_id$/' => ['index']],
        ['rest' => 'nullable'],
    ));
});
```

`Payload::allNullable` adalah `['all' => 'nullable']` yang siap pakai.

`Convert` mengubah nama dan nilai dari satu bentuk ke bentuk lain:

```php
use JundFauz\EloquentKompact\Column\Convert;

Convert::modelToColumn(User::class);                  // user_id
Convert::modelToColumn(User::class, 'author');        // author_user_id
Convert::modelToRelation(User::class);                // user
Convert::modelToRelation(BlogPost::class, true, true); // blog_posts
Convert::modelToRelation(['latest' => BlogPost::class]); // latestBlogPost
Convert::modelToRelation(['latest' => BlogPost::class], true, true); // latest_blog_posts
Convert::booleanToString('active_is');                // ['Not active', 'Active'], diterjemahkan
Convert::dayToSecond(1);                              // 86400
Convert::hourToSecond(1);                             // 3600
```

Prefix hanya ditulis sebagai array dengan satu key, yaitu prefix-nya, dan satu nilai, yaitu class model. Bentuk lain akan melempar `InvalidArgumentException`.

## RawQuery

`JundFauz\EloquentKompact\Query\RawQuery` membangun ekspresi SQL mentah (untuk `selectRaw`, `whereRaw`, `orderByRaw`, dll.) dengan chain fluent, kompatibel dengan MySQL, MariaDB, PostgreSQL & SQLite.

Mulai dengan `create()`, rangkai operasinya sesuai urutan baca, lalu panggil `build()` untuk mendapatkan string SQL-nya.

```php
use JundFauz\EloquentKompact\Query\RawQuery;

RawQuery::create('price')->zeroIfNull()->sum()->as('total_price')->build();
// SUM(COALESCE(price, 0)) AS total_price
```

### Pemakaian di Laravel

`build()` tidak wajib saat memberikan `RawQuery` ke method raw Laravel (`selectRaw`, `whereRaw`, `orWhereRaw`, `havingRaw`, `groupByRaw`, `orderByRaw`, `DB::raw`). `RawQuery` adalah query `Expression`, jadi Laravel mengubahnya menjadi SQL saat meng-compile query. `havingRaw()` memakainya sebagai string, yang menghasilkan SQL yang sama karena `RawQuery` juga `Stringable`:

```php
Order::selectRaw(RawQuery::create('price')->zeroIfNull()->sum()->as('total_price'))->first();
```

Method yang biasanya menerima nama kolom atau tabel (`select`, `orderBy`, `groupBy`, `DB::table`, `from`, …) juga memakainya sebagai SQL mentah, jadi tidak di-quote dan tidak mendapat table prefix koneksi:

```php
DB::table(RawQuery::create('(SELECT customer, SUM(price) AS total FROM orders GROUP BY customer) AS t'))->get();
// select * from (SELECT customer, SUM(price) AS total FROM orders GROUP BY customer) AS t
```

Panggil `build()` saat kamu butuh SQL-nya sebagai `string` sungguhan, misalnya untuk disimpan, dibandingkan, atau diberikan ke parameter bertipe `string` dari file dengan `declare(strict_types=1)`.

### Cara kerja chain

Ekspresi yang diberikan ke `create()` menjadi **subjek**. Setiap method dalam chain membungkus atau memperluas subjek saat ini, dan hasilnya menjadi subjek baru:

```php
use JundFauz\EloquentKompact\Query\Operator;

RawQuery::create('stock')             // stock
    ->compare('0', Operator::MoreThan) // stock > 0
    ->if(RawQuery::string('available'), RawQuery::string('empty'))
    ->as('status')
    ->build();
// CASE WHEN stock > 0 THEN 'available' ELSE 'empty' END AS status
```

Method dengan beberapa operand (`plus`, `minus`, `multiply`, `divide`, `concat`, `isZeroOrNull`) memakai subjek sebagai operand pertama. Kalau `create()` dipanggil tanpa ekspresi, operand-nya dipakai apa adanya:

```php
RawQuery::create('price')->plus('tax', 'shipping')->build(); // price + tax + shipping
RawQuery::create()->plus('tax', 'shipping')->build();        // tax + shipping
```

Setiap method dalam chain mengembalikan `RawQuery` **baru** dan tidak mengubah yang asli, jadi satu base bisa dipakai ulang:

```php
$price = RawQuery::create('price')->zeroIfNull();

$price->sum()->build(); // SUM(COALESCE(price, 0))
$price->max()->build(); // MAX(COALESCE(price, 0))
$price->build();        // COALESCE(price, 0)
```

### Nesting

Setiap argumen yang menerima ekspresi bisa diisi `string` atau `RawQuery` lain. `RawQuery` itu `Stringable`, jadi builder bersarang tidak perlu `build()`:

```php
RawQuery::create('price')
    ->multiply('qty')
    ->plus(RawQuery::create('shipping')->zeroIfNull())
    ->build();
// price * qty + COALESCE(shipping, 0)
```

Operator tidak menambahkan tanda kurung sendiri. Pakai `wrap()` kalau urutan operasi penting:

```php
RawQuery::create('price')->plus('tax')->wrap()->multiply('qty')->build();
// (price + tax) * qty
```

### Binding

`compare()` memakai `?` sebagai sisi kanan default-nya, jadi bisa dipakai dengan query binding:

```php
Order::whereRaw(
    RawQuery::create('total')->compare(operator: Operator::MoreThanOrSame),
    [100_000]
)->get();
// WHERE total >= ?
```

### Method chain

Di tabel ini, `x` adalah subjek saat ini.

| Method | Hasil |
| --- | --- |
| `zeroIfNull()` | `COALESCE(x, 0)` |
| `replaceIfNull($value)` | `NULLIF(x, value)` |
| `isNull()` | `x IS NULL` |
| `isZeroOrNull(...$columns)` | `x = 0 OR x IS NULL OR a = 0 OR a IS NULL ...` |
| `and($condition)` | `x AND condition`; rangkai lagi untuk kondisi tambahan |
| `wrap()` | `(x)` |
| `sum()` / `max()` / `count()` | `SUM(x)` / `MAX(x)` / `COUNT(x)` |
| `plus(...$columns)` | `x + a + b` |
| `minus(...$columns)` | `x - a - b` |
| `multiply(...$columns)` | `x * a * b` |
| `divide(...$columns)` | `x * 1.0 / NULLIF(a, 0) / NULLIF(b, 0)` |
| `if($trueValue, $falseValue)` | `CASE WHEN x THEN trueValue ELSE falseValue END` |
| `switchCase([$when => $then], $default)` | `CASE x WHEN when THEN then ... ELSE default END` |
| `concat(...$values)` | `CONCAT_WS('', x, a, b)` |
| `cast($type)` | `CAST(x AS type)` |
| `as($alias)` | `x AS alias` |
| `dateAfter($count, $interval = Interval::Day)` | MySQL, PostgreSQL: `x + INTERVAL 'count' DAY`<br>SQLite: `datetime(x, 'count days')` |
| `diff($other, $interval = Interval::Day)` | MySQL: `TIMESTAMPDIFF(DAY, other, x)`<br>PostgreSQL: `EXTRACT(DAY FROM (x - other))`<br>SQLite: `(strftime('%s', x) - strftime('%s', other)) / 86400` |
| `compare($other = '?', $operator = Operator::SameWith)` | `x = other` |
| `where($column, $operator = Operator::SameWith, $other = '?')` | `x WHERE column = other` |
| `in($values)` | `x IN (values)`, misalnya list atau subquery |
| `limit($count)` | `x LIMIT count` |
| `asStr()` | [`Str`](#str) yang diawali SQL-nya, misalnya untuk melanjutkan dengan method `Str` |
| `build()` | Mengembalikan string SQL |

`divide()` mengalikan dengan `1.0` untuk menghindari pembagian integer dan mengubah pembagi nol menjadi `NULL` alih-alih error. `diff()` saat ini hanya mendukung `Interval::Day`.

### Helper statis

`now()`, `string()` dan `queryConcat()` mengembalikan `string` biasa, untuk dipakai sebagai operand atau sebagai ekspresi awal `create()`. `relations()`, `flexibleCase()`, `select()`, `recursive()` dan `recursiveUnion()` mengembalikan `RawQuery`, jadi chain bisa dilanjutkan (panggil `build()` untuk `string`).

| Helper | Hasil |
| --- | --- |
| `RawQuery::now()` | `NOW()`; SQLite: `CURRENT_TIMESTAMP` |
| `RawQuery::string($value)` | String literal yang di-escape, misalnya `'available'`. Tanpa argumen: `''` |
| `RawQuery::queryConcat(...$expressions)` | Ekspresi digabung dengan spasi |
| `RawQuery::relations($column, $model)` | Kolom dengan tabel yang dinamai dari satu model, misalnya `relations('name', BlogPost::class)` → `blog_posts.name`; `['latest' => Post::class]` menghasilkan `latest_posts`, dan kolom kosong hanya menghasilkan tabelnya. Tidak di-quote, tidak mendapat table prefix dan tidak membaca `$table` model; pakai [`qualify()`](#statich) untuk itu |
| `RawQuery::select($columns, $table)` | `SELECT a, b FROM table`; `$columns` berupa string atau list |
| `RawQuery::recursive($name, $table, $columns, $where, $joinOn, $select = '*', $isUnionAll = true)` | CTE rekursif pada tabel yang mereferensikan dirinya sendiri: `WITH RECURSIVE name AS (SELECT columns FROM table WHERE where UNION ALL SELECT table.columns FROM table JOIN name ON joinOn) SELECT select FROM name`, dalam beberapa baris |
| `RawQuery::recursiveUnion($name, $anchor, $recursive, $select = '*', $isUnionAll = true)` | `WITH RECURSIVE name AS (anchor UNION ALL recursive) SELECT select FROM name`, dalam beberapa baris, untuk dua query apa pun |
| `RawQuery::flexibleCase($conditions, $default)` | `CASE WHEN a THEN ? ... END` untuk list, atau `CASE WHEN a THEN value ... END` untuk `[$when => $then]` |

```php
RawQuery::flexibleCase(['paid_at IS NULL' => "'unpaid'"], "'paid'")->as('status')->build();
// CASE WHEN paid_at IS NULL THEN 'unpaid' ELSE 'paid' END AS status (dalam beberapa baris)

RawQuery::create('id')->in(RawQuery::select('user_id', 'posts')->where('published_is'))->build();
// id IN (SELECT user_id FROM posts WHERE published_is = ?)

RawQuery::recursive(
    'tree',
    'subs',
    columns: 'id',
    where: ['parent_type' => '?', RawQuery::create('parent_id')->in('?, ?')],
    joinOn: ['parent_type' => '?', 'parent_id' => 'tree.id'],
)->build();
// WITH RECURSIVE tree AS (
//   SELECT id FROM subs WHERE parent_type = ? AND parent_id IN (?, ?)
//   UNION ALL
//   SELECT subs.id FROM subs JOIN tree ON subs.parent_type = ? AND subs.parent_id = tree.id
// ) SELECT * FROM tree (dalam beberapa baris)

RawQuery::recursive('up', 'subs', ['parent_type', 'parent_id'], ['id' => '?'], ['id' => 'up.parent_id'], 'parent_id AS root')
    ->where('parent_type')
    ->limit(1)
    ->build();
// … SELECT parent_id AS root FROM up WHERE parent_type = ? LIMIT 1

RawQuery::recursiveUnion('n', 'SELECT 1 AS x', 'SELECT x + 1 FROM n WHERE x < 5', 'x')->build();
// WITH RECURSIVE n AS (SELECT 1 AS x UNION ALL SELECT x + 1 FROM n WHERE x < 5) SELECT x FROM n (dalam beberapa baris)
```

Di `$where` dan `$joinOn`, key string menghasilkan `key = value` dan item list dipakai apa adanya; semuanya digabung dengan `AND`. Nilainya adalah SQL, jadi berikan `'?'` dan isi nilainya lewat binding, dengan urutan anchor, join, query luar. `recursive()` memberi prefix tabel pada `$columns` dan key `$joinOn` di langkah rekursif, kecuali sudah ada `.`-nya. `$columns` dipakai di kedua langkah, jadi pakai `recursiveUnion()` kalau langkah-langkahnya butuh kolom berbeda (depth atau path) atau CTE-nya tidak punya tabel. Setiap kolom mengambil tipenya dari query anchor, jadi `CAST` di sana kalau query rekursif menghasilkan nilai yang lebih lebar. `WITH RECURSIVE` berjalan di MySQL 8+, MariaDB 10.2.2+, PostgreSQL dan SQLite 3.8.3+.

Konstanta: `RawQuery::CHAR` dan `RawQuery::NULL`, misalnya `->cast(RawQuery::CHAR)`.

Enum yang dipakai method di atas (namespace `JundFauz\EloquentKompact\Query`):

- `Operator`: `SameWith` (`=`), `NotSameWith` (`!=`), `MoreThan` (`>`), `LessThan` (`<`), `MoreThanOrSame` (`>=`), `LessThanOrSame` (`<=`)
- `Interval`: `Second`, `Minute`, `Hour`, `Day`, `Month`, `Year`

### Contoh lain

```php
use JundFauz\EloquentKompact\Query\Interval;

// Menghitung nilai kolom yang tidak kosong
RawQuery::create('email')->replaceIfNull(RawQuery::string())->count()->as('email_count')->build();
// COUNT(NULLIF(email, '')) AS email_count

// Sisa hari sampai jatuh tempo
RawQuery::create('due_date')->diff(RawQuery::now())->as('days_left')->build();
// MySQL:      TIMESTAMPDIFF(DAY, NOW(), due_date) AS days_left
// PostgreSQL: EXTRACT(DAY FROM (due_date - NOW())) AS days_left
// SQLite:     (strftime('%s', due_date) - strftime('%s', CURRENT_TIMESTAMP)) / 86400 AS days_left

// Tanggal kedaluwarsa 30 hari setelah dibuat
RawQuery::create('created_at')->dateAfter(30)->as('expired_at')->build();
// MySQL, PostgreSQL: created_at + INTERVAL '30' DAY AS expired_at
// SQLite:            datetime(created_at, '30 days') AS expired_at

// Rasio terbayar, aman dari pembagian dengan nol
RawQuery::create('paid')->sum()->divide(RawQuery::create('total')->sum())->as('paid_ratio')->build();
// SUM(paid) * 1.0 / NULLIF(SUM(total), 0) AS paid_ratio
```

### Catatan

- Argumen dimasukkan ke SQL apa adanya. Jangan pernah memberikan input user secara langsung: pakai binding (`?`) atau `RawQuery::string()`.
- `now()`, `dateAfter()` dan `diff()` menghasilkan SQL khusus driver untuk MySQL, MariaDB, PostgreSQL dan SQLite. Di driver lain, misalnya SQL Server, `now()` dan `dateAfter()` menghasilkan SQL MySQL (`NOW()`, `INTERVAL`), dan `diff()` melempar `RuntimeException`. Driver dibaca dari koneksi default, jadi method-method ini butuh app yang sudah di-boot (bukan di data provider PHPUnit), dan model di koneksi lain mendapat SQL dari koneksi default.
- Di SQLite, `diff()` membuang pecahan detik, `datetime()` selalu menghasilkan `YYYY-MM-DD HH:MM:SS`, juga untuk kolom date, dan `CURRENT_TIMESTAMP` dalam UTC, sedangkan `NOW()` di MySQL memakai zona waktu session.

## Str

`JundFauz\EloquentKompact\Builder\Str` meng-extend `Stringable` milik Laravel, jadi semua [fluent string method](https://laravel.com/docs/strings#fluent-strings) tersedia, ditambah:

| Method | Hasil |
|---|---|
| `Str::of($value = '', $isMutable = false)` | `Str` baru, lihat [mutable](#str-mutable) |
| `replaceByRegex($pattern, $replace)` | `preg_replace()` pada nilainya |
| `removeByRegex($pattern)` | `preg_replace()` dengan `''` |
| `removeStart($search)` / `removeEnd($search)` | `$search` dihapus hanya di awal / akhir |
| `join($delimiter, ...$strings)` | Nilai dan string-string digabung dengan `$delimiter` |
| `add(...$strings)` / `concat(...$strings)` | String-string ditambahkan di belakang tanpa delimiter |
| `addAtStart(...$strings)` | String-string ditambahkan di depan tanpa delimiter |
| `addWord(...$words)` / `addWordAtStart(...$words)` | Kata-kata ditambahkan di belakang / depan dengan spasi, yang kosong dilewati |
| `addIf($condition, $string)` / `addWordIf($condition, $word)` | `add()` / `addWord()` hanya kalau `$condition` bernilai `true` |
| `newLine($count = 1)` | `$count` baris baru (`"\n"`) ditambahkan di belakang |
| `newLineIfNotEmpty($count = 1)` | `newLine()`, kecuali nilainya kosong |
| `addAtNewLine($string, $lineCount = 1)` | `$string` ditambahkan setelah `$lineCount` baris baru |
| `addNamespace(...$namespaces)` | Namespace ditambahkan di belakang dengan `\`, backslash di tiap bagian di-trim |
| `addNamespaceAtStart(...$namespaces)` | Namespace ditambahkan di depan dengan `\`, backslash di tiap bagian di-trim |
| `addPath(...$paths)` / `addPathAtStart(...$paths)` | Path ditambahkan di belakang / depan dengan satu `/`, yang kosong dilewati; slash di awal dan akhir path keseluruhan dipertahankan |
| `appPath()` | `app_path()` Laravel dari nilainya, misalnya `Models` → `{app_path}/Models` |
| `emptyIfFalse($condition)` | Nilainya, atau `''` kalau `$condition` falsy |
| `relation()` | Nilainya dengan token relasi, misalnya `user_id` |
| `addConstant($constant)` / `addConstantAtStart($class)` | `Class::Constant`, dengan nilainya sebagai class / konstanta |
| `isDefined()` | `defined()` PHP dari nilainya, misalnya `Str::of('Relation')->addConstantAtStart(ColumnCreator::class)->isDefined()` |
| `asRawQuery()` | [`RawQuery`](#rawquery) yang diawali nilainya |
| `asRegex()` | [`Regex`](#regex) yang diawali nilainya, misalnya `Str::of('_id')->asRegex()->endsWith()` → `/_id$/` |
| `sameWith($value)` / `notSameWith($value)` | `===` / `!==` ketat, `Stringable` Laravel dibandingkan berdasarkan nilainya |

`Str` bisa di-extend: `of()` dan semua method di atas, kecuali `asRawQuery()` dan `asRegex()`, mengembalikan instance subclass.

```php
use JundFauz\EloquentKompact\Builder\Str;

Str::of('App/blog_post/')->removeByRegex('/^app\//i')->trim('/')->plural()->pascal()->toString(); // BlogPosts
Str::of('User')->addNamespaceAtStart('App', 'Models')->toString(); // App\Models\User
Str::of('App')->addNamespace('Models', 'User')->toString();        // App\Models\User
Str::of('Models')->addPath('User.php')->appPath()->toString();     // {app_path}/Models/User.php
Str::of('app/Models/app')->removeStart('app/')->toString();       // Models/app
Str::of('a')->sameWith(Str::of('a'));                              // true
```

Berbeda dengan `Stringable::replace()`, yang argumen ketiganya soal case sensitivity, regex selalu lewat `replaceByRegex()` / `removeByRegex()`.

### Str mutable

`Str` bersifat immutable: setiap method mengembalikan `Str` baru. `Str::of($value, true)` memberikan yang mutable, di mana method-method di atas dan method Laravel yang mengembalikan string (`upper()`, `trim()`, `append()`, `replace()`, `finish()`, …) mengubah `Str` itu sendiri lalu mengembalikannya:

```php
$sql = Str::of('SELECT *', true);

if ($table) {
    $sql->addWord('FROM', $table);   // tidak perlu meng-assign hasilnya
}
$sql->upper()->finish(';');

$sql->value(); // SELECT * FROM POSTS;
```

Method Laravel yang mengembalikan hal lain (`explode()`, `length()`, `contains()`, …) serta `when()` / `unless()` tidak berubah.

## BoolB

`JundFauz\EloquentKompact\Builder\BoolB` adalah builder boolean yang immutable, dibaca lewat property readonly `result`. Perbandingannya ketat dan dievaluasi dari kiri ke kanan:

```php
use JundFauz\EloquentKompact\Builder\BoolB;

BoolB::compare($user->role)->sameWith('admin')
    ->or($user->status)->notSameWith('banned')
    ->result;
// ($role === 'admin') || ($status !== 'banned')

BoolB::initiate($user->active_is)->and($user->role)->sameWith('admin')->result;
```

Pakai `andGroup()` / `orGroup()` dengan `BoolB` lain (atau `bool`) untuk tanda kurung:

```php
// $isAdmin && ($isActive || $isVerified)
BoolB::compare($user->role)->sameWith('admin')
    ->andGroup(BoolB::compare($user->status)->sameWith('active')->or($user->verified_at)->notSameWith(null))
    ->result;
```

`BoolB::initiate()` juga menerima `BoolB` dan mengembalikannya apa adanya.

## Regex

`JundFauz\EloquentKompact\Regex\Regex` membangun pattern `preg_*`. Class ini `Stringable`, jadi bisa langsung diberikan ke `preg_match()`:

```php
use JundFauz\EloquentKompact\Regex\Quantifier;
use JundFauz\EloquentKompact\Regex\Regex;

Regex::create('id')->exact();         // /^id$/
Regex::create()->startsWith('app');   // /^app/
Regex::create()->endsWith('_at');     // /_at$/
Regex::create()->contain(Quantifier::oneOrMore, Regex::create()->range('a', 'z')); // /[a-z]+/
```

| Method | Menambahkan |
|---|---|
| `create($pattern = '')` | Diawali `$pattern` |
| `exact(...$parts)` | `^…$` |
| `startsWith(...$parts)` | `^…` |
| `endsWith(...$parts)` | `…$` |
| `group(...$parts)` | `(…)` di sekitar pattern dan bagian-bagiannya |
| `exceptWord($word)` | `(?!word)` |
| `contain($quantifier, ...$parts)` | `[…]` diikuti quantifier (`Quantifier::zeroOrMore`, `oneOrMore`, `zeroOrOne` atau string) |
| `range($from, $until)` | `from-until` |
| `before(...$parts)` | Bagian-bagian di depan pattern, misalnya `Regex::create('id')->group()->before('_')` → `/_(id)/` |
| `build()` | Pattern beserta delimiter `/`-nya |
| `asStr()` | `build()` sebagai [`Str`](#str) |

## Fungsi helper

Helper-helper ini ada di namespace `JundFauz\EloquentKompact`, jadi import dulu:

```php
use function JundFauz\EloquentKompact\str_concat;
use const JundFauz\EloquentKompact\NEW_LINE;
```

| Fungsi | Hasil |
|---|---|
| `is_not_null($value)` / `is_not_array($value)` | Kebalikan `is_null()` / `is_array()` |
| `is_all_null(...$values)` | Apakah semua nilai, termasuk array bersarang, bernilai `null` |
| `is_all_not_null(...$values)` | Apakah tidak ada nilai, termasuk array bersarang, yang `null` |
| `is_one_of_them_null(...$values)` | Apakah setidaknya satu nilai bernilai `null` |
| `is_one_of_them_not_null(...$values)` | Apakah setidaknya satu nilai, termasuk array bersarang, tidak `null` |
| `is_array_or_collection($value)` | Apakah nilainya array atau `Collection` Laravel |
| `is_associative_array($value)` | Apakah nilainya array yang bukan list, jadi `[5 => 'x']` juga termasuk; `[]` tidak |
| `same_with($a, $b, $cast = null)` / `not_same_with($a, $b, $cast = null)` | `$a === $b` / `$a !== $b`. Dengan cast, kedua nilai di-cast dulu dengannya: `same_with('5', 5, 'integer')` → `true` |
| `cast_as($value, $cast)` | Nilai yang di-cast dengan cast Laravel, seperti atribut model: `cast_as('10.5', 'decimal:2')` → `'10.50'`, `cast_as('paid', Status::class)` → `Status::Paid`. Cast tanggal menghasilkan object Carbon |
| `same_with_one_of($value, ...$others)` | Apakah `$value` `===` dengan salah satu nilai lain; `false` tanpa nilai lain |
| `higher_than($a, $b)` / `lower_than($a, $b)` | `$a > $b` / `$a < $b` untuk `int`, `float` dan string numerik, misalnya cast decimal seperti `'2.50'` |
| `higher_than_or_same_with($a, $b)` / `lower_than_or_same_with($a, $b)` | `$a >= $b` / `$a <= $b`, untuk nilai yang sama |
| `is_natural_number($number)` / `is_whole_number($number)` | Apakah `int`-nya `> 0` / `>= 0` |
| `is_zero($value)` | Apakah nilainya numerik dan `0`, juga `-0.0`, `'0'` dan `'0.00'`; `null` dan `'abc'` tidak |
| `is_count_zero($value)` / `is_count_one($value)` / `is_count_many($value)` | Apakah array atau `Countable`-nya tidak punya item / punya satu item / lebih dari satu item |
| `is_app_local_or_test()` | Apakah app berjalan di environment `local` atau `testing` |
| `not($condition)` | `! $condition` |
| `in($needle, $haystack, $strict = false)` | `in_array()` untuk array atau collection |
| `not_in_array($needle, $haystack, $strict = false)` | `! in_array()` |
| `not_in($needle, $haystack, $strict = false)` | `not_in_array()` untuk array atau collection |
| `not_defined($constant)` | `! defined()` |
| `str_constant($class, $constant)` | `Class::Constant`, misalnya untuk `defined()` atau `constant()` |
| `ternary($condition, $trueValue, $falseValue)` | `$condition ? $trueValue : $falseValue` |
| `elvis($value, $default)` | `$value ?: $default` |
| `null_coalescing($value, $default)` | `$value ?? $default`, untuk nilai yang ada: key array yang tidak ada tetap memunculkan warning `Undefined array key` dari PHP, pakai `array_get()` untuk itu |
| `array_get(&$value, $default = null)` | `$value ?? $default` tanpa warning untuk key array yang tidak ada, juga bersarang atau pada `null`: `array_get($config['token'], [])`. Tanpa default hasilnya `null`. Nilainya diambil by reference, jadi harus berupa variabel atau item array, dan key yang tidak ada ditambahkan ke array sebagai `null` |
| `array_get_as_collection(&$value, $default = null)` | `array_get()` sebagai [`Builder\Collection`](#helper-lain) |
| `get_array_list($array)` | Item-item dengan key `int`, key dipertahankan: `get_array_list(['a', 'k' => 'b'])` → `[0 => 'a']` |
| `is_not_dir($path)` | `! is_dir()` |
| `str_concat(...$values)` | Digabung tanpa delimiter |
| `word_concat(...$values)` | Digabung dengan spasi |
| `schema_concat(...$values)` | Digabung dengan `:` |
| `path_concat(...$values)` | Digabung dengan `/` |
| `relation_concat(...$values)` | Digabung dengan `.` |
| `namespace_concat(...$values)` | Digabung dengan `\`, backslash di tiap bagian di-trim |
| `write_line(...$lines)` | Digabung dengan baris baru |
| `comment($sentence)` | `// sentence` |
| `add_tab($sentence, $count = 1)` | Kalimat diindentasi dengan `$count` tab berukuran 4 spasi |
| `full_path_on_app($path)` | Path absolut di dalam `app/`, misalnya `App\Models` → `{app_path}/Models` |
| `array_flat(&$array)` | Meratakan array di tempat |
| `collect(...$values)` | [`Builder\Collection`](#helper-lain) dari satu nilai, seperti `collect()` Laravel, atau dari beberapa nilai sebagai item-nya: `collect(1, 2)` → `[1, 2]`. Tanpa nilai hasilnya kosong. Import untuk memakainya sebagai pengganti milik Laravel |
| `array_wrap($value)` | `Arr::wrap()` Laravel: nilainya sebagai array, `[]` untuk `null` |
| `array_cast($value)` | Item-item sebuah collection, atau `array_wrap()` dari nilai lain |
| `array_get_or($array, ...$keys)` | Nilai pertama yang tidak `null`, sesuai urutan key; dot notation didukung dan key yang tidak ada dilewati |
| `array_filter_filled($array)` | Nilai yang `filled()` menurut Laravel, key dipertahankan: `null`, string kosong dan array atau collection kosong dihapus; `0`, `'0'` dan `false` dipertahankan |
| `array_key_change(&$array, $oldKey, $newKey)` | Mengganti nama key di tempat. Key pindah ke akhir array, `$newKey` yang sudah ada diganti, dan `$oldKey` yang tidak ada memunculkan warning `Undefined array key` dari PHP |
| `array_key_values($array)` | `[key, value]` dari array dengan satu item; dengan beberapa item, semua key lalu semua value dalam satu list datar |
| `array_normalize($list)` | Item dari list dengan satu item, atau list-nya apa adanya. List-nya butuh key numerik mulai dari `0` |
| `array_compact($array, ...$keys)` | `[key => nilai]` untuk key-key array yang diberikan, `null` untuk yang tidak ada; named argument menjadi alias: `array_compact($data, 'a', x: 'b')` → `['a' => …, 'x' => $data['b']]` |
| `assign_as($value, ...$keys)` | `[key => value]` dengan nilai yang sama untuk setiap key, misalnya `assign_as(1, 'a', 'b')` → `['a' => 1, 'b' => 1]`; `[]` tanpa key |
| `merge(...$arrays)` | `array_merge()` dari array-array itu |
| `merge_mixed_array($values)` | String dan array di `$values` digabung jadi satu array: string dengan key `int` menjadi item list, string dengan key string mempertahankan key itu, dan item-item array digabung dengan `array_merge()` |
| `merge_as_array(...$values)` | Array asosiatif atau collection digabung beserta key-nya, key yang belakangan menang dan nilai bersarang tetap apa adanya: `merge_as_array(['a' => 1], ['b' => 2])` → `['a' => 1, 'b' => 2]`. Di sini `null`, string kosong dan array kosong diabaikan. Kalau ada nilai lain di sampingnya, semua nilai masuk ke satu list datar, termasuk array dan collection bersarang; key dibuang dan `null` dipertahankan: `merge_as_array(['a' => 1], 'x')` → `[1, 'x']` |
| `array_pull(&$array, ...$keys)` | `Arr::pullMany()`: menghapus key-key itu dan mengembalikan nilai dari satu key, atau `[key => value]` untuk beberapa key |
| `array_pull_list(&$array, ...$keys)` | `array_pull()`, tapi beberapa key menghasilkan list sesuai urutan yang diberikan, misalnya untuk `[$name, $email] = array_pull_list($data, 'name', 'email')` |
| `stringify($array)` | Setiap nilai di-cast ke `string`, termasuk array bersarang, key dipertahankan; `null` dan `false` menjadi `''` |
| `model_compact($model, ...$columns)` | `[kolom => nilai]` untuk kolom yang diberikan, setelah casting; atribut yang tidak ada bernilai `null` dan relasi yang sudah dimuat menjadi array. Kolom bisa diberikan dalam array, dan key string, di dalam array atau sebagai named argument, dipakai sebagai alias-nya: `model_compact($user, 'id', ['mail' => 'email'])` → `['id' => 1, 'mail' => '…']` |
| `str_before_last()`, `str_lower()`, `str_upper()` | `Str::beforeLast()`, `lower()`, `upper()` dari Laravel |
| `str_empty_if_false($condition, $value)` | `$value`, atau `''` kalau `$condition` falsy, seperti [`Str::emptyIfFalse()`](#str) |

Fungsi-fungsi penggabung juga menerima array, yang akan diratakan: `str_concat('a', ['b', ['c']])` → `abc`. `str_concat()` juga menerima collection: `str_concat('a', collect(['b', 'c']))` → `abc`.

Konstanta: `NEW_LINE` (`"\n"`), `DISTANCE_LINE` (`"\n\n"`), `NAMESPACE_DELIMITER` (`\`), `PATH_DELIMITER` (`/`) dan `TAB` (4 spasi).

## Helper lain

- `Relation\Arrange::relation($column, ...$models)` / `relations(...)` membangun path relasi dari class model (tunggal / jamak), dan mengembalikan `string`:

  ```php
  Arrange::relations('name', User::class, Post::class); // users.posts.name
  Arrange::relation('', User::class, 'roles');          // user.roles
  Arrange::relation('name', ['latest' => Post::class]); // latestPost.name
  ```

- `Builder\Relation` membangun path yang sama dengan chain yang dibaca dari kiri ke kanan. Kolom yang diberikan ke `arrange()` selalu ada di akhir:

  ```php
  use JundFauz\EloquentKompact\Builder\Relation;

  Relation::arrange('name')->relations(Post::class)->relation(User::class)->build(); // posts.user.name
  Relation::arrange()->relations(Post::class)->relation('comments')->build();        // posts.comments
  Relation::arrange('name')->relation(['latest' => Post::class])->build();           // latestPost.name
  ```

  Setiap method mengembalikan `Relation` baru. Class ini query `Expression`, tapi `with()`, `load()` dan `loadMissing()` milik Eloquent hanya menerima string, jadi panggil `build()`, cast ke `string`, atau lewatkan list-nya ke `stringify()` untuk method-method itu: `$user->loadMissing(stringify([$relation, $other]))`. Namanya sama dengan `Relation` milik Eloquent; import salah satunya dengan alias kalau sebuah file butuh keduanya.

- `Builder\Collection` meng-extend `Collection` milik Laravel dengan `filterFilled()`, `deleteNull()`, `sumAndCompact(...$columns)`, `getOr(...$keys)`, `getOrWithDefault($default, ...$keys)`, `getOrWithDefaultAsCollection($default, ...$keys)`, `normalFilter($callback)` dan `implodeAsStr($value = '', $glue)` (`implode()` Laravel sebagai [`Str`](#str)), dibangun di atas `array_filter_filled()` dan `array_get_or()`. Method-method ini, serta `get()`, `before()` dan `after()` milik Laravel, mengembalikan nilai atau default berupa string sebagai [`Str`](#str) dan array sebagai `Collection`; nilai lain dikembalikan apa adanya. Method Laravel mempertahankan class-nya sepanjang chain. Namanya sama dengan `Collection` milik Laravel; import salah satunya dengan alias kalau sebuah file butuh keduanya.

  ```php
  use function JundFauz\EloquentKompact\collect;

  $data = collect(['nickname' => null, 'name' => 'Ana', 'tags' => ['a', 'b']]);

  $data->filterFilled()->keys()->all();                      // ['name', 'tags']
  $data->deleteNull()->keys()->all();                        // ['name', 'tags']
  collect([['qty' => 2], ['qty' => 3]])->sumAndCompact('qty'); // ['qty' => 5]
  $data->getOr('nickname', 'name')->upper()->toString();     // 'ANA'
  $data->getOr('tags');                                      // Collection ['a', 'b']
  $data->get('name');                                        // Str 'Ana'
  $data->getOrWithDefault('Guest', 'nickname');              // Str 'Guest'
  $data->getOrWithDefaultAsCollection('Guest', 'nickname');  // Collection ['Guest']
  collect(['a', 'b'])->implodeAsStr(', ')->upper();          // Str 'A, B'
  ```

  `normalFilter($callback)` adalah `array_filter()` dengan nilai saja, jadi fungsi PHP seperti `'is_string'` bisa dipakai sebagai callback; `filter()` milik Laravel juga memberikan key, yang ditolak fungsi PHP dengan `ArgumentCountError`.

  `deleteNull()` hanya menghapus `null`, key dipertahankan, sedangkan `filterFilled()` juga menghapus string kosong dan array atau collection kosong. Keduanya mengembalikan collection baru dan tidak mengubah yang asli.

  `Str` adalah object, jadi string kosong bernilai truthy: bandingkan dengan `->isEmpty()` atau `->toString()`, bukan `if ($value)`.

- `Model\Collection` meng-extend `Collection` milik Eloquent dengan method yang sama dan `firstAndCompact(...$columns)`, dan dipakai oleh hasil query `KompactModel`, `KompactUser` dan `KompactPivot` (`all()`, `get()`, relasi, `with()`, `load()`):

  ```php
  Post::all()->sumAndCompact('views');        // ['views' => 120]
  Post::all()->firstAndCompact('id', 'name'); // ['id' => 1, 'name' => 'First'] dari model pertama, atau nilai null kalau tidak ada
  Post::all()->find(1);                       // method Eloquent tetap ada
  Post::all()->get(99, 'none');               // Str 'none'
  ```

  Hasil yang bukan model menjadi `Builder\Collection`, karena banyak method collection Eloquent mengharapkan model: `pluck()`, `keys()`, `map()` ke nilai lain, dan `Post::pluck()`, yang lewat [`Model\Builder`](#builder). Jadi `Post::all()->pluck('name')->get(0)` adalah `Str`, sedangkan `first()` tetap `string`. `DB::table()->pluck()` tetap memberikan `Collection` milik Laravel.

- `Helper\Arr` meng-extend `Arr` milik Laravel dengan `pullMany(&$array, ...$keys)` (mengambil beberapa key sekaligus) dan `isKeysDontExist($array, ...$keys)`.
- `Cast\UuidCast` meng-cast atribut dari dan ke `Ramsey\Uuid\UuidInterface`.
- Pesan error dan `Convert::booleanToString()` diterjemahkan dalam bahasa Inggris dan Indonesia.

## Pengembangan dengan bantuan AI

Package ini dikembangkan dengan bantuan AI, menggunakan Claude. Test suite dijalankan di CI untuk setiap push dan pull request, pada setiap versi PHP dan Laravel yang didukung.
