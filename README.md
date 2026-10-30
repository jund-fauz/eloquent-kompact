# Eloquent Kompact

A set of automation & helper tools to improve development experience and convenience in building app using Eloquent ORM by Laravel.

```bash
composer require jund-fauz/eloquent-kompact
```

Requires PHP 8.3+ and Laravel 11, 12 or 13. The service provider is registered through package discovery.

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

This package follows [Semantic Versioning](https://semver.org). It is still in initial development (`0.x`): a minor release (`0.x.0`) may contain breaking changes, while a patch release (`0.x.y`) never does. Composer's `^0.x` constraint only allows patch updates, so require it as e.g. `^0.20`. The API is considered stable from `1.0.0`.

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
| `fillable.excluded_columns_by_regex` | `id`, columns ending in `_at` | Columns left out of the dynamic fillable list |
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
```

Long-running processes (queue workers, Octane) keep their in-memory copy until they restart, so run `php artisan queue:restart` / `php artisan octane:reload` after migrating in production.

## Column naming convention

Most automation reads the **type of a column from its name**: a token at the end of the column name.

| Token | Constant | Example | Auto cast |
|---|---|---|---|
| `_id` | `Creator::Relation` | `author_user_id` | – (used by dynamic relations) |
| `_enum` | `Creator::Enum` | `status_enum` | Enum class, see [AutoCast](#autocast) |
| `_array` | `Creator::Array` | `tags_array` | `array` |
| `_is` | `Creator::Boolean` | `active_is` | `boolean` |
| `_gram` | `Creator::Gram` | `weight_gram` | `decimal:7` |
| `_percentage` | `Creator::Percentage` | `tax_percentage` | `decimal:2` |
| `_pcs` | `Creator::Pcs` | `stock_pcs` | – |
| `_at` | `Creator::Timestamp` | `paid_at` | – |
| `_time` | `Creator::Time` | `start_time` | – |
| `_dropdown` | `Creator::Dropdown` | `size_dropdown` | – |

`JundFauz\EloquentKompact\Column\Creator` builds and reads these names, so a migration never has to spell a token by hand:

```php
use JundFauz\EloquentKompact\Column\Creator;

Creator::relation('author_user');                 // author_user_id
Creator::enum('status');                          // status_enum
Creator::quantity();                              // ['quantity_gram', 'quantity_pcs']

Creator::getPlainColumnName('author_user_id');    // Str: author_user
Creator::getColumnType('author_user_id');         // _id
Creator::getColumnType('author_user_id', true);   // id
Creator::castOf(Creator::Gram);                   // decimal:7
Creator::tokens();                                // every token, longest first
```

`Checker` tells whether a column (a name or a column definition with a `name`) contains a token:

```php
use JundFauz\EloquentKompact\Column\Checker;

Checker::isRelation('author_user_id'); // true
Checker::isEnum($table->string('status_enum')); // true
```

## KompactModel

Extend `JundFauz\EloquentKompact\Model\KompactModel` instead of Eloquent's `Model` to get every trait below at once. `KompactPivot` does the same for pivot models, with `AdditionalQueries`, `DynamicModelRelation`, `RelationData`, `StaticH` and soft deletes.

```php
use JundFauz\EloquentKompact\Model\KompactModel;

class Post extends KompactModel
{
    // Soft deletes are on by default
    protected static bool $useSoftDelete = false;
}
```

Every trait can also be used on its own model.

### DynamicFillableColumnDefinition

`getFillable()` returns every column of the table, except the ones matching `fillable.excluded_columns_by_regex`, merged with the model's own `$fillable`. No more `$fillable` arrays to keep in sync with the migrations.

### AutoCast

`getCasts()` adds a cast for every fillable column with a token that has one (see [the table above](#column-naming-convention)). Casts declared on the model win over the automatic ones.

An `_enum` column is cast to the enum `{singular table in PascalCase}{column without token in PascalCase}` inside `path.database_enum`, or to `string` if that enum does not exist:

```php
// orders.status_enum → App\Enums\OrderStatus
```

### DynamicModelRelation

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

// where() on several columns, with the values taken from another model
Order::wheres($otherOrder, 'tenant_id', 'branch_id')->get();

// The first non-null attribute
$user->getOr('nickname', 'name');

// Strict comparison of attributes, after casting
$user->sameWith('role', 'admin');
$user->match(['role' => ['admin', 'editor'], 'active_is' => true]);
```

`match()` returns `false` when no condition is given. An array value matches if the attribute equals one of its items, unless the attribute itself is an array.

### RelationData

Reads nested relations without null checks. Relations are given as model classes:

```php
$comment->getDataFromRelation('name', Post::class, User::class);
// $comment->post?->user?->name

$comment->getDatasFromRelation(['name', 'email'], Post::class, User::class);
// [$comment->post?->user?->name, $comment->post?->user?->email]
```

`getDatasFromRelation()` is `@experimental`.

### Filterable

Counts the non-empty values of each column, e.g. to show which filters have data:

```php
$counts = Order::getCountForFilter('email', 'phone', 'user.name');
$counts->email_count;          // COUNT(NULLIF(email, ''))
$counts->{'user.name_count'}; // relational columns are always 1
```

### StaticH

```php
Post::tableName();             // posts

// In a migration: the model of the table being built, or null
Post::getModel($table);        // App\Models\Post
```

## IDE helper

Dynamic relations are not methods, so IDEs cannot see them. Generate a helper file:

```bash
php artisan kompact:ide-helper
```

It writes `_ide_helper_kompact.php` (`@property-read` and `@method` for every dynamic relation of the models directly in `path.model` that use `DynamicModelRelation`) and registers it as a class mixin in `ide.json` for Laravel Idea. Each mixin is a `_EK_`-prefixed class inside `EloquentKompact\IdeHelper`, e.g. `EloquentKompact\IdeHelper\App\Models\_EK_User` for `App\Models\User`, so it never shows up as a second `User` class. Other `classMixins` entries in `ide.json` are kept.

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
use JundFauz\EloquentKompact\Column\Creator;
use JundFauz\EloquentKompact\Column\Payload;

Schema::create('posts', function (Blueprint $table) {
    $table->id();

    Config::apply([
        $table->string('title'),
        $table->string(Creator::enum('status')),
        $table->foreignId(Creator::relation('author_user')),
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
Convert::booleanToString('active_is');                // ['Not active', 'Active'], translated
Convert::dayToSecond(1);                              // 86400
Convert::hourToSecond(1);                             // 3600
```

## RawQuery

`JundFauz\EloquentKompact\Query\RawQuery` builds raw SQL expressions (for `selectRaw`, `whereRaw`, `orderByRaw`, etc.) with a fluent chain, compatible with MySQL & PostgreSQL.

Start with `create()`, chain the operations in reading order, then call `build()` to get the SQL string.

```php
use JundFauz\EloquentKompact\Query\RawQuery;

RawQuery::create('price')->zeroIfNull()->sum()->as('total_price')->build();
// SUM(COALESCE(price, 0)) AS total_price
```

### Using it with Laravel

`build()` is optional when passing a `RawQuery` to Laravel's raw methods (`selectRaw`, `whereRaw`, `orWhereRaw`, `havingRaw`, `groupByRaw`, `orderByRaw`, `DB::raw`). A `RawQuery` is `Stringable`, so Laravel turns it into SQL when compiling the query:

```php
Order::selectRaw(RawQuery::create('price')->zeroIfNull()->sum()->as('total_price'))->first();
```

A `RawQuery` is also a Laravel query `Expression`, so methods that normally take a column or table name (`select`, `orderBy`, `groupBy`, `DB::table`, `from`, …) use it as raw SQL instead of quoting it:

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

Methods with several operands (`plus`, `minus`, `multiply`, `divide`, `and`, `concat`, `isZeroOrNull`) use the subject as the first operand. When `create()` is called without an expression, the operands are used as they are:

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
| `and(...$conditions)` | `x AND a AND b` |
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
| `dateAfter($count, $interval = Interval::Day)` | `x + INTERVAL 'count' DAY` |
| `diff($other, $interval = Interval::Day)` | MySQL: `TIMESTAMPDIFF(DAY, other, x)`<br>PostgreSQL: `EXTRACT(DAY FROM (x - other))` |
| `compare($other = '?', $operator = Operator::SameWith)` | `x = other` |
| `build()` | Returns the SQL string |

`divide()` multiplies by `1.0` to avoid integer division and turns zero divisors into `NULL` instead of an error. `diff()` currently only supports `Interval::Day`.

### Static helpers

These return a plain `string`, to be used as an operand or as the starting expression of `create()`.

| Helper | Result |
| --- | --- |
| `RawQuery::now()` | `NOW()` |
| `RawQuery::string($value)` | Escaped string literal, e.g. `'available'`. Without an argument: `''` |
| `RawQuery::relations($column, ...$models)` | Relation path, e.g. `relations('name', User::class, Post::class)` → `users.posts.name` |
| `RawQuery::flexibleCase($conditions, $default)` | `CASE WHEN a THEN ? ... END` for a list, or `CASE WHEN a THEN value ... END` for `[$when => $then]` |
| `RawQuery::queryConcat(...$expressions)` | Expressions joined with a space |

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

// Expiry date 30 days after creation
RawQuery::create('created_at')->dateAfter(30)->as('expired_at')->build();
// created_at + INTERVAL '30' DAY AS expired_at

// Paid ratio, safe from division by zero
RawQuery::create('paid')->sum()->divide(RawQuery::create('total')->sum())->as('paid_ratio')->build();
// SUM(paid) * 1.0 / NULLIF(SUM(total), 0) AS paid_ratio
```

### Notes

- Arguments are inserted into the SQL as they are. Never pass user input directly: use bindings (`?`) or `RawQuery::string()`.
- `diff()` generates driver-specific SQL for MySQL, MariaDB and PostgreSQL; other drivers throw a `RuntimeException`.

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
| `addNamespace(...$namespaces)` | The namespaces appended with `\`, trimming the backslashes of each part |
| `addNamespaceAtStart(...$namespaces)` | The namespaces prepended with `\`, trimming the backslashes of each part |
| `addPath(...$paths)` | The paths appended with `/` |
| `appPath()` | Laravel's `app_path()` of the value, e.g. `Models` → `{app_path}/Models` |
| `emptyIfFalse($condition)` | The value, or `''` if `$condition` is falsy |
| `relation()` | The value with the relation token, e.g. `user_id` |
| `sameWith($value)` / `notSameWith($value)` | Strict `===` / `!==`, a Laravel `Stringable` is compared by its value |

`Str` can be extended: `of()` and every method above return an instance of the subclass.

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
Regex::create()->endsWith('_at');     // /_at$/
Regex::create()->contain(Quantifier::oneOrMore, Regex::create()->range('a', 'z')); // /[a-z]+/
```

| Method | Adds |
|---|---|
| `create($pattern = '')` | Starts with `$pattern` |
| `exact(...$parts)` | `^…$` |
| `endsWith(...$parts)` | `…$` |
| `exceptWord($word)` | `(?!word)` |
| `contain($quantifier, ...$parts)` | `[…]` followed by the quantifier (`Quantifier::zeroOrMore`, `oneOrMore`, `zeroOrOne` or a string) |
| `range($from, $until)` | `from-until` |
| `build()` | The pattern with its `/` delimiters |

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
| `same_with($a, $b)` / `not_same_with($a, $b)` | `$a === $b` / `$a !== $b` |
| `not($condition)` | `! $condition` |
| `str_concat(...$values)` | Joined without a delimiter |
| `word_concat(...$values)` | Joined with a space |
| `schema_concat(...$values)` | Joined with `:` |
| `path_concat(...$values)` | Joined with `/` |
| `namespace_concat(...$values)` | Joined with `\`, trimming the backslashes of each part |
| `write_line(...$lines)` | Joined with a new line |
| `comment($sentence)` | `// sentence` |
| `full_path_on_app($path)` | Absolute path inside `app/`, e.g. `App\Models` → `{app_path}/Models` |
| `array_flat(&$array)` | Flattens the array in place |
| `array_pull(&$array, ...$keys)` | `Arr::pullMany()`: removes the keys and returns the value of one key, or `[key => value]` for several |
| `model_compact($model, ...$columns)` | `[column => value]` for the given columns, after casting; missing attributes are `null` and loaded relations become arrays |
| `str_before_last()`, `str_lower()`, `str_upper()` | Laravel's `Str::beforeLast()`, `lower()`, `upper()` |

The joining functions accept arrays too, which are flattened: `str_concat('a', ['b', ['c']])` → `abc`.

Constants: `NEW_LINE` (`"\n"`), `DISTANCE_LINE` (`"\n\n"`), `NAMESPACE_DELIMITER` (`\`) and `PATH_DELIMITER` (`/`).

## Other helpers

- `Relation\Arrange::relation($column, ...$models)` / `relations(...)` build a relation path from model classes (singular / plural), returning a `string`:

  ```php
  Arrange::relations('name', User::class, Post::class); // users.posts.name
  Arrange::relation('', User::class, 'roles');          // user.roles
  ```

- `Helper\Arr` extends Laravel's `Arr` with `pullMany(&$array, ...$keys)` (pulls several keys at once) and `isKeysDontExist($array, ...$keys)`.
- `Cast\UuidCast` casts an attribute to and from a `Ramsey\Uuid\UuidInterface`.
- Error messages and `Convert::booleanToString()` are translated in English and Indonesian.
