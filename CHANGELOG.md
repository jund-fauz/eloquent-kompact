# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.53.0] - 2026-10-31

### Added

- `RawQuery::select()` and `RawQuery::recursive()` to start a `SELECT … FROM …` query and a recursive CTE, and the `where()` and `in()` chain methods.

  ```php
  RawQuery::create('id')->in(RawQuery::select('user_id', 'posts')->where('published_is'))->build();
  // id IN (SELECT user_id FROM posts WHERE published_is = ?)

  RawQuery::recursive('n', 'SELECT 1 AS x', 'SELECT x + 1 FROM n WHERE x < 5', true, 'x')->build();
  // WITH RECURSIVE n AS (SELECT 1 AS x UNION ALL SELECT x + 1 FROM n WHERE x < 5) SELECT x FROM n, on several lines
  ```

  `recursive()` selects `*` when no column is given. Its columns come after `$isUnionAll`, so pass `false` to give columns to a `UNION` query.

- `Str::addWord()`, `addIf()`, `addWordIf()`, `addAtNewLine()` and `newLineIfNotEmpty()`.

  ```php
  Str::of('UNION')->addWordIf($isUnionAll, 'ALL')->addAtNewLine('SELECT 1')->toString(); // "UNION ALL
SELECT 1"
  ```

- `array_wrap()` helper function, a shorthand for Laravel's `Arr::wrap()`.

### Changed

- `RawQuery::cast()` and `as()` also accept a `RawQuery`.
- `Str::newLine()` appends `NEW_LINE` (`"\n"`) instead of Laravel's `PHP_EOL`, so it gives the same result on every OS (`PHP_EOL` is `"\r\n"` on Windows). A count of 0 or less appends nothing instead of throwing a `ValueError`.

### Tests

- Added unit tests for `RawQuery::select()`, `recursive()`, `where()`, `in()`, `cast()` and `as()` with a `RawQuery`, the new `Str` methods and `array_wrap()`.

## [0.52.0] - 2026-10-31

### Added

- `stringify()` helper function to cast every value of an array to a `string`, nested arrays included, keeping the keys. `null` and `false` become `''`; a value that cannot be cast, such as a `Closure` or an enum, throws an `Error`. It turns a list of `Builder\Relation` into the strings Eloquent's `with()`, `load()` and `loadMissing()` accept.

  ```php
  use function JundFauz\EloquentKompact\stringify;

  stringify([Relation::arrange()->relations(Post::class)->relation(User::class)]); // ['posts.user']
  $user->loadMissing(stringify([$postsUser, $comments]));
  ```

### Tests

- Added unit tests for `stringify()`.

## [0.51.0] - 2026-10-31

### Added

- `Builder\Relation`, an immutable relation path builder started with `Relation::arrange($column)`, with `relation()` and `relations()`. Unlike nested `Arrange` calls, the chain is read from left to right, and the column always comes last.

  ```php
  // Arrange
  Arrange::relations(Arrange::relation('name', User::class), Post::class); // posts.user.name

  // Relation
  Relation::arrange('name')->relations(Post::class)->relation(User::class)->build(); // posts.user.name
  ```

  It is `Stringable` and a query `Expression`. Eloquent's `with()`, `load()` and `loadMissing()` do not accept it as it is: call `build()` or cast it to a `string`.

- `relation_concat()` helper function to join strings with `.`.

### Changed

- `AdditionalQueries::getMany()` declares its `array` return type. A model that overrides `getMany()` must declare it too.

### Tests

- Added unit tests for `Builder\Relation` and `relation_concat()`.

## [0.50.0] - 2026-10-31

### Added

- `AdditionalQueries::getMany()` to read several attributes of a model at once, as a list in the order of the given columns. A missing attribute is `null`.

  ```php
  $user->getMany('name', 'email'); // ['Ana', 'ana@example.com']
  ```

### Tests

- Added unit tests for `getMany()`.

## [0.49.0] - 2026-10-31

### Added

- `StaticH::morphClass()`, a static shorthand for `getMorphClass()`: the morph alias of the model if it is in `Relation::morphMap()`, or its class name.

  ```php
  Post::morphClass(); // 'App\Models\Post', or 'post' with Relation::morphMap(['post' => Post::class])
  ```

### Tests

- Added unit tests for `StaticH::tableName()` and `morphClass()`.

## [0.48.0] - 2026-10-31

### Added

- `add_tab()` helper function to indent a sentence with a number of tabs (1 by default). A count of 0 or less leaves the sentence as it is.

  ```php
  use function JundFauz\EloquentKompact\add_tab;

  add_tab('return $value;');    // '    return $value;'
  add_tab('return $value;', 2); // '        return $value;'
  ```

- `TAB` constant (4 spaces).

### Tests

- Added unit tests for `add_tab()` and `TAB`.

## [0.47.0] - 2026-10-31

### Added

- `Regex::startsWith()`, the counterpart of `endsWith()`, to anchor the pattern at the start of the string.

  ```php
  Regex::create()->startsWith('app'); // /^app/
  ```

### Changed

- **BREAKING:** The `AdditionalQueries::wheres()` scope was renamed to `whereSameWith()`. An old call does not throw right away: Laravel reads `wheres()` as a dynamic where on a column named `s`, so the query fails when it runs.

  ```php
  // 0.46
  Order::wheres($otherOrder, 'tenant_id', 'branch_id')->get();

  // 0.47
  Order::whereSameWith($otherOrder, 'tenant_id', 'branch_id')->get();
  ```

### Tests

- Added unit tests for `Regex`, including `startsWith()`.
- Added unit tests for `whereSameWith()`.

## [0.46.0] - 2026-10-31

### Added

- `AdditionalQueries::whereMorphTypeNot()` scope, the opposite of `whereMorphType()`: it leaves out the rows whose morph type column points to the model class, using its morph alias if the class is in `Relation::morphMap()`. Rows whose morph type is `NULL` are left out too.

  ```php
  Comment::whereMorphTypeNot('commentable', Post::class)->get(); // where not commentable_type = 'App\Models\Post'
  ```

### Removed

- **BREAKING:** Support for Laravel 11, which is out of security support. The package now requires Laravel 12 or 13 (`illuminate/support`, `illuminate/database` and `illuminate/console` `^12.0|^13.0`); Composer keeps apps on Laravel 11 at 0.45.
- Laravel 11 from the GitHub Actions test matrix.

### Tests

- Added unit tests for `whereMorphTypeNot()`.
- Removed the Laravel 11 skip from the `Str::plural()` `$prependCount` test.

## [0.45.0] - 2026-10-31

### Added

- `AdditionalQueries::isMorphModelSame()` to check whether a model's morph type attribute points to a model class, using its morph alias if the class is in `Relation::morphMap()`. The comparison is strict, like `sameWith()`.

  ```php
  $comment->isMorphModelSame('commentable', Post::class); // true
  ```

### Tests

- Added unit tests for `isMorphModelSame()`.

## [0.44.0] - 2026-10-30

### Added

- `Str::asRawQuery()` to start a `RawQuery` from the value.

  ```php
  Str::of('price')->asRawQuery()->sum()->build(); // SUM(price)
  ```

### Changed

- **BREAKING:** `RawQuery::and()` takes a single `string` condition instead of several `string|RawQuery` conditions. Extra arguments are silently ignored by PHP, so an old call with several conditions does not fail but drops every condition after the first; chain `and()` instead. A `RawQuery` argument is still turned into a string, except in a file with `declare(strict_types=1)`, where it throws a `TypeError`. `RawQuery::create()->and('a')` now gives ` AND a` instead of `a`.

  ```php
  // 0.43
  RawQuery::create('a = 1')->and('b = 2', 'c = 3');

  // 0.44
  RawQuery::create('a = 1')->and('b = 2')->and('c = 3');
  ```

- **BREAKING:** `RawQuery::flexibleCase()` and `RawQuery::relations()` return a `RawQuery` instead of a `string`, so the chain can go on. Code that uses the result as a string through Laravel's raw methods or string interpolation is not affected; code that needs a real `string` (comparison with `===`, a `string` parameter with `declare(strict_types=1)`) must call `build()`.

  ```php
  // 0.43
  $sql = RawQuery::flexibleCase('a = 1');

  // 0.44
  $sql = RawQuery::flexibleCase('a = 1')->build();
  RawQuery::relations('title', User::class)->count()->build(); // COUNT(users.title)
  ```

### Tests

- Updated the `RawQuery` unit tests for the single-condition `and()`, and for `flexibleCase()` and `relations()` returning a `RawQuery`.
- Added unit tests for `Str::asRawQuery()`.

## [0.43.0] - 2026-10-30

### Added

- `_type` column token (`Creator::Type`) for morph type columns, with `Creator::type()` and `Checker::isType()`.

  ```php
  Creator::type('commentable'); // commentable_type
  ```

- `AdditionalQueries::whereMorphType()` scope to filter a morph type column by a model class, using its morph alias if the class is in `Relation::morphMap()`.

  ```php
  Comment::whereMorphType('commentable', Post::class)->get(); // where commentable_type = 'App\Models\Post'
  ```

### Changed

- Columns ending in `_type` are now read as having a token: `Creator::getColumnType('blood_type')` returns `_type` instead of `''`, and `Creator::getPlainColumnName('blood_type')` returns `blood` instead of `blood_type`. No cast is added for them.

### Tests

- Added unit tests for the `_type` token and `whereMorphType()`.

## [0.42.0] - 2026-10-30

### Added

- `array_pull()` helper function, a shorthand for `Arr::pullMany()`: it removes the given keys from the array and returns the value of a single key, or `[key => value]` for several keys. A missing key is `null`.

  ```php
  use function JundFauz\EloquentKompact\array_pull;

  array_pull($data, 'name');          // 'Ana'
  array_pull($data, 'name', 'email'); // ['name' => 'Ana', 'email' => 'ana@example.com']
  ```

### Tests

- Added unit tests for `array_pull()`.

## [0.41.0] - 2026-10-30

### Added

- `Str::addPath()` to append paths with `/`, and `Str::appPath()` to resolve the value inside the app directory with Laravel's `app_path()`.

  ```php
  Str::of('Models')->addPath('User.php')->appPath()->toString(); // {app_path}/Models/User.php
  ```

### Changed

- `Str::of()` and the `Str` methods added by the package return `static` instead of `self`, so a subclass of `Str` keeps its own class through the chain.

### Tests

- Added unit tests for `Str::addPath()`, `appPath()` and subclasses of `Str`.

## [0.40.0] - 2026-10-30

### Added

- `model_compact()` helper function to take the given attributes of a model as `[column => value]`, after casting. A missing attribute is `null`, and a loaded relation is converted to an array.

  ```php
  use function JundFauz\EloquentKompact\model_compact;

  model_compact($user, 'name', 'active_is'); // ['name' => 'Ana', 'active_is' => true]
  ```

### Tests

- Added unit tests for `model_compact()`.

## [0.39.1] - 2026-10-30

### Fixed

- The mixin classes generated by `php artisan kompact:ide-helper` had the same short name as their model (`EloquentKompact\IdeHelper\App\Models\User`), so IDEs could offer them as a second `User` class. They are now prefixed with `_EK_` (`EloquentKompact\IdeHelper\App\Models\_EK_User`); run the command again to regenerate the file and `ide.json`.
- The generated `_ide_helper_kompact.php` is now marked `/** @noinspection all */`, so PhpStorm does not report inspections in it.
- The closing backtick was missing in the "Generated by `php artisan kompact:ide-helper`" comment of the generated file.

### Tests

- Added unit tests for the mixin names, mixin blocks and file header generated by `kompact:ide-helper`.

## [0.39.0] - 2026-10-30

### Changed

- **BREAKING:** `Arrange::relation()` and `Arrange::relations()` return a `string` again instead of a `Builder\Str`, as before 0.35.0. Code that chains `Str` methods on the result must wrap it first:

  ```php
  // 0.38
  Arrange::relation('name', User::class)->upper();

  // 0.39
  Str::of(Arrange::relation('name', User::class))->upper();
  ```

  Code that only uses the result as a string is not affected.

### Tests

- Added unit tests for `Arrange::relation()` and `relations()`, and for `RawQuery::relations()` returning a `string`.

## [0.38.1] - 2026-10-30

### Fixed

- `Str::addNamespace()` kept the backslashes around each part, so `Str::of('App\\')->addNamespace('\\Models')` gave `App\\\Models`. It now trims them like `addNamespaceAtStart()`: `App\Models`.
- Typo in the `php artisan kompact:ide-helper` summary (`writtent` → `written`).

### Tests

- Added unit tests for `Str::addNamespace()` with leading and trailing backslashes.

## [0.38.0] - 2026-10-30

### Added

- `Str::addNamespace()` to append namespaces with `\`.

  ```php
  Str::of('App')->addNamespace('Models', 'User')->toString(); // App\Models\User
  ```

### Fixed

- `php artisan kompact:ide-helper` printed its summary in Indonesian; it is now in English.

### Tests

- Added unit tests for `Str::addNamespace()`.

## [0.37.0] - 2026-10-30

### Removed

- **BREAKING:** The `column.to_be_replaced` config. `Creator` always puts the column type token at the end of the column name (`user_id`), so a config that set `'/^/'` for the prefix scheme now silently gets the suffix scheme. A published config may keep the `column` key; it is ignored.

### Changed

- `Creator` no longer reads the config, so it works without a booted Laravel app.

### Tests

- Replaced the `Creator` tests for the `column.to_be_replaced` config with a test that the suffix scheme is always used.

## [0.36.0] - 2026-10-30

### Added

- `Str::removeStart()` and `removeEnd()` to remove a string only at the start or end, as `StrB` had before 0.35.0.

  ```php
  Str::of('app/Models/app')->removeStart('app/')->toString(); // Models/app
  Str::of('app/Models/app')->removeEnd('/app')->toString();   // app/Models
  ```

- README sections for every feature: the column naming convention, `KompactModel` and its traits, the IDE helper, the migration helpers, `Str`, `BoolB`, `Regex`, the helper functions and the other helpers.

### Fixed

- The README said `RawQuery` methods change the same instance; they return a new one since 0.15.0.

### Tests

- Added unit tests for `Str::removeStart()` and `removeEnd()`.

## [0.35.0] - 2026-10-30

### Added

- `php artisan kompact:ide-helper` to generate `_ide_helper_kompact.php` and register it in `ide.json`, so IDEs autocomplete the relations resolved by `DynamicModelRelation`. Set `KOMPACT_IDE_HELPER=true` to run it automatically after `php artisan migrate` in the local environment.
- `column.to_be_replaced` config to choose where `Creator` puts the column type token. The default `Regex::create()->endsWith()` (`/$/`) keeps the suffix scheme (`user_id`); `/^/` switches to a prefix scheme.
- `Builder\Str`, extending Laravel's `Stringable`, with `replaceByRegex()`, `removeByRegex()`, `join()`, `concat()`, `add()`, `addAtStart()`, `addNamespaceAtStart()`, `emptyIfFalse()`, `relation()`, `sameWith()` and `notSameWith()`. `sameWith()` and `notSameWith()` compare strictly, unwrapping any Laravel `Stringable`.

  ```php
  Str::of('App/Models')->removeByRegex('/^app\//i')->toString(); // Models
  Str::of('user')->relation()->sameWith('user_id');             // true
  ```

- `word_concat()`, `schema_concat()`, `write_line()`, `comment()`, `namespace_concat()`, `path_concat()`, `full_path_on_app()`, `array_flat()`, `str_before_last()`, `str_lower()`, `str_upper()`, `same_with()`, `not_same_with()` and `not()` helper functions. `str_concat()` and the other joining functions also accept arrays, which are flattened.
- `NEW_LINE`, `DISTANCE_LINE`, `NAMESPACE_DELIMITER` and `PATH_DELIMITER` constants.
- Dependency on `illuminate/console`.

### Changed

- **BREAKING:** `Builder\StrB` was replaced by `Builder\Str`, which extends Laravel's `Stringable` instead of the internal `Builder`. Every Laravel `Stringable` method is available, but:
  - `StrB::initiate()` is now `Str::of()`, and `build()` is now `toString()`, `value()` or a `(string)` cast.
  - The third argument of `replace()` and the second argument of `remove()` now mean case sensitive, not regex, so an old call with `true` no longer fails but replaces the pattern as plain text. Use `replaceByRegex()` and `removeByRegex()` instead.
  - `removeStart()` and `removeEnd()` were removed; use `replaceStart($search, '')` and `replaceEnd($search, '')` instead.

  ```php
  // 0.34
  StrB::initiate('App/Models')->replace('/^app\//i', '', true)->build();

  // 0.35
  Str::of('App/Models')->replaceByRegex('/^app\//i', '')->toString();
  ```

- **BREAKING:** The helper functions are no longer global; they live in the `JundFauz\EloquentKompact` namespace, as do the new constants.

  ```php
  use function JundFauz\EloquentKompact\is_not_null;
  use function JundFauz\EloquentKompact\str_concat;
  ```

- **BREAKING:** `Helper\Path` is marked `@internal`, and `Path::concat()`, `namespaceConcat()` and `fullPathOnApp()` were removed. A published `config/eloquent-kompact.php` that uses them must be updated:

  ```php
  // 0.34
  use JundFauz\EloquentKompact\Helper\Path;
  'model' => Path::namespaceConcat('App', 'Models'),

  // 0.35
  use function JundFauz\EloquentKompact\namespace_concat;
  'model' => namespace_concat('App', 'Models'),
  ```

  `path_concat()` and `full_path_on_app()` replace `Path::concat()` and `Path::fullPathOnApp()`.

- **BREAKING:** `Creator::getPlainColumnName()` returns a `Builder\Str` instead of a `string`.
- **BREAKING:** `Arrange::relation()` and `Arrange::relations()` return a `Builder\Str` instead of a `string`.
- `Creator` reads its token position from the `column.to_be_replaced` config instead of a constant, so it needs a booted Laravel app.

### Deprecated

- `Arr::arrange()`; use `str_concat()` instead.

### Tests

- Added unit tests for `Builder\Str`, `Creator` and the helper functions and constants.
- Replaced the `StrB` unit tests with `Str` unit tests.

## [0.34.0] - 2026-10-29

### Added

- `StrB::lower()` and `upper()`, backed by Laravel's `Str`, multibyte safe.

  ```php
  StrB::initiate('BlogPost')->lower()->build(); // blogpost
  ```

- Global `str_concat()` helper function to join strings without a delimiter.

  ```php
  str_concat('a', 'b', 'c'); // abc
  ```

### Changed

- **BREAKING:** The `ErrorThrow` trait moved from `Internal\Error` to `Helper\Error` and is no longer `@internal`.

  ```php
  // 0.33
  use JundFauz\EloquentKompact\Internal\Error\ErrorThrow;

  // 0.34
  use JundFauz\EloquentKompact\Helper\Error\ErrorThrow;
  ```

### Removed

- **BREAKING:** `Helper\Str`, deprecated since 0.22.0. Use `Builder\StrB` for the package methods and `Illuminate\Support\Str` for the Laravel methods.

  ```php
  // 0.33
  Str::concat('a', 'b');
  Str::ifTrue($condition, 'a');

  // 0.34
  StrB::initiate('a')->concat('b')->build(); // or str_concat('a', 'b')
  StrB::initiate('a')->emptyIfFalse($condition)->build();
  ```

- **BREAKING:** `Helper\Compare`, deprecated since 0.30.0; use `BoolB::compare()` instead.

  ```php
  // 0.33
  Compare::of($a)->sameWith($b);

  // 0.34
  BoolB::compare($a)->sameWith($b)->result;
  ```

- **BREAKING:** `same_with()`, deprecated since 0.27.0; use `BoolB::compare()->sameWith()` instead.

### Tests

- Added unit tests for `StrB::lower()`, `upper()` and `str_concat()`.
- Removed the tests for `Helper\Compare` and `same_with()`.

## [0.33.0] - 2026-10-29

### Changed

- `StrB::emptyIfFalse()` accepts any value instead of only a `bool`, and empties the string when the value is falsy (`false`, `0`, `''`, `'0'`, `null`, `[]`).

  ```php
  StrB::initiate('a')->emptyIfFalse($user->nickname)->build();
  ```

### Tests

- Added unit tests for `StrB::emptyIfFalse()` with non-boolean values.

## [0.32.0] - 2026-10-29

### Added

- `StrB::before()`, `beforeLast()`, `after()`, `afterLast()` and `between()`, backed by Laravel's `Str`, to take the part of a string before, after or between given strings. If a given string is not found or empty, the whole string is kept. `between()` takes the part from the first `$from` to the last `$to`.

  ```php
  StrB::initiate('App\Models\User')->afterLast('\\')->build();          // User
  StrB::initiate('App\Models\User')->after('\\')->beforeLast('\\')->build(); // Models
  StrB::initiate('[a] [b]')->between('[', ']')->build();                // a] [b
  ```

### Tests

- Added unit tests for `StrB::before()`, `beforeLast()`, `after()`, `afterLast()` and `between()`.

## [0.31.0] - 2026-10-29

### Added

- `BoolB::andGroup()` and `orGroup()` to combine with another `BoolB` (or a `bool`) as a group, instead of chaining from left to right.

  ```php
  // $isAdmin && ($isActive || $isVerified)
  BoolB::compare($user->role)->sameWith('admin')
      ->andGroup(BoolB::compare($user->status)->sameWith('active')->or($user->verified_at)->notSameWith(null))
      ->result;
  ```

### Changed

- `BoolB::initiate()` also accepts a `BoolB`, and returns it as-is.

### Tests

- Added unit tests for `BoolB::andGroup()`, `orGroup()` and `initiate()` with a `BoolB`.

## [0.30.0] - 2026-10-29

### Added

- `BoolB::compare()` to start a boolean from a strict comparison, with `sameWith()` and `notSameWith()`.

  ```php
  BoolB::compare($status)->sameWith('active')->result;
  ```

### Changed

- **BREAKING:** `BoolB::and()` and `or()` take a value to compare instead of a `bool`, and return a comparison to finish with `sameWith()` or `notSameWith()`. Chains are still evaluated from left to right.

  ```php
  // 0.29
  BoolB::initiate($user->is_active)->and($user->role === 'admin')->result;

  // 0.30
  BoolB::initiate($user->is_active)->and($user->role)->sameWith('admin')->result;
  ```

- **BREAKING:** `BoolB::set()` is now `private`.
- **BREAKING:** The `set()` method of the `Internal\Concerns\Builder` base class is now `protected`, so `RawQuery::set()`, `Regex::set()` and `StrB::set()` can no longer be called from outside.

### Removed

- **BREAKING:** `BoolB::create()`, deprecated since 0.26.0; use `BoolB::initiate()` instead.

### Deprecated

- `Helper\Compare`; use `BoolB::compare()` instead.

  ```php
  // 0.29
  Compare::of($a)->sameWith($b);

  // 0.30
  BoolB::compare($a)->sameWith($b)->result;
  ```

### Tests

- Updated the `BoolB` unit tests for `compare()`, `sameWith()` and `notSameWith()` chains.

## [0.29.0] - 2026-10-28

### Added

- `StrB::replaceStart()` and `replaceEnd()`, backed by Laravel's `Str`, to replace a string only at the start or end.
- `StrB::removeStart()` and `removeEnd()` to remove a string only at the start or end.

  ```php
  StrB::initiate('app/Models/app')->removeStart('app/')->build(); // Models/app
  StrB::initiate('app/Models/app')->removeEnd('/app')->build();   // app/Models
  ```

### Tests

- Added unit tests for `StrB::replaceStart()`, `replaceEnd()`, `removeStart()` and `removeEnd()`.

## [0.28.0] - 2026-10-28

### Added

- `StrB::concat()` to append strings without a delimiter.

  ```php
  StrB::initiate('a')->concat('b', 'c')->build(); // abc
  ```

### Changed

- The `Str::concat()` deprecation now points to `StrB::concat()` instead of `StrB::join()`.

  ```php
  // 0.27
  StrB::initiate('a')->join('', 'b', 'c')->build();

  // 0.28
  StrB::initiate('a')->concat('b', 'c')->build();
  ```

### Tests

- Added unit tests for `StrB::concat()`.

## [0.27.0] - 2026-10-28

### Added

- `Helper\Compare`, started with `Compare::of()`, with `sameWith()` and `notSameWith()` for strict `===` / `!==` comparison.

  ```php
  Compare::of($status)->sameWith('active');
  ```

### Deprecated

- `same_with()`; use `Compare::of()->sameWith()` instead.

  ```php
  // 0.26
  same_with($a, $b);

  // 0.27
  Compare::of($a)->sameWith($b);
  ```

### Tests

- Added unit tests for `Compare`.

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

[Unreleased]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.53.0...HEAD
[0.53.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.52.0...v0.53.0
[0.52.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.51.0...v0.52.0
[0.51.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.50.0...v0.51.0
[0.50.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.49.0...v0.50.0
[0.49.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.48.0...v0.49.0
[0.48.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.47.0...v0.48.0
[0.47.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.46.0...v0.47.0
[0.46.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.45.0...v0.46.0
[0.45.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.44.0...v0.45.0
[0.44.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.43.0...v0.44.0
[0.43.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.42.0...v0.43.0
[0.42.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.41.0...v0.42.0
[0.41.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.40.0...v0.41.0
[0.40.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.39.1...v0.40.0
[0.39.1]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.39.0...v0.39.1
[0.39.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.38.1...v0.39.0
[0.38.1]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.38.0...v0.38.1
[0.38.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.37.0...v0.38.0
[0.37.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.36.0...v0.37.0
[0.36.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.35.0...v0.36.0
[0.35.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.34.0...v0.35.0
[0.34.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.33.0...v0.34.0
[0.33.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.32.0...v0.33.0
[0.32.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.31.0...v0.32.0
[0.31.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.30.0...v0.31.0
[0.30.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.29.0...v0.30.0
[0.29.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.28.0...v0.29.0
[0.28.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.27.0...v0.28.0
[0.27.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.26.0...v0.27.0
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
