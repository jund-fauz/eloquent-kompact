<?php

namespace JundFauz\EloquentKompact\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Centralized in-memory cache registry for Kompact model traits and custom pivots.
 */
final class ModelCache
{
    /**
     * Cached fillable column lists keyed by model FQCN.
     *
     * @var array<class-string<Model>, array<int, string>>
     * @internal
     */
    public static array $fillable = [];

    /**
     * Cached inferred cast maps keyed by model FQCN.
     *
     * @var array<class-string<Model>, array<string, string>>
     * @internal
     */
    public static array $casts = [];

    /**
     * Cached dynamic relation configurations keyed by model FQCN and relation name.
     *
     * @var array<class-string<Model>, array<string, array<string, mixed>|false>>
     * @internal
     */
    public static array $relations = [];

    /**
     * Cached discovered custom `KompactPivot` classes keyed by namespace and directory.
     *
     * @var array<string, list<class-string<KompactPivot>>>
     * @internal
     */
    public static array $pivots = [];

    /**
     * Clears all in-memory caches for fillable columns, auto-casts, dynamic relations, and custom pivots.
     *
     * @return void
     */
    public static function flush(): void
    {
        self::$fillable = [];
        self::$casts = [];
        self::$relations = [];
        self::$pivots = [];
    }
}
