# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.90.0] - 2026-11-04

### Added

- `assign()` also takes an array of `[column => value]`.

  ```php
  $order->assign(['status' => 'paid', 'note' => '-']);
  ```

### Changed

- **Breaking:** `isEmpty()`, `isEmptyAndDirty()`, `assignIfEmpty()` and `assignIfEmptyFrom()` are renamed to `isBlank()`, `isBlankAndDirty()`, `assignIfBlank()` and `assignIfBlankFrom()`.
- **Breaking:** these methods use Laravel's `blank()` instead of PHP's `empty()`, so `0`, `'0'` and `false` are no longer replaced, and a string of only spaces now is. `isBlank()` is now the opposite of `isFilled()`.
- The checks on lists inside the package use `blank()` too, with the same result.

### Tests

- Updated the tests of the renamed methods, and added tests for `0`, `'0'` and `false`, a string of only spaces and `assign()` with an array.

## [0.89.0] - 2026-11-04

### Added

- `qualify($column)`: a column with the table of the model, as a `RawQuery` that is already quoted and has the table prefix of the connection, so a chain can go on from it.

  ```php
  BlogPost::qualify('qty')->zeroIfNull()->sum()->as('total'); // SUM(COALESCE("blog_posts"."qty", 0)) AS total
  ```

- `foreignFrom()` has a second parameter for the prefix of the foreign key, like `foreign()`.

### Changed

- **Breaking:** `foreignFrom()` takes one model class and returns a `RawQuery`, quoted like `qualify()`, using the `$table` of that model. `pluck()` fails on it, so write the column as a string there.
- **Breaking:** `RawQuery::relations()` takes one model class, since a path with more of them is not a valid column. More classes are ignored without an error.

### Removed

- **Breaking:** `snakeTo()`. Use `qualify()`.

### Fixed

- `StaticH::getModel()` did not find the model when `path.model` ended with a backslash.

### Tests

- Updated the tests of `foreignFrom()` and `RawQuery::relations()`, and added tests for `qualify()`, including `havingRaw()` with a table prefix and another connection, and for `getModel()` with a trailing backslash.

## [0.88.0] - 2026-11-04

### Added

- `foreignFrom(...$modelClasses)`: the foreign key column of the model on another table.
- `snakeTo($column, ...$modelClasses)`: a column on another table.

  ```php
  User::foreignFrom(BlogPost::class);      // blog_posts.user_id
  User::snakeTo('title', BlogPost::class); // blog_posts.title
  ```

### Tests

- Added unit tests for `foreignFrom()` and `snakeTo()`, including a join with a table prefix.

## [0.87.0] - 2026-11-04

### Added

- `AndDirty` variants of the comparison methods, which also need the column to be dirty: `sameWithAndDirty()`, `notSameWithAndDirty()`, `sameWithOtherAndDirty()`, `sameWithOtherOrAndDirty()`, `notSameWithOtherAndDirty()`, `notSameWithOtherOrAndDirty()`, `matchAndDirty()`, `isMorphModelSameAndDirty()`, `isEmptyAndDirty()`, `isFilledAndDirty()`, `isNullAndDirty()` and `isNotNullAndDirty()`.

  ```php
  $order->sameWithAndDirty('status', 'paid'); // status has changed to 'paid' and is not saved yet
  ```

### Tests

- Added `AndDirtyTest`.

## [0.86.0] - 2026-11-04

### Added

- `getRelVia(...$relationClasses)`: the related model of nested relations, like `getVia()` without a column. It takes prefixed relations too.

  ```php
  $comment->getRelVia(Post::class, User::class); // $comment->post?->user
  ```

### Changed

- `getManyVia()` is no longer `@experimental`.

### Tests

- Added unit tests for `getRelVia()`.

## [0.85.0] - 2026-11-04

### Added

- `assign_as($value, ...$keys)`: `[key => value]` with the same value for every key.

### Tests

- Added unit tests for `assign_as()`.

## [0.84.0] - 2026-11-04

### Added

- Prefixed relations, written as `[prefix => model class]`, on `Convert::modelToRelation()`, `Arrange::relation()`, `Arrange::relations()`, `Builder\Relation::relation()`, `Builder\Relation::relations()`, `RawQuery::relations()`, `relationTo()`, `columnTo()`, `getVia()` and `getManyVia()`. Another array throws an `InvalidArgumentException`.

  ```php
  Convert::modelToRelation(['latest' => WorkOrderLedger::class]);       // latestWorkOrderLedger
  Convert::modelToRelation(['latest' => WorkOrderLedger::class], true, true); // latest_work_order_ledgers
  Arrange::relation('name', ['latest' => WorkOrderLedger::class]);      // latestWorkOrderLedger.name
  ```

- `singular(?string $prefix = null)` and `plural(?string $prefix = null)`: the relation name of the model with a prefix, e.g. `WorkOrderLedger::singular('latest')`.
- `array_key_values($array)`: `[key, value]` of an array with one item, or every key and then every value in one flat list.
- `array_normalize($list)`: the item of a list with one item, or the list as it is.

### Changed

- **Breaking:** the signatures of `relationTo()`, `columnTo()`, `getVia()`, `getManyVia()`, `singular()` and `plural()` changed for the prefix, so a class that overrides one of them with the old signature gives a fatal error.
- **Breaking:** `Arrange::relation()` and `Arrange::relations()` only flatten lists now. An array with string keys used to be flattened to its values: with one key it is now a prefixed relation, and with several keys it throws an `InvalidArgumentException`.

### Removed

- **Breaking:** `Helper\Arr::arrange()`, deprecated since 0.35.0. Use `str_concat()`.

### Tests

- Added `ConvertTest`, and unit tests for prefixed relations on `Arrange`, `Builder\Relation`, `RawQuery`, `StaticH`, `getVia()` and `getManyVia()`, and for `array_key_values()` and `array_normalize()`.

## [0.83.0] - 2026-11-04

### Added

- `Model\Builder`: Eloquent's `Builder` with `whereSameWith()`, `whereMorphType()`, `whereMorphTypeNot()`, `whereInRelation()` and a `pluck()` that returns a `Builder\Collection`. `KompactModel`, `KompactUser` and `KompactPivot` use it through `$builder`.
- `AdditionalQueries::randomId(...$filter)`: the id of a random row, with the arguments of `where()`, or `null` if there is none.
- `StaticH::baseName()`: the class name without its namespace.

### Changed

- **Breaking:** `whereSameWith()`, `whereMorphType()`, `whereMorphTypeNot()` and `whereInRelation()` moved from scopes on the `AdditionalQueries` trait to `Model\Builder`.
  - A model that only uses the trait has to set `protected static string $builder = Builder::class;`. Without it there is no error: Laravel reads the call as a dynamic where, e.g. `where same_with = ...`.
  - A scope of the same name on a model is no longer called, `hasNamedScope()` returns `false` for them, and they cannot be passed to `scopes()`.
  - They still group earlier conditions that contain an `or`, like a scope.
- **Breaking:** results of `Model\Collection` that are not models are now a `Builder\Collection` instead of Laravel's `Collection`: `pluck()`, `keys()`, `map()` to other values, `flatten()`, `countBy()`, etc. `Post::pluck()` too. So `get()` on them returns a `Str` for a string, and `=== 'value'` is `false`.

### Removed

- **Breaking:** `getDataFromRelation()` and `getDatasFromRelation()`, deprecated since 0.78.0. Use `getVia()` and `getManyVia()`.

### Tests

- Added `BuilderTest`, covering the grouping of an earlier `or`, a related model with and without the builder, and `pluck()`.
- Added unit tests for `randomId()`, `baseName()`, the collection of results that are not models, and the builder of `KompactModel`, `KompactUser` and `KompactPivot`.
- Removed the tests of the deprecated names.

## [0.82.0] - 2026-11-04

### Changed

- **Breaking:** `KompactUser` is now abstract, so it can only be extended.
- **Breaking:** `laravel/framework` (`^12.0|^13.0`) moved from `require-dev` to `require`, because `KompactUser` extends `Illuminate\Foundation\Auth\User`, which only that package has.
- `$collectionClass` moved from the `Kompact` trait to `KompactModel` and `KompactUser`. Another model that uses the `Kompact` trait has to set it itself to get `Model\Collection`.

### Fixed

- Fatal error on PHP 8.3 and 8.4 when loading `KompactModel` or `KompactUser` in 0.81.0: `Model and Kompact define the same property ($collectionClass)`. PHP before 8.5 binds a trait after the parent class, so the different default value of the trait's property was incompatible with `Model`.

### Tests

- Added unit tests for the abstract `KompactUser`, the collection of `KompactUser`, and a check that the `Kompact` trait declares no property of `Model`, since a run on PHP 8.5 alone does not show that fatal error.

## [0.81.0] - 2026-11-03

### Added

- `KompactUser`: Laravel's `Illuminate\Foundation\Auth\User` with everything `KompactModel` has, to extend for the user model.
- `Kompact` trait: every trait of `KompactModel`, `$useSoftDelete` and `flushKompactCache()`, shared by `KompactModel` and `KompactUser`.
- `Model\Collection`: Eloquent's `Collection` with the methods of `Builder\Collection`, sharing them through the `Builder\Trait\Kollection` trait.
- `AdditionalQueries::compact(...$columns)`: `[column => value]` for the given columns, from `model_compact()`.
- `sumAndCompact(...$columns)` on `Builder\Collection` and `Model\Collection`: the sum of each column, as `[column => sum]`.
- `Model\Collection::firstAndCompact(...$columns)`: `[column => value]` of the first model. An empty collection gives `null` for each column.

  ```php
  $user->compact('name', 'email');            // ['name' => 'Ana', 'email' => 'ana@example.com']
  Post::all()->sumAndCompact('views');        // ['views' => 120]
  Post::all()->firstAndCompact('id', 'name'); // ['id' => 1, 'name' => 'First']
  ```

### Changed

- **Breaking:** renamed the `DynamicFillableColumnDefinition` trait to `AutoFillable`, and `DynamicModelRelation` to `AutoRelation`, without keeping the old names.
- Results of `KompactModel`, `KompactUser` and `KompactPivot` now use `Model\Collection` instead of Eloquent's `Collection`, which it extends. As on `Builder\Collection`, `get()`, `before()` and `after()` return a string value or default as a `Str`, and an array as a collection. A model with `#[CollectedBy]` or its own `$collectionClass` keeps that class.
- `Filterable::getCountForFilter()` returns `?static` instead of `?KompactModel`, so it also works on a model that does not extend `KompactModel`.
- `php artisan migrate` also flushes the cache of `KompactUser`.

### Removed

- **Breaking:** the `RelationData` trait, deprecated in 0.80.0. Use `AdditionalQueries` instead.

### Fixed

- `$useSoftDelete = false` now turns soft deletes off for `delete()`. It used to fail on a table without a `deleted_at` column, and to fill that column instead of removing the row on a table with it.

### Tests

- Added unit tests for `KompactUser`, the `Kompact` trait, `Model\Collection`, `compact()`, `sumAndCompact()`, `firstAndCompact()` and `$useSoftDelete`.
- Renamed `RelationDataTest` to `GetViaTest`, without the tests of the removed trait.

## [0.80.0] - 2026-11-03

### Added

- `StaticH::foreign($prefix = null)`: the foreign key column of the model, from `Convert::modelToColumn()`. It always ends with `_id`, even for a model with another primary key, unlike Eloquent's `getForeignKey()`.

  ```php
  BlogPost::foreign();       // blog_post_id
  BlogPost::foreign('from'); // from_blog_post_id
  ```

### Changed

- Moved `getVia()`, `getManyVia()`, `getDataFromRelation()` and `getDatasFromRelation()` to `AdditionalQueries`. `KompactModel` and `KompactPivot` keep every method, and no longer use `RelationData`.
- `RelationData` now only uses `AdditionalQueries`, so a model using it also gets every other method of `AdditionalQueries`. A model can use both traits.

### Deprecated

- `RelationData` trait, use `AdditionalQueries` instead.

### Tests

- Added unit tests for `foreign()`, the deprecated trait and using both traits on a model.

## [0.79.0] - 2026-11-03

### Added

- `Builder\Collection::deleteNull()`: a new collection without the `null` values, keeping the keys. Blank strings, empty arrays, `0`, `'0'` and `false` are kept, unlike `filterFilled()`, which also removes blank strings and empty arrays or collections. The original collection is left as it is.

  ```php
  collect(['a', null, '', 0])->deleteNull()->all(); // [0 => 'a', 2 => '', 3 => 0]
  ```

### Tests

- Added unit tests for `deleteNull()`.

## [0.78.0] - 2026-11-03

### Added

- `RelationData::getVia()` and `RelationData::getManyVia()`: shorter names for `getDataFromRelation()` and `getDatasFromRelation()`, with the same parameters and results. `getManyVia()` is `@experimental`.

  ```php
  $comment->getVia('name', Post::class, User::class);                // $comment->post?->user?->name
  $comment->getManyVia(['name', 'email'], Post::class, User::class); // ['Ana', 'ana@example.com']
  ```

### Deprecated

- `RelationData::getDataFromRelation()`, use `getVia()` instead.
- `RelationData::getDatasFromRelation()`, use `getManyVia()` instead.

### Tests

- Added unit tests for `getVia()`, `getManyVia()` and the deprecated names.

## [0.77.0] - 2026-11-03

### Added

- `StaticH::columnTo($columnName, ...$modelClasses)`: like `relationTo()`, with a column at the end of the path, from `Arrange::relation()`. An empty column gives the same path as `relationTo()`.

  ```php
  User::columnTo('name');                   // user.name
  User::columnTo('name', 'roles');          // user.roles.name
  User::columnTo('title', BlogPost::class); // user.blogPost.title
  ```

### Changed

- `StaticH::relationTo()` now goes through `columnTo()`, so a model that overrides `columnTo()` changes `relationTo()` as well. Its results are otherwise the same.

### Documentation

- Documented `singular()`, `plural()`, `relationTo()` and `columnTo()` in the README.

### Tests

- Added unit tests for `columnTo()`.

## [0.76.0] - 2026-11-03

### Added

- `StaticH::relationTo(...$modelClasses)`: a relation path that starts with the singular name of the model, from `Arrange::relation()`, for use on another model that has a relation to it. Classes become singular names, and other strings are kept as they are, so write a plural relation as a string.

  ```php
  User::relationTo(BlogPost::class); // user.blogPost
  User::relationTo('roles');         // user.roles

  Order::with(User::relationTo('roles')); // order → user → roles
  ```

### Tests

- Added unit tests for `relationTo()`.

## [0.75.0] - 2026-11-03

### Added

- `StaticH::singular()` and `StaticH::plural()`: the relation name of the model in camelCase, from `Convert::modelToRelation()`. A subclass gives its own name, and a morph alias is not used.

  ```php
  BlogPost::singular(); // blogPost
  BlogPost::plural();   // blogPosts
  ```

### Tests

- Added unit tests for `singular()` and `plural()`.

## [0.74.0] - 2026-11-03

### Added

- `AdditionalQueries::whereInRelation()` scope: `whereHas()` with `whereIn()` on a column of the relation. Nested relations (`author.tags`) work, and an empty list of values finds nothing. On a `belongsToMany` relation, a column that is also on the pivot table has to be written with its table (`'tags.id'`), or the query fails with an ambiguous column error.

  ```php
  Order::whereInRelation('items', 'status', ['paid', 'sent'])->get();
  ```

### Tests

- Added unit tests for `whereInRelation()` on `hasMany`, `belongsTo`, `belongsToMany`, `morphTo`, self-referencing and nested relations.

## [0.73.0] - 2026-11-03

### Added

- `array_key_change()` helper function to rename an array key in place. The key moves to the end of the array and an existing `$newKey` is replaced. A missing `$oldKey` gives PHP's `Undefined array key` warning, which Laravel turns into an `ErrorException`. The same key for both leaves the array as it is.

  ```php
  use function JundFauz\EloquentKompact\array_key_change;

  $data = ['name' => 'Ana', 'age' => 20];
  array_key_change($data, 'name', 'full_name'); // ['age' => 20, 'full_name' => 'Ana']
  ```

### Tests

- Added unit tests for `array_key_change()`.

## [0.72.0] - 2026-11-02

### Added

- `AdditionalQueries::sameWithOtherOr()`, `notSameWithOther()` and `notSameWithOtherOr()`, the other forms of `sameWithOther()`. The comparison is strict and after casting, so objects such as the Carbon dates of `datetime` columns are never the same. They return `false` when no column is given.

  ```php
  $order->sameWithOtherOr($otherOrder, 'tenant_id', 'branch_id');    // at least one column is the same
  $order->notSameWithOther($otherOrder, 'tenant_id', 'branch_id');   // every column is different
  $order->notSameWithOtherOr($otherOrder, 'tenant_id', 'branch_id'); // at least one column is different
  ```

### Tests

- Added unit tests for `sameWithOtherOr()`, `notSameWithOther()` and `notSameWithOtherOr()`.

## [0.71.0] - 2026-11-02

### Added

- `AdditionalQueries::assignIfNull()` and `assignIfNullFrom()`, like `assignIfEmpty()` and `assignIfEmptyFrom()` but only a `null` attribute is replaced, so `''`, `0` and `false` are kept. `assignIfNull()` takes a column and a value, or an array of `[column => value]`.

  ```php
  $order->assignIfNull('note', '-');
  $order->assignIfNull(['note' => '-', 'status' => 'new']);
  $order->assignIfNullFrom($otherOrder, 'tenant_id', 'branch_id');
  ```

- `AdditionalQueries::isFilled()`, `isNull()` and `isNotNull()` to check an attribute. `isFilled()` uses Laravel's `filled()`, so `0`, `'0'` and `false` are filled, while `null`, blank strings and empty arrays are not. A model that declares its own method with one of these names must use the same signature.

  ```php
  $order->isFilled('note');
  $order->isNull('note');
  $order->isNotNull('note');
  ```

### Tests

- Added unit tests for `assignIfNull()`, `assignIfNullFrom()`, `isFilled()`, `isNull()` and `isNotNull()`.

## [0.70.0] - 2026-11-02

### Added

- `AdditionalQueries::assignIfEmpty()` also takes an array of `[column => value]`.

  ```php
  $order->assignIfEmpty(['note' => '-', 'status' => 'new']);
  ```

- `AdditionalQueries::isEmpty()` to check whether an attribute is PHP's `empty()`. A model that declares its own `isEmpty()` must use the same signature.

  ```php
  $order->isEmpty('note'); // true
  ```

### Tests

- Added unit tests for `assignIfEmpty()` with an array and for `isEmpty()`.

## [0.69.0] - 2026-11-02

### Added

- `AdditionalQueries::assign()`, `assignFrom()`, `assignIfEmpty()` and `assignIfEmptyFrom()` to set attributes from a value or from the same columns of another model, without saving. The `IfEmpty` variants only set an attribute that is PHP's `empty()`, so `null`, `''`, `'0'`, `0`, `false` and `[]` are replaced.

  ```php
  $order->assign('status', 'paid');
  $order->assignFrom($otherOrder, 'tenant_id', 'branch_id');
  $order->assignIfEmpty('note', '-');
  $order->assignIfEmptyFrom($otherOrder, 'tenant_id', 'branch_id');
  ```

### Tests

- Added unit tests for `assign()`, `assignFrom()`, `assignIfEmpty()` and `assignIfEmptyFrom()`.

## [0.68.0] - 2026-11-02

### Added

- `AdditionalQueries::notSameWith()`, the opposite of `sameWith()`: a shorthand for `$model->{$column} !== $value`, after casting.

  ```php
  $user->notSameWith('role', 'admin'); // true
  ```

### Tests

- Added unit tests for `notSameWith()`.

## [0.67.0] - 2026-11-02

### Added

- `higher_than()` and `lower_than()` helper functions, for `$a > $b` and `$a < $b`. They take an `int`, a `float` or a numeric string, such as the value of a `decimal` cast. A string that is not numeric is compared as text.

  ```php
  use function JundFauz\EloquentKompact\higher_than;
  use function JundFauz\EloquentKompact\lower_than;

  higher_than($order->weight_gram, '2.50'); // true for '2.75'
  lower_than(0.2, 0.9);                     // true
  ```

### Tests

- Added unit tests for `higher_than()` and `lower_than()`.

## [0.66.0] - 2026-11-02

### Added

- `same_with_one_of()` helper function to check whether a value is the same as one of the other values, with a strict `===` comparison like `same_with()`. It returns `false` when no other value is given.

  ```php
  use function JundFauz\EloquentKompact\same_with_one_of;

  same_with_one_of($status, 'paid', 'shipped'); // true
  same_with_one_of(1, '1');                     // false
  ```

### Tests

- Added unit tests for `same_with_one_of()`.

## [0.65.0] - 2026-11-02

### Added

- `AdditionalQueries::sameWithOther()` to check whether the given attributes of a model are the same as those of another model, using `sameWith()`: the comparison is strict and after casting, so objects such as the Carbon dates of `datetime` columns are never the same. It returns `false` when no column is given.

  ```php
  $order->sameWithOther($otherOrder, 'tenant_id', 'branch_id'); // true
  ```

### Tests

- Added unit tests for `sameWithOther()`.

## [0.64.0] - 2026-11-01

### Changed

- **BREAKING:** `Builder\Collection::get()`, `before()` and `after()` return a string value or default as a `Builder\Str` and an array as a `Collection`, like `getOr()`. Code that compares the result with `===` or passes it to a `string` parameter with `declare(strict_types=1)` must call `toString()`.

  ```php
  // 0.63
  collect(['name' => 'Ana'])->get('name');            // 'Ana'

  // 0.64
  collect(['name' => 'Ana'])->get('name')->toString(); // 'Ana'
  ```

### Removed

- The unused `collect()` import in `Builder\Str`.

### Tests

- Added unit tests for `Builder\Collection::get()`, `before()` and `after()`.

## [0.63.0] - 2026-11-01

### Added

- `RawQuery::recursiveUnion()`, the recursive CTE from any two queries that `recursive()` used to build, and the `RawQuery::limit()` chain method.

### Changed

- **BREAKING:** `RawQuery::recursive()` builds a recursive CTE over a self-referencing table from its parts: the columns, the anchor `WHERE` conditions and the `JOIN … ON` conditions of the recursive step. The outer `SELECT` columns come next and `UNION ALL` is the default. Pass two whole queries to `recursiveUnion()` instead, whose `$select` also comes before `$isUnionAll` and defaults to `UNION ALL`. Without `declare(strict_types=1)`, an old positional call does not fail on the `bool`, so update every call.

  ```php
  // 0.62
  RawQuery::recursive('tree', $anchor, $recursive, true, 'id');

  // 0.63
  RawQuery::recursiveUnion('tree', $anchor, $recursive, 'id');
  RawQuery::recursive('tree', 'subs', 'id', ['parent_id' => '?'], ['parent_id' => 'tree.id'], 'id');
  ```

### Tests

- Updated the `RawQuery::recursive()` unit tests and added unit tests for `recursiveUnion()` and `limit()`.

## [0.62.0] - 2026-11-01

### Added

- `Collection::getOrWithDefaultAsCollection()`, like `getOrWithDefault()` but the result is always a `Collection`: an array gives its items, a `null` gives an empty collection and any other value is wrapped in one.

  ```php
  collect(['nickname' => null])->getOrWithDefaultAsCollection('Guest', 'nickname')->all(); // ['Guest']
  ```

### Changed

- **BREAKING:** `Collection::getOr()` and `getOrWithDefault()` return a string value or default as a `Builder\Str` instead of a `string`, so the chain can go on with `Str` methods. Code that compares the result with `===` or passes it to a `string` parameter with `declare(strict_types=1)` must call `toString()`. A `Str` is an object, so an empty string is now truthy.

  ```php
  // 0.61
  collect(['name' => 'Ana'])->getOr('name');                       // 'Ana'

  // 0.62
  collect(['name' => 'Ana'])->getOr('name')->toString();           // 'Ana'
  collect(['name' => 'Ana'])->getOr('name')->upper()->toString();  // 'ANA'
  ```

### Tests

- Updated the `Builder\Collection` unit tests for string values returned as a `Str`, and added unit tests for `getOrWithDefaultAsCollection()`.

## [0.61.0] - 2026-11-01

### Added

- `collect()` helper function to create a `Builder\Collection`, like Laravel's global `collect()`. It lives in the `JundFauz\EloquentKompact` namespace, so Laravel's `collect()` is still used unless it is imported. Unlike Laravel's, it needs a value: call `collect([])` for an empty collection.

  ```php
  use function JundFauz\EloquentKompact\collect;

  collect(['nickname' => null, 'name' => 'Ana'])->getOr('nickname', 'name'); // 'Ana'
  ```

### Tests

- Added unit tests for `collect()`.

## [0.60.0] - 2026-11-01

### Added

- `array_get_or()` helper function to take the first value that is not `null`, in the order of the given keys. Keys may use dot notation, and missing keys are skipped. Only `null` is skipped: `''`, `0` and `false` are returned.

  ```php
  use function JundFauz\EloquentKompact\array_get_or;

  array_get_or(['nickname' => null, 'name' => 'Ana'], 'nickname', 'name'); // 'Ana'
  array_get_or(['user' => ['name' => 'Ana']], 'missing', 'user.name');      // 'Ana'
  ```

- `Builder\Collection`, extending Laravel's `Collection`, with `filterFilled()`, `getOr()` and `getOrWithDefault()`, built on `array_filter_filled()` and `array_get_or()`. An array value or default is returned as a `Collection`.

  ```php
  use JundFauz\EloquentKompact\Builder\Collection;

  $data = new Collection(['nickname' => null, 'name' => 'Ana', 'tags' => ['a', 'b']]);

  $data->getOr('nickname', 'name');             // 'Ana'
  $data->getOr('tags');                         // Collection ['a', 'b']
  $data->getOrWithDefault('Guest', 'nickname'); // 'Guest'
  ```

### Tests

- Added unit tests for `array_get_or()` and `Builder\Collection`.

## [0.59.0] - 2026-11-01

### Added

- `array_filter_filled()` helper function to keep only the values that are Laravel's `filled()`. `null`, blank strings (`''`, `'  '`) and empty arrays or collections are removed; `0`, `'0'` and `false` are kept. The keys are kept, and nested arrays are not filtered.

  ```php
  use function JundFauz\EloquentKompact\array_filter_filled;

  array_filter_filled(['a', '', null, 0, 'k' => ' ']); // [0 => 'a', 3 => 0]
  ```

### Tests

- Added unit tests for `array_filter_filled()`.

## [0.58.0] - 2026-11-01

### Added

- `array_pull_list()` helper function, like `array_pull()` but several keys give a list in the order of the given keys instead of `[key => value]`, to destructure the result. A single key still gives its value, and a missing key is `null`.

  ```php
  use function JundFauz\EloquentKompact\array_pull_list;

  [$name, $email] = array_pull_list($data, 'name', 'email'); // 'Ana', 'ana@example.com'
  array_pull_list($data, 'name');                            // 'Ana'
  ```

### Tests

- Added unit tests for `array_pull_list()`.

## [0.57.0] - 2026-10-31

### Fixed

- `Str::addWord()` and `addWordAtStart()` added a space next to an empty value or word: `Str::of()->addWord('a')` gave `' a'`. Empty words are now skipped: `'a'`.
- `Str::addPath()` and `addPathAtStart()` added a `/` next to an empty value or path, and kept the slashes around each part, so `Str::of('app/')->addPath('/Models')` gave `app///Models`. Empty paths are now skipped and the parts are joined with a single `/`: `app/Models`. The slashes at the start and end of the whole path are kept, so `Str::of('/var/')->addPath('www/')` gives `/var/www/`.

### Tests

- Added unit tests for `Str::addWord()`, `addWordAtStart()`, `addPath()` and `addPathAtStart()` with empty values and extra slashes.

## [0.56.0] - 2026-10-31

### Added

- `Str::addWordAtStart()` and `addPathAtStart()`, the counterparts of `addWord()` and `addPath()`, to prepend words with a space and paths with `/`.

  ```php
  Str::of('FROM users')->addWordAtStart('SELECT', 'id')->toString();    // SELECT id FROM users
  Str::of('User.php')->addPathAtStart('app', 'Models')->toString();    // app/Models/User.php
  ```

### Tests

- Added unit tests for `Str::addWordAtStart()` and `addPathAtStart()`.

## [0.55.0] - 2026-10-31

### Added

- `merge_as_array()` helper function to put every given value in one flat list. Nested arrays and collections are flattened with Laravel's `Arr::flatten()`, so their keys are dropped; `null` is kept and an empty array adds nothing.

  ```php
  use function JundFauz\EloquentKompact\merge_as_array;

  merge_as_array('a', ['b', ['c']], ['k' => 'd'], null); // ['a', 'b', 'c', 'd', null]
  ```

### Tests

- Added unit tests for `merge_as_array()`.

## [0.54.0] - 2026-10-31

### Added

- `RawQuery::asStr()`, the counterpart of `Str::asRawQuery()`, to continue with `Str` methods from the SQL.

  ```php
  RawQuery::select('id', 'users')->asStr()->addAtNewLine('LIMIT 10')->toString(); // "SELECT id FROM users\nLIMIT 10"
  ```

### Fixed

- The `Str` example of the 0.53.0 entry was split over two lines.

### Tests

- Added unit tests for `RawQuery::asStr()`.

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
  Str::of('UNION')->addWordIf($isUnionAll, 'ALL')->addAtNewLine('SELECT 1')->toString(); // "UNION ALL\nSELECT 1"
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

[Unreleased]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.90.0...HEAD
[0.90.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.89.0...v0.90.0
[0.89.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.88.0...v0.89.0
[0.88.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.87.0...v0.88.0
[0.87.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.86.0...v0.87.0
[0.86.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.85.0...v0.86.0
[0.85.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.84.0...v0.85.0
[0.84.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.83.0...v0.84.0
[0.83.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.82.0...v0.83.0
[0.82.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.81.0...v0.82.0
[0.81.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.80.0...v0.81.0
[0.80.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.79.0...v0.80.0
[0.79.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.78.0...v0.79.0
[0.78.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.77.0...v0.78.0
[0.77.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.76.0...v0.77.0
[0.76.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.75.0...v0.76.0
[0.75.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.74.0...v0.75.0
[0.74.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.73.0...v0.74.0
[0.73.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.72.0...v0.73.0
[0.72.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.71.0...v0.72.0
[0.71.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.70.0...v0.71.0
[0.70.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.69.0...v0.70.0
[0.69.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.68.0...v0.69.0
[0.68.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.67.0...v0.68.0
[0.67.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.66.0...v0.67.0
[0.66.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.65.0...v0.66.0
[0.65.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.64.0...v0.65.0
[0.64.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.63.0...v0.64.0
[0.63.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.62.0...v0.63.0
[0.62.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.61.0...v0.62.0
[0.61.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.60.0...v0.61.0
[0.60.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.59.0...v0.60.0
[0.59.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.58.0...v0.59.0
[0.58.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.57.0...v0.58.0
[0.57.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.56.0...v0.57.0
[0.56.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.55.0...v0.56.0
[0.55.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.54.0...v0.55.0
[0.54.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v0.53.0...v0.54.0
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
