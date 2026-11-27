<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Column\ColumnCheck;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\AutoFillable;
use JundFauz\EloquentKompact\Model\Traits\AutoRelation;
use JundFauz\EloquentKompact\Model\Traits\StaticH;
use JundFauz\EloquentKompact\Model\Traits\Validation;
use ReflectionClass;
use ReflectionException;
use function array_column;
use function array_key_exists;
use function array_keys;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function is_dir;
use function is_int;
use function is_string;
use function is_subclass_of;
use function JundFauz\EloquentKompact\full_path_on_app;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\path_concat;

abstract class KompactPivot extends Pivot
{
    use AdditionalQueries, AutoFillable, AutoRelation, StaticH, SoftDeletes, Validation;

    protected static string $collectionClass = Collection::class;
    protected static string $builder = Builder::class;
    protected static array $pivotBetween = [];

    /**
     * Raw `$pivotBetween` definition declared on this custom pivot.
     *
     * @return array<int|string, class-string<Model>|array{0: class-string<Model>, 1: string}>
     * @final
     */
    public static function pivotBetween(): array
    {
        return static::$pivotBetween;
    }

    /**
     * Normalized endpoint descriptors resolved from `$pivotBetween`.
     *
     * @return list<array{class: class-string<Model>, foreign_key: ?string, relations: list<string>}>
     * @final
     */
    public static function pivotEndpoints(): array
    {
        $endpoints = [];

        foreach (static::$pivotBetween as $key => $spec) {
            if (is_int($key) && is_string($spec)) {
                $endpoints[] = [
                    'class' => $spec,
                    'foreign_key' => null,
                    'relations' => [Convert::modelToRelation($spec)],
                ];
            } elseif (is_string($key) && is_string($spec)) {
                $isRelationColumn = ColumnCheck::isRelation($key);
                $fk = $isRelationColumn ? $key : ColumnCreator::relation(Str::snake($key));
                $stemRelation = (string) ColumnCreator::getPlainColumnName($fk)->camel();
                $keyRelation = Str::camel($isRelationColumn ? (string) ColumnCreator::getPlainColumnName($key) : $key);

                $endpoints[] = [
                    'class' => $spec,
                    'foreign_key' => $fk,
                    'relations' => array_values(array_unique([$keyRelation, $stemRelation])),
                ];
            } elseif (is_array($spec) && count($spec) >= 2) {
                [$modelClass, $second] = array_values($spec);

                if (is_int($key)) {
                    $fk = ColumnCheck::isRelation($second) ? $second : ColumnCreator::relation(Str::snake($second));
                    $relations = [(string) ColumnCreator::getPlainColumnName($fk)->camel()];
                } elseif (ColumnCheck::isRelation($key)) {
                    $fk = $key;
                    $alias = Str::camel($second);
                    $stem = (string) ColumnCreator::getPlainColumnName($fk)->camel();
                    $relations = array_values(array_unique([$alias, $stem]));
                } else {
                    $fk = ColumnCheck::isRelation($second) ? $second : ColumnCreator::relation(Str::snake($second));
                    $alias = Str::camel($key);
                    $stem = (string) ColumnCreator::getPlainColumnName($fk)->camel();
                    $relations = array_values(array_unique([$alias, $stem]));
                }

                $endpoints[] = [
                    'class' => $modelClass,
                    'foreign_key' => $fk,
                    'relations' => $relations,
                ];
            }
        }

        return $endpoints;
    }

    /**
     * Whether this pivot connects the two given model classes.
     *
     * @param class-string<Model> $first
     * @param class-string<Model> $second
     * @return bool
     * @final
     */
    public static function connects(string $first, string $second): bool
    {
        $classes = array_column(static::pivotEndpoints(), 'class');

        return $first === $second
            ? count(array_keys($classes, $first, true)) >= 2
            : in_array($first, $classes, true) && in_array($second, $classes, true);
    }

    /**
     * Concrete `KompactPivot` subclasses in `$namespace` that declare `$pivotBetween`.
     *
     * @param string $namespace
     * @param string|null $fallbackDir
     * @return list<class-string<self>>
     * @throws ReflectionException
     * @final
     */
    public static function pivots(string $namespace, ?string $fallbackDir = null): array
    {
        $dir = full_path_on_app($namespace);
        if (! is_dir($dir) && $fallbackDir !== null) {
            $dir = $fallbackDir;
        }

        $cacheKey = "$namespace|$dir";
        if (array_key_exists($cacheKey, ModelCache::$pivots)) {
            return ModelCache::$pivots[$cacheKey];
        }

        $pivots = [];
        foreach (glob(path_concat($dir, '*.php')) ?: [] as $file) {
            $class = namespace_concat($namespace, pathinfo($file, PATHINFO_FILENAME));

            if (
                is_subclass_of($class, self::class)
                && ! (new ReflectionClass($class))->isAbstract()
                && count($class::pivotEndpoints()) >= 2
            ) {
                $pivots[] = $class;
            }
        }

        return ModelCache::$pivots[$cacheKey] = $pivots;
    }

    /**
     * Clears static caches for dynamic relations, auto-fillables, and discovered custom pivots.
     *
     * @return void
     * @final
     */
    public static function flushKompactCache(): void
    {
        ModelCache::flush();
    }
}
