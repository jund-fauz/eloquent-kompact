# Eloquent Kompact

A set of automation & helper tools to improve development experience and convenience in building app using Eloquent ORM by Laravel.

```bash
composer require jund-fauz/eloquent-kompact
```

Requires PHP 8.3+ and Laravel 12 or 13. The service provider is registered through package discovery.

- [Versioning](#versioning)
- [Configuration](#configuration)
- [Column naming convention](#column-naming-convention)
- [KompactModel](#kompactmodel)
- [IDE helper](#ide-helper)
- [Migration helpers](#migration-helpers)
- [RawQuery](#rawquery)
- [Str](#str)
- [BoolB](#boolb)
- [Regex](#regex)
- [Helper functions](#helper-functions)
- [Other helpers](#other-helpers)

## Versioning

This package follows [Semantic Versioning](https://semver.org) and is stable since `1.0.0`: breaking changes only come in a major release (`2.0.0`), new features in a minor release (`1.x.0`) and fixes in a patch release (`1.x.y`). Require it as `^1.0`.

Classes and methods marked `@internal` or `@experimental` are not covered by this promise and may change in any release.

## Configuration

The package works without publishing its config. To change the defaults, publish it:

```bash
php artisan vendor:publish --tag=eloquent-kompact-config
```

| Key | Default | Used for |
|---|---|---|
| `path.model` | `App\Models` | Namespace where dynamic relations and `StaticH::getModel()` look for models |
| `path.database_enum` | `App\Enums` | Namespace where `AutoCast` looks for enums |
| `column.custom_tokens` | `[]` | Your own column tokens, see [Custom tokens](#custom-tokens) |
| `fillable.exclude_column_by.exact` | `['id']` | Column names left out of the dynamic fillable list |
| `fillable.exclude_column_by.token` | `[]` | Token names (`'relation'`, `'enum'`, a custom token name, …) whose columns are left out of the dynamic fillable list |
| `fillable.exclude_column_by.regex` | `[]` | Regexes of columns left out of the dynamic fillable list |
| `ide-helper.auto_generate` | `env('KOMPACT_IDE_HELPER', false)` | Run `kompact:ide-helper` after `php artisan migrate` in the local environment |

A namespace may be written with or without a trailing backslash (`App\Models` or `App\Models\`).

> **Note:** your published config is merged with the package defaults on the **top-level keys only** (Laravel's `mergeConfigFrom()`). If you keep a top-level key such as `path`, keep **every** key inside it, because the package defaults for that key are not filled in. For example, a published config with only `'path' => ['model' => ...]` leaves `path.database_enum` empty.

### Column cache

Model columns are read from the database once and cached (file cache, plus memory for the running process). They feed the dynamic fillable list, auto casts, and dynamic relations.

The cache is cleared automatically after `php artisan migrate` (including `migrate:fresh` and `migrate:rollback`). To clear it by hand, e.g. after changing the schema without migrations:

```php
\JundFauz\EloquentKompact\Column\ColumnListing::flush();
\JundFauz\EloquentKompact\Model\KompactModel::flushKompactCache();
\JundFauz\EloquentKompact\Model\KompactPivot::flushKompactCache();
\JundFauz\EloquentKompact\Model\KompactUser::flushKompactCache();
```

Long-running processes (queue workers, Octane) keep their in-memory copy until they restart, so run `php artisan queue:restart` / `php artisan octane:reload` after migrating in production.

## Column naming convention

Most automation reads the **type of a column from its name**: a token at the end of the column name, after the delimiter (`_`). A token is always a suffix, so a token elsewhere in the name (`user_identity`, `created_at_backup`) or without the delimiter (`paid`) does not count.

| Token | Constant | Example | Auto cast |
|---|---|---|---|
| `id` | `ColumnCreator::Relation` | `author_user_id` | – (used by dynamic relations) |
| `enum` | `ColumnCreator::Enum` | `status_enum` | Enum class, see [AutoCast](#autocast) |
| `array` | `ColumnCreator::Array` | `tags_array` | `array` |
| `is` | `ColumnCreator::Boolean` | `active_is` | `boolean` |
| `gram` | `ColumnCreator::Gram` | `weight_gram` | `decimal:7` |
| `percentage` | `ColumnCreator::Percentage` | `tax_percentage` | `decimal:2` |
| `pcs` | `ColumnCreator::Pcs` | `stock_pcs` | – |
| `at` | `ColumnCreator::Timestamp` | `paid_at` | – |
| `time` | `ColumnCreator::Time` | `start_time` | – |
| `dropdown` | `ColumnCreator::Dropdown` | `size_dropdown` | – |
| `type` | `ColumnCreator::Type` | `commentable_type` | – (morph type column) |

`JundFauz\EloquentKompact\Column\ColumnCreator` builds and reads these names, so a migration never has to spell a token by hand:

```php
use JundFauz\EloquentKompact\Column\ColumnCreator;

ColumnCreator::relation('author_user');                 // author_user_id
ColumnCreator::enum('status');                          // status_enum
ColumnCreator::quantity();                              // ['quantity_gram', 'quantity_pcs']

ColumnCreator::getPlainColumnName('author_user_id');    // Str: author_user
ColumnCreator::getColumnType('author_user_id');         // id
ColumnCreator::getColumnType('author_user_id', true);   // _id, with the delimiter
ColumnCreator::castOf(ColumnCreator::Gram);                   // decimal:7
ColumnCreator::tokens();                                // Builder\Collection of every token, longest first
```

### Custom tokens

Add your own tokens in `column.custom_tokens`, as `name => token` or `name => [token, cast]`:

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
ColumnCreator::castOf('pu');              // decimal:3, so AutoCast casts price_pu
ColumnCheck::isPerUnit('price_pu');     // true
```

The name is written in snake case and called in camel case. A custom token is checked before a built-in one, so a custom name or token that is also built in wins. The caches of `ColumnCreator` are rebuilt when the config changes; the casts of a model are cached by `AutoCast` until `flushKompactCache()`, see [Column cache](#column-cache).

### Delimiter

The delimiter between the column name and the token is `_`. Change it once, before any model is used, e.g. in the `register()` of a service provider:

```php
ColumnCreator::configureDelimiter('-');

ColumnCreator::relation('user');         // user-id
ColumnCreator::getColumnType('user-id'); // id
ColumnCreator::getDelimiter();           // -
```

Only the token gets the delimiter: the name itself, with a prefix, stays snake case, e.g. `Convert::modelToColumn(User::class, 'author')` → `author_user-id`.

`ColumnCheck` tells whether a column (a name or a column definition with a `name`) ends with a token. Only the last token counts:

```php
use JundFauz\EloquentKompact\Column\ColumnCheck;

ColumnCheck::isRelation('author_user_id'); // true
ColumnCheck::isEnum($table->string('status_enum')); // true
ColumnCheck::isRelation('user_identity'); // false
ColumnCheck::isRelation('user_id_enum'); // false, it is an enum column
```

## KompactModel

Extend `JundFauz\EloquentKompact\Model\KompactModel` instead of Eloquent's `Model` to get every trait below at once. `KompactUser` does the same for the user model, on top of Laravel's `Illuminate\Foundation\Auth\User`. Both only use the `Kompact` trait, which a model with another parent class can use too; such a model has to set `$collectionClass` and `$builder` itself to get [`Model\Collection`](#other-helpers) and [`Model\Builder`](#builder). `KompactUser` is abstract. `KompactPivot` does the same for pivot models, with `AdditionalQueries`, `AutoRelation`, `StaticH` and soft deletes.

```php
use JundFauz\EloquentKompact\Model\KompactModel;

class Post extends KompactModel
{
    // Soft deletes are on by default
    protected static bool $useSoftDelete = false;
}

class User extends KompactUser
{
    //
}
```

With `$useSoftDelete = false`, `delete()` removes the row, so the table does not need a `deleted_at` column.

Every trait can also be used on its own model.

Model results use [`Model\Collection`](#other-helpers), unless the model has `#[CollectedBy]` or its own `$collectionClass`. Queries use [`Model\Builder`](#builder), unless the model has `#[UseEloquentBuilder]` or its own `$builder`.

### AutoFillable

`getFillable()` returns every column of the table, merged with the model's own `$fillable`. No more `$fillable` arrays to keep in sync with the migrations. Left out are the timestamp columns (`ColumnCreator::Timestamp`, e.g. `paid_at`) and the columns of `fillable.exclude_column_by`:

```php
'fillable' => [
    'exclude_column_by' => [
        'exact' => ['id', 'remember_token'],
        'token' => ['relation'],                  // every column ending in _id
        'regex' => [Regex::create('secret_')->startsWith()],
    ],
],
```

A model that should fill its timestamp columns too sets:

```php
protected static bool $includeTimestampColumn = true;
```

> **Note:** this includes columns such as `password` and `remember_token` on a `KompactUser`. Add the columns that should not be mass assigned to `fillable.exclude_column_by`, and never pass unfiltered input to `create()` or `fill()`.

### AutoCast

`getCasts()` adds a cast for every fillable column with a token that has one (see [the table above](#column-naming-convention)). Casts declared on the model win over the automatic ones.

An `_enum` column is cast to the enum `{singular table in PascalCase}{column without token in PascalCase}` inside `path.database_enum`, or to `string` if that enum does not exist:

```php
// orders.status_enum → App\Enums\OrderStatus
```

### AutoRelation

Relations are resolved from the relation name and the columns, without declaring a method:

| Relation | Resolved as | Requires |
|---|---|---|
| `$post->authorUser` | `belongsTo(User::class, 'author_user_id')` | a `User` model; the name is tried as `AuthorUser`, then `User` |
| `$user->posts` | `hasMany(Post::class, 'user_id')` | `posts.user_id` |
| `$user->authorPosts` | `hasMany(Post::class, 'author_user_id')` | `posts.author_user_id` |
| `$post->comments` | `morphMany(Comment::class, 'commentable')` | no `comments.post_id`, `Comment` has a single public method returning `MorphTo` and a `commentable_type` column |

A plural name without a matching column or morph throws a `BadMethodCallException`. Relations declared as methods always win. Dynamic relations respect `Model::preventLazyLoading()` and can be eager loaded (`Post::with('authorUser')`).

To get autocomplete for these relations, see [IDE helper](#ide-helper).

### AdditionalQueries

```php
// Copy related rows to another model, keeping only the given columns
$order->copyRelationsTo($newOrder, ['items' => ['product_id', 'qty_pcs']]);

// Whether a morph type attribute points to the class, using its morph alias if there is one
$comment->isMorphModelSame('commentable', Post::class); // true

// The first non-null attribute
$user->getOr('nickname', 'name');

// Several attributes, in the given order (a missing one is null)
$user->getMany('name', 'email'); // ['Ana', 'ana@example.com']

// Attributes of nested relations without null checks, with the relations given as model classes
$comment->getVia('name', Post::class, User::class);                // $comment->post?->user?->name
$comment->getManyVia(['name', 'email'], Post::class, User::class); // [$comment->post?->user?->name, $comment->post?->user?->email]
$comment->getVia('name', ['latest' => Post::class]);               // $comment->latestPost?->name

// The related model of nested relations, without a column
$comment->getRelVia(Post::class, User::class);                     // $comment->post?->user

// [column => value] for the given columns, after casting (a missing one is null)
$user->compact('name', 'email'); // ['name' => 'Ana', 'email' => 'ana@example.com']

// update() with several arrays merged, where a later key wins
$order->updateMany($data, $order->compact('note'), ['status' => 'paid']);

// Set attributes, without saving
$order->assign('status', 'paid');
$order->assign(['status' => 'paid', 'note' => '-']);
$order->assignFrom($otherOrder, 'tenant_id', 'branch_id');
$order->assignIfBlank('note', '-');
$order->assignIfBlank(['note' => '-', 'status' => 'new']);
$order->assignIfBlankFrom($otherOrder, 'tenant_id', 'branch_id');
$order->assignIfNull('note', '-');
$order->assignIfNull(['note' => '-', 'status' => 'new']);
$order->assignIfNullFrom($otherOrder, 'tenant_id', 'branch_id');

// Whether an attribute is blank, filled or null
$order->isBlank('note');
$order->isFilled('note');
$order->isNull('note');
$order->isNotNull('note');

// Strict comparison of attributes, after casting
$user->sameWith('role', 'admin');
$user->notSameWith('role', 'admin');
$user->sameWithOther($otherUser, 'tenant_id', 'branch_id');      // every column is the same
$user->sameWithOtherOr($otherUser, 'tenant_id', 'branch_id');    // at least one column is the same
$user->notSameWithOther($otherUser, 'tenant_id', 'branch_id');   // every column is different
$user->notSameWithOtherOr($otherUser, 'tenant_id', 'branch_id'); // at least one column is different
$user->match(['role' => ['admin', 'editor'], 'active_is' => true]);

// The same comparisons, for columns that have changed and are not saved yet
$user->sameWithAndDirty('role', 'admin');
$user->notSameWithAndDirty('role', 'admin');
$user->sameWithOtherAndDirty($otherUser, 'tenant_id', 'branch_id');      // every column is the same and dirty
$user->sameWithOtherOrAndDirty($otherUser, 'tenant_id', 'branch_id');    // at least one column is the same and dirty
$user->notSameWithOtherAndDirty($otherUser, 'tenant_id', 'branch_id');   // every column is different and dirty
$user->notSameWithOtherOrAndDirty($otherUser, 'tenant_id', 'branch_id'); // at least one column is different and dirty
$user->matchAndDirty(['role' => ['admin', 'editor'], 'active_is' => true]);
$comment->isMorphModelSameAndDirty('commentable', Post::class);
$order->isBlankAndDirty('note');
$order->isFilledAndDirty('note');
$order->isNullAndDirty('note');
$order->isNotNullAndDirty('note');

// Whether columns were changed by the last save
$order->wereChanged('status', 'note');    // every column was changed
$order->wereNotChanged('status', 'note'); // every column was not changed
$order->wereChanged();                    // any column was changed
$order->wereNotChanged();                 // no column was changed

// The id of a random row, or null if there is none
Order::randomId();
Order::randomId('status', 'paid'); // the arguments of where()
```

`assignIfBlank()`, `assignIfBlankFrom()` and `isBlank()` use Laravel's `blank()`, so `null`, blank strings (also `'   '`) and empty arrays count as blank, while `0`, `'0'` and `false` do not. `isFilled()` uses `filled()`, its opposite. `assignIfNull()` and `assignIfNullFrom()` only replace `null`.

`match()` and the `sameWithOther()` family return `false` when no condition or column is given. `notSameWithOther()` is not the opposite of `sameWithOther()`: that is `notSameWithOtherOr()`. Objects such as the Carbon dates of `datetime` columns are compared by identity, so two models never have the same date. An array value matches if the attribute equals one of its items, unless the attribute itself is an array.

The `AndDirty` methods use Eloquent's `isDirty()` on each column, so `sameWithOtherOrAndDirty()` needs one column that is both dirty and the same, and `sameWithOtherAndDirty()` and `matchAndDirty()` are `false` if one of the columns is not dirty. Every attribute set on a new model is dirty. Changed columns are still dirty in the `saving`, `saved`, `updating`, `updated`, `creating` and `created` events, and nothing is dirty once `save()` has returned.

`wereChanged()` and `wereNotChanged()` use Eloquent's `wasChanged()` on each column. Unlike `wasChanged('a', 'b')`, which needs one changed column, `wereChanged('a', 'b')` needs all of them. `wereNotChanged()` is not the opposite of `wereChanged()` when some columns were changed and others were not: both are `false`. Like `wasChanged()`, they see no change right after `create()`, and a `save()` without changes keeps the changes of the last update.

`updateMany()` only takes arrays and ignores the empty ones. If one of them is a list, it changes nothing and returns `false` without an error, so check the result when the data can be a list. Like `update()`, it only fills the fillable columns and returns `false` on a model that is not saved.

`randomId()` reads the `id` column, so it does not work on a model with another primary key.

### Builder

`KompactModel`, `KompactUser` and `KompactPivot` use `JundFauz\EloquentKompact\Model\Builder`, which extends Eloquent's `Builder`:

```php
// where() on several columns, with the values taken from another model
Order::whereSameWith($otherOrder, 'tenant_id', 'branch_id')->get();

// where() on a morph type column, using the morph alias of the class if there is one
Comment::whereMorphType('commentable', Post::class)->get(); // where commentable_type = 'App\Models\Post'
Comment::whereMorphTypeNot('commentable', Post::class)->get(); // where not commentable_type = 'App\Models\Post'

// whereHas() with whereIn() on a column of the relation
Order::whereInRelation('items', 'status', ['paid', 'sent'])->get();

// A Builder\Collection instead of Laravel's Collection
Order::pluck('status');
```

> **Note:** a model that only uses a trait has to set `protected static string $builder = Builder::class;` itself, and so does the related model when these methods are called on a relation. Without it there is **no error**: Laravel reads the call as a dynamic where, so `whereSameWith($other, 'id')` becomes `where same_with = ...`.

Like a scope, these methods put the earlier conditions in a group if they contain an `or`, so `Order::where('a', 1)->orWhere('b', 1)->whereMorphType(...)` gives `where (a = ? or b = ?) and ...`. That group holds every earlier condition, and a later `orWhere()` is not grouped, so still use a closure to group the conditions yourself.

`whereInRelation()` uses the column as given, so on a `belongsToMany` relation, a column that is also on the pivot table has to be written with its table (`'tags.id'`), or the query fails with an ambiguous column error. An empty list of values finds nothing.

These methods are not scopes, so a scope of the same name on a model is never called, `hasNamedScope()` returns `false` for them, and they cannot be passed to `scopes()`.

### StaticH

```php
Post::tableName();             // posts
Post::morphClass();            // App\Models\Post, or its alias in Relation::morphMap()
BlogPost::baseName();          // BlogPost

// The relation name of the model
BlogPost::singular();          // blogPost
BlogPost::plural();            // blogPosts
BlogPost::singular('latest');  // latestBlogPost
BlogPost::plural('latest');    // latestBlogPosts

// The foreign key column of the model
BlogPost::foreign();           // blog_post_id
BlogPost::foreign('from');     // from_blog_post_id

// A column with its table, for a join or a RawQuery chain
User::foreignFrom(BlogPost::class);           // "blog_posts"."user_id"
User::foreignFrom(BlogPost::class, 'author'); // "blog_posts"."author_user_id"
BlogPost::qualify('title');                   // "blog_posts"."title"
BlogPost::qualify('qty')->zeroIfNull()->sum()->as('total'); // SUM(COALESCE("blog_posts"."qty", 0)) AS total

User::leftJoin('blog_posts', 'users.id', '=', User::foreignFrom(BlogPost::class));

// A path that starts with the model, for use on another model that has a relation to it
User::relationTo('roles');                // user.roles
User::relationTo(BlogPost::class);        // user.blogPost
User::columnTo('name', 'roles');          // user.roles.name
User::columnTo('title', BlogPost::class); // user.blogPost.title
User::relationTo(['latest' => BlogPost::class]); // user.latestBlogPost

Order::with(User::relationTo('roles'));   // order → user → roles

// In a migration: the model of the table being built, or null
Post::getModel($table);        // App\Models\Post
```

`singular()`, `plural()`, `foreign()`, `relationTo()` and `columnTo()` use the name of the class they are called on, so a subclass gives its own name, and a morph alias is not used. They do not create the model or read the database, so they are safe in a migration. `foreign()` always ends with `_id`, even for a model with another primary key, unlike Eloquent's `getForeignKey()`. In a path, a class becomes a singular name and any other string is kept as it is, so write a plural relation as a string (`'roles'`). A prefixed relation is written as `[prefix => model class]`, see [`Convert`](#migration-helpers).

`foreignFrom()` and `qualify()` return a [`RawQuery`](#rawquery) that is already quoted by the grammar of the connection of the model, with its table prefix, so it stays right in a join and through a chain. They use the `$table` of the model, and a column that already has a table (`other.name`) keeps it. They create the model, without reading the database. An empty column gives invalid SQL. `pluck()` looks for a property with the quotes in its name and fails, so write the column as a string there: `pluck('blog_posts.title')`.

## IDE helper

Dynamic relations are not methods, so IDEs cannot see them. Generate a helper file:

```bash
php artisan kompact:ide-helper
```

It writes `_ide_helper_kompact.php` (`@property-read` and `@method` for every dynamic relation of the models directly in `path.model` that use `AutoRelation`) and registers it as a class mixin in `ide.json` for Laravel Idea. Each mixin is a `_EK_`-prefixed class inside `EloquentKompact\IdeHelper`, e.g. `EloquentKompact\IdeHelper\App\Models\_EK_User` for `App\Models\User`, so it never shows up as a second `User` class. Other `classMixins` entries in `ide.json` are kept.

When the related model uses its own builder, such as [`Model\Builder`](#builder) on a `KompactModel`, the `@method` of the relation also returns that builder, so its methods are suggested after `$post->comments()`.

Set `KOMPACT_IDE_HELPER=true` in `.env` to run it automatically after every `php artisan migrate` in the `local` environment. You will probably want to add `_ide_helper_kompact.php` to `.gitignore`.

## Migration helpers

`Config::apply()` applies column modifiers to many columns at once. The payload keys are, in this order:

- `all`: every column;
- a column name: that column;
- a regex: every matching column;
- `rest`: every column that no column name or regex matched.

A payload is a modifier name, a list of them, or `[modifier => arguments]`:

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

`Payload::allNullable` is a ready-made `['all' => 'nullable']`.

`Convert` turns names and values into each other:

```php
use JundFauz\EloquentKompact\Column\Convert;

Convert::modelToColumn(User::class);                  // user_id
Convert::modelToColumn(User::class, 'author');        // author_user_id
Convert::modelToRelation(User::class);                // user
Convert::modelToRelation(BlogPost::class, true, true); // blog_posts
Convert::modelToRelation(['latest' => BlogPost::class]); // latestBlogPost
Convert::modelToRelation(['latest' => BlogPost::class], true, true); // latest_blog_posts
Convert::booleanToString('active_is');                // ['Not active', 'Active'], translated
Convert::dayToSecond(1);                              // 86400
Convert::hourToSecond(1);                             // 3600
```

A prefix is only written as an array with one key, the prefix, and one value, the model class. Anything else throws an `InvalidArgumentException`.

## RawQuery

`JundFauz\EloquentKompact\Query\RawQuery` builds raw SQL expressions (for `selectRaw`, `whereRaw`, `orderByRaw`, etc.) with a fluent chain, compatible with MySQL, MariaDB, PostgreSQL & SQLite.

Start with `create()`, chain the operations in reading order, then call `build()` to get the SQL string.

```php
use JundFauz\EloquentKompact\Query\RawQuery;

RawQuery::create('price')->zeroIfNull()->sum()->as('total_price')->build();
// SUM(COALESCE(price, 0)) AS total_price
```

### Using it with Laravel

`build()` is optional when passing a `RawQuery` to Laravel's raw methods (`selectRaw`, `whereRaw`, `orWhereRaw`, `havingRaw`, `groupByRaw`, `orderByRaw`, `DB::raw`). A `RawQuery` is a query `Expression`, so Laravel turns it into SQL when compiling the query. `havingRaw()` uses it as a string instead, which gives the same SQL because a `RawQuery` is also `Stringable`:

```php
Order::selectRaw(RawQuery::create('price')->zeroIfNull()->sum()->as('total_price'))->first();
```

Methods that normally take a column or table name (`select`, `orderBy`, `groupBy`, `DB::table`, `from`, …) use it as raw SQL too, so it is not quoted and does not get the table prefix of the connection:

```php
DB::table(RawQuery::create('(SELECT customer, SUM(price) AS total FROM orders GROUP BY customer) AS t'))->get();
// select * from (SELECT customer, SUM(price) AS total FROM orders GROUP BY customer) AS t
```

Call `build()` when you need the SQL as a real `string`, e.g. to store it, compare it, or pass it to a parameter typed as `string` from a file with `declare(strict_types=1)`.

### How the chain works

The expression passed to `create()` becomes the **subject**. Every chained method wraps or extends the current subject, and the result becomes the new subject:

```php
use JundFauz\EloquentKompact\Query\Operator;

RawQuery::create('stock')             // stock
    ->compare('0', Operator::MoreThan) // stock > 0
    ->if(RawQuery::string('available'), RawQuery::string('empty'))
    ->as('status')
    ->build();
// CASE WHEN stock > 0 THEN 'available' ELSE 'empty' END AS status
```

Methods with several operands (`plus`, `minus`, `multiply`, `divide`, `concat`, `isZeroOrNull`) use the subject as the first operand. When `create()` is called without an expression, the operands are used as they are:

```php
RawQuery::create('price')->plus('tax', 'shipping')->build(); // price + tax + shipping
RawQuery::create()->plus('tax', 'shipping')->build();        // tax + shipping
```

Every chained method returns a **new** `RawQuery` and leaves the original unchanged, so one base can be reused:

```php
$price = RawQuery::create('price')->zeroIfNull();

$price->sum()->build(); // SUM(COALESCE(price, 0))
$price->max()->build(); // MAX(COALESCE(price, 0))
$price->build();        // COALESCE(price, 0)
```

### Nesting

Every argument that takes an expression accepts either a `string` or another `RawQuery`. A `RawQuery` is `Stringable`, so nested builders don't need `build()`:

```php
RawQuery::create('price')
    ->multiply('qty')
    ->plus(RawQuery::create('shipping')->zeroIfNull())
    ->build();
// price * qty + COALESCE(shipping, 0)
```

Operators don't add parentheses on their own. Use `wrap()` when precedence matters:

```php
RawQuery::create('price')->plus('tax')->wrap()->multiply('qty')->build();
// (price + tax) * qty
```

### Bindings

`compare()` uses `?` as its default right-hand side, so it works with query bindings:

```php
Order::whereRaw(
    RawQuery::create('total')->compare(operator: Operator::MoreThanOrSame),
    [100_000]
)->get();
// WHERE total >= ?
```

### Chain methods

In the table, `x` is the current subject.

| Method | Result |
| --- | --- |
| `zeroIfNull()` | `COALESCE(x, 0)` |
| `replaceIfNull($value)` | `NULLIF(x, value)` |
| `isNull()` | `x IS NULL` |
| `isZeroOrNull(...$columns)` | `x = 0 OR x IS NULL OR a = 0 OR a IS NULL ...` |
| `and($condition)` | `x AND condition`; chain it for more conditions |
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
| `in($values)` | `x IN (values)`, e.g. a list or a subquery |
| `limit($count)` | `x LIMIT count` |
| `asStr()` | A [`Str`](#str) started with the SQL, e.g. to keep building it with `Str` methods |
| `build()` | Returns the SQL string |

`divide()` multiplies by `1.0` to avoid integer division and turns zero divisors into `NULL` instead of an error. `diff()` currently only supports `Interval::Day`.

### Static helpers

`now()`, `string()` and `queryConcat()` return a plain `string`, to be used as an operand or as the starting expression of `create()`. `relations()`, `flexibleCase()`, `select()`, `recursive()` and `recursiveUnion()` return a `RawQuery`, so the chain can go on (call `build()` for a `string`).

| Helper | Result |
| --- | --- |
| `RawQuery::now()` | `NOW()`; SQLite: `CURRENT_TIMESTAMP` |
| `RawQuery::string($value)` | Escaped string literal, e.g. `'available'`. Without an argument: `''` |
| `RawQuery::queryConcat(...$expressions)` | Expressions joined with a space |
| `RawQuery::relations($column, $model)` | Column with the table named after one model, e.g. `relations('name', BlogPost::class)` → `blog_posts.name`; `['latest' => Post::class]` gives `latest_posts`, and an empty column gives only the table. It is not quoted, does not get the table prefix and does not read the `$table` of the model; use [`qualify()`](#statich) for that |
| `RawQuery::select($columns, $table)` | `SELECT a, b FROM table`; `$columns` is a string or a list |
| `RawQuery::recursive($name, $table, $columns, $where, $joinOn, $select = '*', $isUnionAll = true)` | Recursive CTE over a self-referencing table: `WITH RECURSIVE name AS (SELECT columns FROM table WHERE where UNION ALL SELECT table.columns FROM table JOIN name ON joinOn) SELECT select FROM name`, on several lines |
| `RawQuery::recursiveUnion($name, $anchor, $recursive, $select = '*', $isUnionAll = true)` | `WITH RECURSIVE name AS (anchor UNION ALL recursive) SELECT select FROM name`, on several lines, for any two queries |
| `RawQuery::flexibleCase($conditions, $default)` | `CASE WHEN a THEN ? ... END` for a list, or `CASE WHEN a THEN value ... END` for `[$when => $then]` |

```php
RawQuery::flexibleCase(['paid_at IS NULL' => "'unpaid'"], "'paid'")->as('status')->build();
// CASE WHEN paid_at IS NULL THEN 'unpaid' ELSE 'paid' END AS status (on several lines)

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
// ) SELECT * FROM tree (on several lines)

RawQuery::recursive('up', 'subs', ['parent_type', 'parent_id'], ['id' => '?'], ['id' => 'up.parent_id'], 'parent_id AS root')
    ->where('parent_type')
    ->limit(1)
    ->build();
// … SELECT parent_id AS root FROM up WHERE parent_type = ? LIMIT 1

RawQuery::recursiveUnion('n', 'SELECT 1 AS x', 'SELECT x + 1 FROM n WHERE x < 5', 'x')->build();
// WITH RECURSIVE n AS (SELECT 1 AS x UNION ALL SELECT x + 1 FROM n WHERE x < 5) SELECT x FROM n (on several lines)
```

In `$where` and `$joinOn`, a string key gives `key = value` and a list item is kept as it is; they are joined with `AND`. The values are SQL, so pass `'?'` and give the values as bindings, in the order anchor, join, outer query. `recursive()` prefixes `$columns` and the `$joinOn` keys with the table in the recursive step, unless they already have a `.`. `$columns` are used in both steps, so use `recursiveUnion()` when the steps need different columns (a depth or a path) or the CTE has no table. Each column takes its type from the anchor query, so `CAST` it there when the recursive query makes a wider value. `WITH RECURSIVE` works on MySQL 8+, MariaDB 10.2.2+, PostgreSQL and SQLite 3.8.3+.

Constants: `RawQuery::CHAR` and `RawQuery::NULL`, e.g. `->cast(RawQuery::CHAR)`.

Enums used by the methods above (namespace `JundFauz\EloquentKompact\Query`):

- `Operator`: `SameWith` (`=`), `NotSameWith` (`!=`), `MoreThan` (`>`), `LessThan` (`<`), `MoreThanOrSame` (`>=`), `LessThanOrSame` (`<=`)
- `Interval`: `Second`, `Minute`, `Hour`, `Day`, `Month`, `Year`

### More examples

```php
use JundFauz\EloquentKompact\Query\Interval;

// Count non-empty values of a column
RawQuery::create('email')->replaceIfNull(RawQuery::string())->count()->as('email_count')->build();
// COUNT(NULLIF(email, '')) AS email_count

// Days left until due date
RawQuery::create('due_date')->diff(RawQuery::now())->as('days_left')->build();
// MySQL:      TIMESTAMPDIFF(DAY, NOW(), due_date) AS days_left
// PostgreSQL: EXTRACT(DAY FROM (due_date - NOW())) AS days_left
// SQLite:     (strftime('%s', due_date) - strftime('%s', CURRENT_TIMESTAMP)) / 86400 AS days_left

// Expiry date 30 days after creation
RawQuery::create('created_at')->dateAfter(30)->as('expired_at')->build();
// MySQL, PostgreSQL: created_at + INTERVAL '30' DAY AS expired_at
// SQLite:            datetime(created_at, '30 days') AS expired_at

// Paid ratio, safe from division by zero
RawQuery::create('paid')->sum()->divide(RawQuery::create('total')->sum())->as('paid_ratio')->build();
// SUM(paid) * 1.0 / NULLIF(SUM(total), 0) AS paid_ratio
```

### Notes

- Arguments are inserted into the SQL as they are. Never pass user input directly: use bindings (`?`) or `RawQuery::string()`.
- `now()`, `dateAfter()` and `diff()` generate driver-specific SQL for MySQL, MariaDB, PostgreSQL and SQLite. On another driver, e.g. SQL Server, `now()` and `dateAfter()` give the SQL of MySQL (`NOW()`, `INTERVAL`), and `diff()` throws a `RuntimeException`. The driver is read from the default connection, so these methods need a booted app (not in a PHPUnit data provider), and a model on another connection gets the SQL of the default one.
- On SQLite, `diff()` drops fractions of a second, `datetime()` always gives `YYYY-MM-DD HH:MM:SS`, also for a date column, and `CURRENT_TIMESTAMP` is in UTC, while MySQL's `NOW()` uses the time zone of the session.

## Str

`JundFauz\EloquentKompact\Builder\Str` extends Laravel's `Stringable`, so every [fluent string method](https://laravel.com/docs/strings#fluent-strings) is available, plus:

| Method | Result |
|---|---|
| `Str::of($value = '')` | A new `Str` |
| `replaceByRegex($pattern, $replace)` | `preg_replace()` on the value |
| `removeByRegex($pattern)` | `preg_replace()` with `''` |
| `removeStart($search)` / `removeEnd($search)` | `$search` removed only at the start / end |
| `join($delimiter, ...$strings)` | The value and the strings joined with `$delimiter` |
| `add(...$strings)` / `concat(...$strings)` | The strings appended without a delimiter |
| `addAtStart(...$strings)` | The strings prepended without a delimiter |
| `addWord(...$words)` / `addWordAtStart(...$words)` | The words appended / prepended with a space, skipping empty ones |
| `addIf($condition, $string)` / `addWordIf($condition, $word)` | `add()` / `addWord()` only if `$condition` is `true` |
| `newLine($count = 1)` | `$count` new lines (`"\n"`) appended |
| `newLineIfNotEmpty($count = 1)` | `newLine()`, unless the value is empty |
| `addAtNewLine($string, $lineCount = 1)` | `$string` appended after `$lineCount` new lines |
| `addNamespace(...$namespaces)` | The namespaces appended with `\`, trimming the backslashes of each part |
| `addNamespaceAtStart(...$namespaces)` | The namespaces prepended with `\`, trimming the backslashes of each part |
| `addPath(...$paths)` / `addPathAtStart(...$paths)` | The paths appended / prepended with a single `/`, skipping empty ones; the slashes at the start and end of the whole path are kept |
| `appPath()` | Laravel's `app_path()` of the value, e.g. `Models` → `{app_path}/Models` |
| `emptyIfFalse($condition)` | The value, or `''` if `$condition` is falsy |
| `relation()` | The value with the relation token, e.g. `user_id` |
| `addConstant($constant)` / `addConstantAtStart($class)` | `Class::Constant`, with the value as the class / the constant |
| `isDefined()` | PHP's `defined()` of the value, e.g. `Str::of('Relation')->addConstantAtStart(ColumnCreator::class)->isDefined()` |
| `asRawQuery()` | A [`RawQuery`](#rawquery) started with the value |
| `asRegex()` | A [`Regex`](#regex) started with the value, e.g. `Str::of('_id')->asRegex()->endsWith()` → `/_id$/` |
| `sameWith($value)` / `notSameWith($value)` | Strict `===` / `!==`, a Laravel `Stringable` is compared by its value |

`Str` can be extended: `of()` and every method above, except `asRawQuery()` and `asRegex()`, return an instance of the subclass.

```php
use JundFauz\EloquentKompact\Builder\Str;

Str::of('App/blog_post/')->removeByRegex('/^app\//i')->trim('/')->plural()->pascal()->toString(); // BlogPosts
Str::of('User')->addNamespaceAtStart('App', 'Models')->toString(); // App\Models\User
Str::of('App')->addNamespace('Models', 'User')->toString();        // App\Models\User
Str::of('Models')->addPath('User.php')->appPath()->toString();     // {app_path}/Models/User.php
Str::of('app/Models/app')->removeStart('app/')->toString();       // Models/app
Str::of('a')->sameWith(Str::of('a'));                              // true
```

Unlike `Stringable::replace()`, whose third argument is about case sensitivity, regexes always go through `replaceByRegex()` / `removeByRegex()`.

## BoolB

`JundFauz\EloquentKompact\Builder\BoolB` is an immutable boolean builder, read with the readonly `result` property. Comparisons are strict and evaluated from left to right:

```php
use JundFauz\EloquentKompact\Builder\BoolB;

BoolB::compare($user->role)->sameWith('admin')
    ->or($user->status)->notSameWith('banned')
    ->result;
// ($role === 'admin') || ($status !== 'banned')

BoolB::initiate($user->active_is)->and($user->role)->sameWith('admin')->result;
```

Use `andGroup()` / `orGroup()` with another `BoolB` (or a `bool`) for parentheses:

```php
// $isAdmin && ($isActive || $isVerified)
BoolB::compare($user->role)->sameWith('admin')
    ->andGroup(BoolB::compare($user->status)->sameWith('active')->or($user->verified_at)->notSameWith(null))
    ->result;
```

`BoolB::initiate()` also accepts a `BoolB` and returns it as it is.

## Regex

`JundFauz\EloquentKompact\Regex\Regex` builds `preg_*` patterns. It is `Stringable`, so it can be passed to `preg_match()` directly:

```php
use JundFauz\EloquentKompact\Regex\Quantifier;
use JundFauz\EloquentKompact\Regex\Regex;

Regex::create('id')->exact();         // /^id$/
Regex::create()->startsWith('app');   // /^app/
Regex::create()->endsWith('_at');     // /_at$/
Regex::create()->contain(Quantifier::oneOrMore, Regex::create()->range('a', 'z')); // /[a-z]+/
```

| Method | Adds |
|---|---|
| `create($pattern = '')` | Starts with `$pattern` |
| `exact(...$parts)` | `^…$` |
| `startsWith(...$parts)` | `^…` |
| `endsWith(...$parts)` | `…$` |
| `group(...$parts)` | `(…)` around the pattern and the parts |
| `exceptWord($word)` | `(?!word)` |
| `contain($quantifier, ...$parts)` | `[…]` followed by the quantifier (`Quantifier::zeroOrMore`, `oneOrMore`, `zeroOrOne` or a string) |
| `range($from, $until)` | `from-until` |
| `before(...$parts)` | The parts in front of the pattern, e.g. `Regex::create('id')->group()->before('_')` → `/_(id)/` |
| `build()` | The pattern with its `/` delimiters |
| `asStr()` | `build()` as a [`Str`](#str) |

## Helper functions

The helpers live in the `JundFauz\EloquentKompact` namespace, so import them first:

```php
use function JundFauz\EloquentKompact\str_concat;
use const JundFauz\EloquentKompact\NEW_LINE;
```

| Function | Result |
|---|---|
| `is_not_null($value)` / `is_not_array($value)` | Negated `is_null()` / `is_array()` |
| `is_all_null(...$values)` | Whether every value, nested arrays included, is `null` |
| `is_associative_array($value)` | Whether the value is an array that is not a list, so also `[5 => 'x']`; `[]` is not |
| `same_with($a, $b)` / `not_same_with($a, $b)` | `$a === $b` / `$a !== $b` |
| `same_with_one_of($value, ...$others)` | Whether `$value` is `===` to one of the others; `false` without others |
| `higher_than($a, $b)` / `lower_than($a, $b)` | `$a > $b` / `$a < $b` for `int`, `float` and numeric strings, e.g. decimal casts like `'2.50'` |
| `higher_than_or_same_with($a, $b)` / `lower_than_or_same_with($a, $b)` | `$a >= $b` / `$a <= $b`, for the same values |
| `is_natural_number($number)` / `is_whole_number($number)` | Whether the `int` is `> 0` / `>= 0` |
| `is_zero($number)` | Whether the `int` or `float` is `0`, also `-0.0` |
| `is_count_zero($value)` / `is_count_one($value)` / `is_count_many($value)` | Whether the array or `Countable` has no item / one item / more than one item |
| `is_app_local_or_test()` | Whether the app runs in the `local` or `testing` environment |
| `not($condition)` | `! $condition` |
| `in($needle, $haystack, $strict = false)` | `in_array()` for an array or a collection |
| `not_in_array($needle, $haystack, $strict = false)` | `! in_array()` |
| `not_in($needle, $haystack, $strict = false)` | `not_in_array()` for an array or a collection |
| `not_defined($constant)` | `! defined()` |
| `str_constant($class, $constant)` | `Class::Constant`, e.g. for `defined()` or `constant()` |
| `ternary($condition, $trueValue, $falseValue)` | `$condition ? $trueValue : $falseValue` |
| `elvis($value, $default)` | `$value ?: $default` |
| `null_coalescing($value, $default)` | `$value ?? $default` |
| `str_concat(...$values)` | Joined without a delimiter |
| `word_concat(...$values)` | Joined with a space |
| `schema_concat(...$values)` | Joined with `:` |
| `path_concat(...$values)` | Joined with `/` |
| `relation_concat(...$values)` | Joined with `.` |
| `namespace_concat(...$values)` | Joined with `\`, trimming the backslashes of each part |
| `write_line(...$lines)` | Joined with a new line |
| `comment($sentence)` | `// sentence` |
| `add_tab($sentence, $count = 1)` | The sentence indented with `$count` tabs of 4 spaces |
| `full_path_on_app($path)` | Absolute path inside `app/`, e.g. `App\Models` → `{app_path}/Models` |
| `array_flat(&$array)` | Flattens the array in place |
| `collect(...$values)` | A [`Builder\Collection`](#other-helpers) of one value, like Laravel's `collect()`, or of several values as its items: `collect(1, 2)` → `[1, 2]`. Without a value it is empty. Import it to use it instead of Laravel's |
| `array_wrap($value)` | Laravel's `Arr::wrap()`: the value as an array, `[]` for `null` |
| `array_cast($value)` | The items of a collection, or `array_wrap()` of any other value |
| `array_get_or($array, ...$keys)` | The first value that is not `null`, in the order of the keys; dot notation is supported and missing keys are skipped |
| `array_filter_filled($array)` | The values that are Laravel's `filled()`, keeping the keys: `null`, blank strings and empty arrays or collections are removed; `0`, `'0'` and `false` are kept |
| `array_key_change(&$array, $oldKey, $newKey)` | Renames a key in place. The key moves to the end of the array, an existing `$newKey` is replaced, and a missing `$oldKey` gives PHP's `Undefined array key` warning |
| `array_key_values($array)` | `[key, value]` of an array with one item; with several items, every key and then every value in one flat list |
| `array_normalize($list)` | The item of a list with one item, or the list as it is. The list needs numeric keys from `0` |
| `assign_as($value, ...$keys)` | `[key => value]` with the same value for every key, e.g. `assign_as(1, 'a', 'b')` → `['a' => 1, 'b' => 1]`; `[]` without keys |
| `merge_as_array(...$values)` | Associative arrays or collections merged with their keys, where a later key wins and nested values stay as they are: `merge_as_array(['a' => 1], ['b' => 2])` → `['a' => 1, 'b' => 2]`. `null`, blank strings and empty arrays are ignored there. With any other value next to them, every value goes in one flat list, nested arrays and collections included; keys are dropped and `null` is kept: `merge_as_array(['a' => 1], 'x')` → `[1, 'x']` |
| `array_pull(&$array, ...$keys)` | `Arr::pullMany()`: removes the keys and returns the value of one key, or `[key => value]` for several |
| `array_pull_list(&$array, ...$keys)` | `array_pull()`, but several keys give a list in the given order, e.g. for `[$name, $email] = array_pull_list($data, 'name', 'email')` |
| `stringify($array)` | Every value cast to a `string`, nested arrays included, keeping the keys; `null` and `false` become `''` |
| `model_compact($model, ...$columns)` | `[column => value]` for the given columns, after casting; missing attributes are `null` and loaded relations become arrays |
| `str_before_last()`, `str_lower()`, `str_upper()` | Laravel's `Str::beforeLast()`, `lower()`, `upper()` |
| `str_empty_if_false($condition, $value)` | `$value`, or `''` if `$condition` is falsy, like [`Str::emptyIfFalse()`](#str) |

The joining functions accept arrays too, which are flattened: `str_concat('a', ['b', ['c']])` → `abc`. `str_concat()` also accepts collections: `str_concat('a', collect(['b', 'c']))` → `abc`.

Constants: `NEW_LINE` (`"\n"`), `DISTANCE_LINE` (`"\n\n"`), `NAMESPACE_DELIMITER` (`\`), `PATH_DELIMITER` (`/`) and `TAB` (4 spaces).

## Other helpers

- `Relation\Arrange::relation($column, ...$models)` / `relations(...)` build a relation path from model classes (singular / plural), returning a `string`:

  ```php
  Arrange::relations('name', User::class, Post::class); // users.posts.name
  Arrange::relation('', User::class, 'roles');          // user.roles
  Arrange::relation('name', ['latest' => Post::class]); // latestPost.name
  ```

- `Builder\Relation` builds the same paths with a chain read from left to right. The column given to `arrange()` always comes last:

  ```php
  use JundFauz\EloquentKompact\Builder\Relation;

  Relation::arrange('name')->relations(Post::class)->relation(User::class)->build(); // posts.user.name
  Relation::arrange()->relations(Post::class)->relation('comments')->build();        // posts.comments
  Relation::arrange('name')->relation(['latest' => Post::class])->build();           // latestPost.name
  ```

  Every method returns a new `Relation`. It is a query `Expression`, but Eloquent's `with()`, `load()` and `loadMissing()` only take strings, so call `build()`, cast it to a `string`, or pass the list through `stringify()` for them: `$user->loadMissing(stringify([$relation, $other]))`. Its name is the same as Eloquent's `Relation`; import one of them with an alias if a file needs both.

- `Builder\Collection` extends Laravel's `Collection` with `filterFilled()`, `deleteNull()`, `sumAndCompact(...$columns)`, `getOr(...$keys)`, `getOrWithDefault($default, ...$keys)`, `getOrWithDefaultAsCollection($default, ...$keys)`, `normalFilter($callback)` and `implodeAsStr($value = '', $glue)` (Laravel's `implode()` as a [`Str`](#str)), built on `array_filter_filled()` and `array_get_or()`. These methods, and Laravel's `get()`, `before()` and `after()`, return a string value or default as a [`Str`](#str) and an array as a `Collection`; other values are returned as they are. Laravel's methods keep the class through the chain. Its name is the same as Laravel's `Collection`; import one of them with an alias if a file needs both.

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

  `normalFilter($callback)` is `array_filter()` with the value only, so a PHP function such as `'is_string'` can be the callback; Laravel's `filter()` also passes the key, which a PHP function rejects with an `ArgumentCountError`.

  `deleteNull()` only removes `null`, keeping the keys, while `filterFilled()` also removes blank strings and empty arrays or collections. Both return a new collection and leave the original as it is.

  A `Str` is an object, so an empty string is truthy: compare with `->isEmpty()` or `->toString()` rather than `if ($value)`.

- `Model\Collection` extends Eloquent's `Collection` with the same methods and `firstAndCompact(...$columns)`, and is what `KompactModel`, `KompactUser` and `KompactPivot` results use (`all()`, `get()`, relations, `with()`, `load()`):

  ```php
  Post::all()->sumAndCompact('views');        // ['views' => 120]
  Post::all()->firstAndCompact('id', 'name'); // ['id' => 1, 'name' => 'First'] of the first model, or null values if there is none
  Post::all()->find(1);                       // Eloquent's methods are still there
  Post::all()->get(99, 'none');               // Str 'none'
  ```

  Results that are not models become a `Builder\Collection`, since many methods of Eloquent's collection expect models: `pluck()`, `keys()`, `map()` to other values, and `Post::pluck()`, which goes through [`Model\Builder`](#builder). So `Post::all()->pluck('name')->get(0)` is a `Str`, while `first()` stays a `string`. `DB::table()->pluck()` still gives Laravel's `Collection`.

- `Helper\Arr` extends Laravel's `Arr` with `pullMany(&$array, ...$keys)` (pulls several keys at once) and `isKeysDontExist($array, ...$keys)`.
- `Cast\UuidCast` casts an attribute to and from a `Ramsey\Uuid\UuidInterface`.
- Error messages and `Convert::booleanToString()` are translated in English and Indonesian.
