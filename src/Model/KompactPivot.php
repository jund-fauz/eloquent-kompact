<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use JundFauz\EloquentKompact\Model\Traits\AdditionalQueries;
use JundFauz\EloquentKompact\Model\Traits\AutoFillable;
use JundFauz\EloquentKompact\Model\Traits\AutoRelation;
use JundFauz\EloquentKompact\Model\Traits\StaticH;
use JundFauz\EloquentKompact\Model\Traits\Validation;
use ReflectionClass;
use ReflectionException;
use function array_key_exists;
use function array_keys;
use function count;
use function in_array;
use function is_dir;
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

    /** @var array<string, list<class-string<self>>> */
    private static array $pivotsCache = [];

    /**
     * Model classes connected by this custom pivot.
     *
     * @return list<class-string<Model>>
     */
    public static function pivotBetween(): array
    {
        return static::$pivotBetween;
    }

    /**
     * Whether this pivot connects the two given model classes.
     *
     * @param class-string<Model> $first
     * @param class-string<Model> $second
     * @return bool
     */
    public static function connects(string $first, string $second): bool
    {
        return $first === $second
            ? count(array_keys(static::$pivotBetween, $first, true)) >= 2
            : in_array($first, static::$pivotBetween, true) && in_array($second, static::$pivotBetween, true);
    }

    /**
     * Concrete `KompactPivot` subclasses in `$namespace` that declare `$pivotBetween`.
     *
     * @param string $namespace
     * @param string|null $fallbackDir
     * @return list<class-string<self>>
     * @throws ReflectionException
     */
    public static function pivots(string $namespace, ?string $fallbackDir = null): array
    {
        $dir = full_path_on_app($namespace);
        if (! is_dir($dir) && $fallbackDir !== null) {
            $dir = $fallbackDir;
        }

        $cacheKey = "$namespace|$dir";
        if (array_key_exists($cacheKey, self::$pivotsCache)) {
            return self::$pivotsCache[$cacheKey];
        }

        $pivots = [];
        foreach (glob(path_concat($dir, '*.php')) ?: [] as $file) {
            $class = namespace_concat($namespace, pathinfo($file, PATHINFO_FILENAME));

            if (
                is_subclass_of($class, self::class)
                && ! (new ReflectionClass($class))->isAbstract()
                && count($class::pivotBetween()) >= 2
            ) {
                $pivots[] = $class;
            }
        }

        return self::$pivotsCache[$cacheKey] = $pivots;
    }

    public static function flushKompactCache(): void
    {
        self::$dynamicRelationCache = [];
        self::$AFCache = null;
        self::$pivotsCache = [];
    }
}
