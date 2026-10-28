# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.26.0] - 2026-10-28

### Added

- `BoolB::initiate()`, matching `StrB::initiate()`.

### Deprecated

- `BoolB::create()`; use `BoolB::initiate()` instead.

  ```php
  // 0.25
  BoolB::create($user->is_active)->and($user->is_verified)->result;

  // 0.26
  BoolB::initiate($user->is_active)->and($user->is_verified)->result;
  ```

## [0.25.0] - 2026-10-28

### Added

- `Builder\BoolB`, an immutable boolean builder started with `BoolB::create()`, with `and()`, `or()` and `set()`. The value is read from the readonly `result` property.

  ```php
  BoolB::create($user->is_active)->and($user->is_verified)->or($user->is_admin)->result;
  ```

- `same_with()` helper function in the `JundFauz\EloquentKompact\Helper` namespace, a strict `===` comparison. It is not global, so import it with `use function JundFauz\EloquentKompact\Helper\same_with;`.

### Changed

- The `Internal\Concerns\Builder` base class is marked `@internal`. The methods it provides to `RawQuery`, `Regex` and `StrB` stay public API.

### Tests

- Added unit tests for `BoolB` and `same_with()`.

## [0.24.0] - 2026-10-28

### Added

- `AdditionalQueries::match()` to check whether a model's attributes match every given condition, using strict comparison. An array value matches if the attribute equals one of its items, unless the attribute itself is an array.

  ```php
  $user->match(['role' => ['admin', 'editor'], 'is_active' => true]);
  ```

- `AdditionalQueries::sameWith()`, a shorthand for `$model->{$column} === $value`. The attribute is compared after casting, so the value must have the cast type.
- Global `is_not_array()` helper function.

## [0.23.0] - 2026-10-28

### Added

- `StrB::singular()`, `plural()`, `pascal()`, `camel()` and `snake()`, backed by Laravel's `Str`. The `plural()` `$prependCount` argument requires Laravel 12+ and the `pascal()` `$normalize` argument requires Laravel 13+; older versions ignore them.

  ```php
  StrB::initiate('blog_post')->plural()->pascal()->build(); // BlogPosts
  ```

### Tests

- Added unit tests for `StrB`.

## [0.22.0] - 2026-10-28

### Added

- `StrB::remove()`, `join()` and `emptyIfFalse()`.

### Deprecated

- `Helper\Str`; use `Builder\StrB` for the package methods and `Illuminate\Support\Str` for the Laravel methods.
- `Str::concat()`; use `StrB::join()` instead.

  ```php
  // 0.21
  Str::concat('a', 'b', 'c');

  // 0.22
  StrB::initiate('a')->join('', 'b', 'c')->build();
  ```

- `Str::ifTrue()`; use `StrB::emptyIfFalse()` instead. Unlike `ifTrue()`, `emptyIfFalse()` only accepts a `bool`.

### Changed

- The internal `methodNotFound()` helper is now `protected`, so it is no longer callable from outside the package classes.

## [0.21.0] - 2026-10-27

### Added

- `Builder\StrB`, an immutable string builder started with `StrB::initiate()`, with `replace()` (plain or regex), `trim()`, `ltrim()` and `rtrim()`.
- `Path::fullPathOnApp()` to turn a namespace-like path into an absolute path inside the app directory.

  ```php
  Path::fullPathOnApp('App/Database/Models/'); // {app_path}\Database/Models
  ```

### Changed

- `RawQuery` and `Regex` share an internal `Builder` base class; `RawQuery::create()`, `build()` and string casting behave as before.
- `Checker` and `Creator` throw their `BadMethodCallException` through a shared internal helper. No behavior change.

## [0.20.3] - 2026-10-27

### Changed

- `AutoCast` and `StaticH` no longer import `Illuminate\Support\Str` under the `LaravelStr` alias; `AutoCast` uses the package `Str` helper instead. No behavior change.

## [0.20.2] - 2026-10-27

### Changed

- The internal `Driver` class moved from `Query\Driver\Driver` to `Internal\Database\Driver`.

## [0.20.1] - 2026-10-27

### Changed

- `RelationData::getDataFromRelation()` and `getDatasFromRelation()` type the relation classes as `string`.
- `RelationData::getDatasFromRelation()` is marked `@experimental` and may change in a minor release.

### Added

- Versioning and backward compatibility policy in the README.
- GitHub Actions workflow that runs the tests on PHP 8.3 to 8.5 against Laravel 11, 12 and 13, on every push and every week, to catch clashes with new Laravel releases early.

### Fixed

- The README listed `App\Database\Models` and `App\Database\Enums` as the default namespaces; they are `App\Models` and `App\Enums` since `0.16.0`.

## [0.20.0] - 2026-10-27

### Changed

- **BREAKING:** `Helper\Arr::pull()` with several keys was renamed to `pullMany()`. `Arr::pull()` is now Laravel's `pull($array, $key, $default = null)`, so an old call with several keys does not fail but treats the second key as the default value and returns only the first key's value.

  ```php
  // 0.19
  Arr::pull($data, 'name', 'email');

  // 0.20
  Arr::pullMany($data, 'name', 'email');
  ```

- `Helper\Arr` extends `Illuminate\Support\Arr` and `Helper\Str` extends `Illuminate\Support\Str`, so every Laravel `Arr` / `Str` method is available on the package helpers.
- `Arrange::relation()` and `Arrange::relations()` accept relation classes as nested arrays, and pass through relation names that are not class names as-is.

  ```php
  Arrange::relations('', [User::class, 'posts']); // users.posts
  ```

## [0.19.1] - 2026-10-27

### Fixed

- `is_all_null()` returned `false` when given arrays of `null` values, e.g. `is_all_null([null, null])`. Nested arrays are now flattened before the check.

## [0.19.0] - 2026-10-27

### Added

- `RelationData::getDatasFromRelation()` to read several columns from the same relation in one call, returning their values in the order of the given column names.

## [0.18.0] - 2026-10-27

### Added

- Global `is_all_null()` helper function.
- Dependency on `symfony/polyfill-php84`, so `is_all_null()` can use `array_all()` on PHP 8.3.

## [0.17.0] - 2026-10-26

### Changed

- **BREAKING:** Removed the dependency on `jund-fauz/regex-builder`. The package now ships its own `JundFauz\EloquentKompact\Regex\Regex`. A published `config/eloquent-kompact.php` that imports `JundFauz\RegexBuilder\Regex` must import `JundFauz\EloquentKompact\Regex\Regex` instead, or the app must require `jund-fauz/regex-builder` itself.

  ```php
  // 0.16
  use JundFauz\RegexBuilder\Regex;

  // 0.17
  use JundFauz\EloquentKompact\Regex\Regex;
  ```

### Added

- `Regex` fluent pattern builder with `create()`, `exact()`, `endsWith()`, `exceptWord()`, `contain()`, `range()` and `build()`.
- `Quantifier` enum (`zeroOrMore`, `oneOrMore`, `zeroOrOne`).
- `Arr::arrange()` to join array elements into a string.

## [0.16.0] - 2026-10-26

### Changed

- **BREAKING:** The default `path.model` namespace is now `App\Models` (was `App\Database\Models`), and the default `path.database_enum` namespace is now `App\Enums` (was `App\Database\Enums`). Apps that keep their models or enums in the old namespaces must publish the config and set these paths.
- **BREAKING:** Requires `jund-fauz/regex-builder` `~2.0` (was `^1.1`). A published config must use the 2.x syntax:

  ```php
  // 0.15
  Regex::create(Regex::exact('id'));
  Regex::create(Regex::endsWith('_at'));

  // 0.16
  Regex::create('id')->exact();
  Regex::create()->endsWith('_at');
  ```

## [0.15.0] - 2026-10-26

### Changed

- **BREAKING:** `RawQuery` is immutable. Every chained method returns a new instance and leaves the original unchanged, so code that relied on mutating an instance must use the returned value.

  ```php
  // 0.14
  $query = RawQuery::create('price');
  $query->sum();
  $query->build(); // SUM(price)

  // 0.15
  $query = RawQuery::create('price');
  $query->sum()->build(); // SUM(price)
  $query->build();        // price
  ```

- **BREAKING:** `RawQuery` implements Laravel's query `Expression`, so methods that take a column or table name (`select`, `orderBy`, `groupBy`, `DB::table`, `from`, …) use it as raw SQL instead of quoting it. `DB::table(RawQuery::create('orders'))` now compiles to `from orders` instead of `from "orders"`, and a subquery no longer needs `DB::raw()`.
- **BREAKING:** The default `path.database_enum` namespace is now `App\Database\Enums` (was `App\Database\Enum`).
- **BREAKING:** Removed the `DynamicModelRelation::basicModelNamespace` constant.
- Dynamic relations are resolved only through `relationResolver()`; the `getRelationValue()` and `__call()` overrides and `hasDynamicRelation()` were removed.
- `Driver` is marked `@internal`.

### Added

- `php artisan vendor:publish --tag=eloquent-kompact-config` to publish the package config.
- `KompactModel::flushKompactCache()` and `KompactPivot::flushKompactCache()` to clear the in-memory column, cast, fillable and relation caches.
- Configuration and column cache sections in the README.

### Fixed

- The package config was never loaded, so `KompactModel` failed with `foreach() argument must be of type array|object, null given` unless the config was published. It is now merged with `mergeConfigFrom()`.
- Model and enum namespaces were joined without a separator (e.g. `App\Database\ModelsUser`), so dynamic relations, enum auto casts and `StaticH::getModel()` never found their classes. Namespaces may now be written with or without a trailing backslash.
- Dynamic relations bypassed `Model::preventLazyLoading()` and `Model::automaticallyEagerLoadRelationships()`.
- The column cache was never cleared after the schema changed, so new columns were missing from the dynamic fillable list and auto casts. It is now cleared automatically after `php artisan migrate`.

### Tests

- Added tests for passing `RawQuery` instances to Laravel query builder methods, and for `RawQuery` immutability.

## [0.14.0] - 2026-10-24

### Changed

- `RawQuery::flexibleCase()` now accepts `RawQuery` instances as conditions and values.

### Tests

- Added unit tests for using `RawQuery` builders inside `replaceIfNull()`, `if()`, `switchCase()`, `and()`, `concat()` and `flexibleCase()`.

## [0.13.0] - 2026-10-24

### Changed

- **BREAKING:** `RawQuery` is now a fluent, chainable builder instead of a set of static string helpers.
  Start an expression with `RawQuery::create($expression)`, chain methods, then call `build()` or cast it to a string.

  ```php
  // 0.12
  RawQuery::sum(RawQuery::zeroIfNull('price'));

  // 0.13
  RawQuery::create('price')->zeroIfNull()->sum()->build();
  ```

  The affected methods no longer take the source column as their first argument and now return `static`:
  `zeroIfNull()`, `isNull()`, `and()`, `wrap()`, `sum()`, `plus()`, `minus()`, `multiply()`, `divide()`, `if()`,
  `switchCase()`, `concat()`, `cast()`, `as()`, `max()`, `dateAfter()`, `diff()` and `compare()`.
- **BREAKING:** `RawQuery::nullIfZero()` was replaced by `replaceIfNull(string|RawQuery $replaceValue)`.
- **BREAKING:** Model traits now type against `Illuminate\Database\Eloquent\Model`, and model / enum namespaces are read from the new `eloquent-kompact` config.
- `Filterable` now builds its count expression with `RawQuery`.
- `RawQuery::queryConcat()` and most builder methods accept `RawQuery` instances as operands.

### Added

- `RawQuery::create()`, `count()`, `build()` and `__toString()`.
- `config/eloquent-kompact.php` with a regex-based list of columns excluded from dynamic fillable.
- Global `is_not_null()` helper function.
- Dependency on `jund-fauz/regex-builder`.
- PHPUnit and Orchestra Testbench test setup with a `composer test` script, and unit tests for `RawQuery`.

## [0.12.0] - 2026-10-24

### Added

- `KompactModel` base model, combining these traits (soft deletes can be toggled with a static property):
  - `AdditionalQueries`: copy relations and scope queries based on other models.
  - `AutoCast`: cast attributes automatically from column types and enums.
  - `DynamicFillableColumnDefinition`: determine fillable columns dynamically, excluding them by regex.
  - `DynamicModelRelation`: resolve relations dynamically from naming conventions.
  - `Filterable`: count non-empty values for given columns, including relational ones.
  - `RelationData`: read nested relation data.
  - `StaticH`: static helpers such as the table name and resolving a model from a schema blueprint.
- `KompactPivot` base pivot with the dynamic relation traits and soft deletes.

## [0.11.0] - 2026-10-23

### Added

- `RawQuery::queryConcat()` to join several query expressions with spaces.

## [0.10.0] - 2026-10-23

### Added

- `Operator` enum for SQL comparison operators.
- `RawQuery::compare()` to build comparison expressions using `Operator`.

### Removed

- `RawQuery::sameWith()`; use `RawQuery::compare()` instead.

### Fixed

- `RawQuery::diff()` now takes two column names and uses the second one correctly in the MySQL and PostgreSQL expressions.

## [0.9.0] - 2026-10-23

### Added

- `RawQuery::now()` returning the current timestamp expression.

### Changed

- `RawQuery::diff()` only supports the `DAY` interval for now.

## [0.8.0] - 2026-10-23

### Added

- `RawQuery::diff()` to compute the interval difference between dates. It generates driver-specific SQL for MySQL, MariaDB and PostgreSQL; other drivers (e.g. SQLite, SQL Server) throw a `RuntimeException`.
- Translated error message for unsupported drivers (`en`, `id`).

## [0.7.0] - 2026-10-23

### Added

- `Interval` enum (`Second`, `Minute`, `Hour`, `Day`, `Month`, `Year`).

### Changed

- `RawQuery::dateAfter()` takes an interval count and an `Interval` type.

## [0.6.0] - 2026-10-23

### Added

- `RawQuery::nullIfZero()`, `dateAfter()` and `string()` (escapes a value with `DB::escape`).

### Changed

- `RawQuery::if()` uses `CASE WHEN` for broader database support.
- `RawQuery::concat()` uses `CONCAT_WS` with an empty separator.
- `RawQuery::divide()` casts the first operand to float and wraps the divisors with `nullIfZero()`.
- `RawQuery::sum()` accepts a single expression.

## [0.5.0] - 2026-10-23

### Added

- `RawQuery::sum()` and `RawQuery::plus()`.

### Changed

- `RawQuery` methods use strict `string` type hints for column names and values.

## [0.4.1] - 2026-10-23

### Fixed

- Wrong delimiter when concatenating relation strings in `RawQuery`.

## [0.4.0] - 2026-10-23

### Added

- `Arr` helper with `pull()` and `isKeysDontExist()`.
- `Str` helper with `concat()` and `ifTrue()`.

## [0.3.0] - 2026-10-23

### Added

- `UuidCast` to cast attributes to and from `UuidInterface`.

## [0.2.0] - 2026-10-23

### Added

- `Relation\Arrange` with `relation()` and `relations()` to build relation strings from model classes and column names.

### Fixed

- Typo in the package description.

## [0.1.0] - 2026-10-23

### Added

- Initial release for PHP 8.3+ and Laravel 11, 12 and 13.
- `EloquentKompactServiceProvider`, registered through Laravel package discovery.
- Column utilities: `Checker`, `ColumnListing`, `Config`, `Convert`, `Creator` and `Payload`.
- `RawQuery` static helpers for raw SQL expressions.
- Translations for `en` and `id`.

[Unreleased]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.26.0...HEAD
[0.26.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.25.0...v0.26.0
[0.25.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.24.0...v0.25.0
[0.24.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.23.0...v0.24.0
[0.23.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.22.0...v0.23.0
[0.22.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.21.0...v0.22.0
[0.21.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.20.3...v0.21.0
[0.20.3]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.20.2...v0.20.3
[0.20.2]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.20.1...v0.20.2
[0.20.1]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.20.0...v0.20.1
[0.20.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.19.1...v0.20.0
[0.19.1]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.19.0...v0.19.1
[0.19.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.18.0...v0.19.0
[0.18.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.17.0...v0.18.0
[0.17.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.16.0...v0.17.0
[0.16.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.15.0...v0.16.0
[0.15.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.14.0...v0.15.0
[0.14.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.13.0...v0.14.0
[0.13.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.12.0...v0.13.0
[0.12.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.11.0...v0.12.0
[0.11.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.10.0...v0.11.0
[0.10.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.9.0...v0.10.0
[0.9.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.8.0...v0.9.0
[0.8.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/jund-fauz/eloquent-kompact/releases/tag/v0.1.0
