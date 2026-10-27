# Eloquent Kompact

A set of automation & helper tools to improve development experience and convenience in building app using Eloquent ORM by Laravel.

```bash
composer require jund-fauz/eloquent-kompact
```

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

- Each method changes the same instance. To reuse a base expression, call `create()` again instead of chaining twice from one variable.
- Arguments are inserted into the SQL as they are. Never pass user input directly: use bindings (`?`) or `RawQuery::string()`.
