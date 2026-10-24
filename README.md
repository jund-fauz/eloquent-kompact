# Eloquent Kompact

A set of automation & helper tools to improve development experience and convenience in building app using Eloquent ORM by Laravel.

```bash
composer require jund-fauz/eloquent-kompact
```

## RawQuery

`JundFauz\EloquentKompact\Query\RawQuery` builds raw SQL expressions (for `selectRaw`, `whereRaw`, `orderByRaw`, etc.) with a fluent chain, compatible with MySQL & PostgreSQL.

Start with `create()`, chain the operations in reading order, then call `build()` to get the SQL string.

```php
use JundFauz\EloquentKompact\Query\RawQuery;

$total = RawQuery::create('price')->zeroIfNull()->sum()->as('total_price')->build();
// SUM(COALESCE(price, 0)) AS total_price

Order::selectRaw($total)->first();
```

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
    RawQuery::create('total')->compare(operator: Operator::MoreThanOrSame)->build(),
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
