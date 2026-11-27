<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use BadMethodCallException;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\ModelCache;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_values;
use function count;
use function in_array;
use function is_subclass_of;
use function JundFauz\EloquentKompact\namespace_concat;
use function reset;
use function str_starts_with;

trait AutoRelation
{
    protected static bool $includeSoftDeletedData = false;

    /**
     * Determine whether dynamic relations targeting this model should include soft-deleted records.
     *
     * @return bool
     * @final
     */
    public static function includesSoftDeletedData(): bool
    {
        return static::$includeSoftDeletedData;
    }

    /**
     * Determine whether dynamic relations targeting `$relatedClass` should include soft-deleted records.
     *
     * @param class-string<Model> $relatedClass
     * @return bool
     * @throws ReflectionException
     */
    protected static function shouldIncludeSoftDeletedData(string $relatedClass): bool
    {
        return method_exists($relatedClass, 'includesSoftDeletedData')
            ? $relatedClass::includesSoftDeletedData()
            : (property_exists($relatedClass, 'includeSoftDeletedData')
                && (bool) (new ReflectionClass($relatedClass))->getStaticPropertyValue('includeSoftDeletedData', false));
    }

    /**
     * Resolves the single `morphTo` relation name declared on `$className` when its `*_type` column exists.
     *
     * @param class-string<Model> $className
     * @param string $table
     * @return string|null
     * @throws ReflectionException
     */
    protected static function resolveMorphName(string $className, string $table): ?string
    {
        $names = [];

        foreach ((new ReflectionClass($className))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ((string)$method->getReturnType() === MorphTo::class) {
                $names[] = $method->getName();
            }
        }

        return count($names) === 1 && ColumnListing::has($table, "$names[0]_type")
            ? $names[0]
            : null;
    }

    /**
     * Resolves the foreign key column on `$pivotClass`'s `$table` referencing `static::class`, optionally filtered by `$prefix`.
     *
     * @param class-string<KompactPivot> $pivotClass
     * @param string $table
     * @param string $prefix
     * @return string|null
     */
    protected static function resolvePivotForeignKey(string $pivotClass, string $table, string $prefix): ?string
    {
        $valid = [];
        foreach ($pivotClass::pivotEndpoints() as $endpoint) {
            if ($endpoint['class'] !== static::class) {
                continue;
            }

            $fk = $endpoint['foreign_key'] ?? Convert::modelToColumn(static::class);
            if (ColumnListing::has($table, $fk)) {
                $valid[] = ['fk' => $fk, 'relations' => $endpoint['relations']];
            }
        }

        if ($valid === []) {
            return null;
        }

        if ($prefix !== '') {
            $camelPrefix = Str::camel($prefix);
            $prefixFk = ColumnCreator::relation($prefix);
            $matched = array_values(array_filter(
                $valid,
                static fn (array $ep): bool => in_array($camelPrefix, $ep['relations'], true) || $ep['fk'] === $prefixFk
            ));

            return count($matched) === 1 ? $matched[0]['fk'] : null;
        }

        if (count($valid) === 1) {
            return $valid[0]['fk'];
        }

        $pivotSnake = Str::snake(class_basename($pivotClass));
        $matched = [];
        foreach ($valid as $ep) {
            foreach ($ep['relations'] as $rel) {
                if (str_starts_with($pivotSnake, Str::snake($rel).'_')) {
                    $matched[] = $ep['fk'];
                    break;
                }
            }
        }

        return count($matched) === 1 ? $matched[0] : null;
    }

    /**
     * Resolves a custom `KompactPivot` connecting `static::class` and `$relatedClass`, including mapped endpoint foreign keys.
     *
     * @param string $modelNamespace
     * @param class-string<Model> $relatedClass
     * @param string $foreignKey
     * @param string $relatedKey
     * @param string $prefix
     * @return array{0: class-string<KompactPivot>, 1: string, 2: string, 3: string}|null
     * @throws ReflectionException
     */
    protected static function resolveCustomPivot(
        string $modelNamespace,
        string $relatedClass,
        string $foreignKey,
        string $relatedKey,
        string $prefix = ''
    ): ?array {
        $fileName = (new ReflectionClass($relatedClass))->getFileName();
        $fallbackDir = $fileName ? dirname($fileName) : null;

        foreach (KompactPivot::pivots($modelNamespace, $fallbackDir) as $pivotClass) {
            if (! $pivotClass::connects(static::class, $relatedClass)) {
                continue;
            }

            $pivotTable = (new $pivotClass)->getTable();
            if (! Schema::hasTable($pivotTable)) {
                continue;
            }

            if (ColumnListing::has($pivotTable, $foreignKey) && ColumnListing::has($pivotTable, $relatedKey)) {
                return [$pivotClass, $pivotTable, $foreignKey, $relatedKey];
            }

            $endpoints = $pivotClass::pivotEndpoints();
            $camelPrefix = $prefix !== '' ? Str::camel($prefix) : '';
            $prefixFk = $prefix !== '' ? ColumnCreator::relation($prefix) : '';
            $matchesPrefix = static fn (array $ep, string $fk): bool => in_array($camelPrefix, $ep['relations'], true) || $fk === $prefixFk;

            if (static::class === $relatedClass) {
                $selfEndpoints = [];
                foreach ($endpoints as $idx => $ep) {
                    if ($ep['class'] !== static::class) {
                        continue;
                    }

                    $fk = $ep['foreign_key'] ?? Convert::modelToColumn(static::class);
                    if (ColumnListing::has($pivotTable, $fk)) {
                        $selfEndpoints[$idx] = ['fk' => $fk, 'relations' => $ep['relations']];
                    }
                }

                if (count($selfEndpoints) < 2) {
                    continue;
                }

                if ($prefix === '') {
                    $ordered = array_values($selfEndpoints);
                    if (count($ordered) === 2 && $ordered[0]['fk'] !== $ordered[1]['fk']) {
                        return [$pivotClass, $pivotTable, $ordered[0]['fk'], $ordered[1]['fk']];
                    }

                    continue;
                }

                $targetMatches = [];
                foreach ($selfEndpoints as $idx => $ep) {
                    if ($matchesPrefix($ep, $ep['fk'])) {
                        $targetMatches[$idx] = $ep['fk'];
                    }
                }

                if (count($targetMatches) === 1) {
                    $targetIdx = array_key_first($targetMatches);
                    $others = [];
                    foreach ($selfEndpoints as $idx => $ep) {
                        if ($idx !== $targetIdx) {
                            $others[] = $ep['fk'];
                        }
                    }

                    if (count($others) === 1) {
                        return [$pivotClass, $pivotTable, $others[0], $targetMatches[$targetIdx]];
                    }
                }

                continue;
            }

            $selfValid = [];
            $relatedValid = [];
            foreach ($endpoints as $ep) {
                if ($ep['class'] === static::class) {
                    $fk = $ep['foreign_key'] ?? Convert::modelToColumn(static::class);
                    if (ColumnListing::has($pivotTable, $fk)) {
                        $selfValid[] = ['fk' => $fk, 'relations' => $ep['relations']];
                    }
                } elseif ($ep['class'] === $relatedClass) {
                    $rk = $ep['foreign_key'] ?? Convert::modelToColumn($relatedClass);
                    if (ColumnListing::has($pivotTable, $rk)) {
                        $relatedValid[] = ['fk' => $rk, 'relations' => $ep['relations']];
                    }
                }
            }

            if ($selfValid === [] || $relatedValid === []) {
                continue;
            }

            if ($prefix === '') {
                if (count($selfValid) === 1 && count($relatedValid) === 1) {
                    return [$pivotClass, $pivotTable, $selfValid[0]['fk'], $relatedValid[0]['fk']];
                }

                continue;
            }

            $selfMatched = array_values(array_filter($selfValid, static fn (array $ep): bool => $matchesPrefix($ep, $ep['fk'])));
            if (count($selfMatched) === 1 && count($relatedValid) === 1) {
                return [$pivotClass, $pivotTable, $selfMatched[0]['fk'], $relatedValid[0]['fk']];
            }

            $relatedMatched = array_values(array_filter($relatedValid, static fn (array $ep): bool => $matchesPrefix($ep, $ep['fk'])));
            if (count($relatedMatched) === 1 && count($selfValid) === 1) {
                return [$pivotClass, $pivotTable, $selfValid[0]['fk'], $relatedMatched[0]['fk']];
            }
        }

        return null;
    }

    /**
     * Resolves a `belongsToMany` relation configuration when `$singularRelation` matches a custom `KompactPivot` endpoint alias.
     *
     * @param string $modelNamespace
     * @param string $singularRelation
     * @return array{type: string, class: class-string<Model>, foreign_key: string, table: string, related_key: string, pivot: class-string<KompactPivot>}|null
     * @throws ReflectionException
     */
    protected static function resolvePluralPivotAlias(string $modelNamespace, string $singularRelation): ?array
    {
        $fileName = (new ReflectionClass(static::class))->getFileName();
        $fallbackDir = $fileName ? dirname($fileName) : null;
        $matches = [];

        foreach (KompactPivot::pivots($modelNamespace, $fallbackDir) as $pivotClass) {
            $pivotTable = (new $pivotClass)->getTable();
            if (! Schema::hasTable($pivotTable)) {
                continue;
            }

            $endpoints = $pivotClass::pivotEndpoints();
            foreach ($endpoints as $targetIdx => $targetEp) {
                if (! in_array($singularRelation, $targetEp['relations'], true)) {
                    continue;
                }

                $rk = $targetEp['foreign_key'] ?? Convert::modelToColumn($targetEp['class']);
                if (! ColumnListing::has($pivotTable, $rk)) {
                    continue;
                }

                foreach ($endpoints as $selfIdx => $selfEp) {
                    if ($selfIdx === $targetIdx || $selfEp['class'] !== static::class) {
                        continue;
                    }

                    $fk = $selfEp['foreign_key'] ?? Convert::modelToColumn(static::class);
                    if (ColumnListing::has($pivotTable, $fk)) {
                        $matches["$pivotClass|$fk|$rk"] = [
                            'type' => 'belongsToMany',
                            'class' => $targetEp['class'],
                            'foreign_key' => $fk,
                            'table' => $pivotTable,
                            'related_key' => $rk,
                            'pivot' => $pivotClass,
                        ];
                    }
                }
            }
        }

        return count($matches) === 1 ? reset($matches) : null;
    }

    /**
     * Resolves and caches the dynamic relation configuration for `$relationName`, or `false` if no model matches.
     *
     * @param string $relationName
     * @return array{type: string, class: class-string<Model>, foreign_key: string, table?: string, related_key?: string, pivot?: class-string<KompactPivot>}|false
     * @throws BadMethodCallException
     * @throws ReflectionException
     */
    protected static function resolveDynamicRelationConfig(string $relationName): array|false
    {
        if (array_key_exists($relationName, ModelCache::$relations[static::class] ?? [])) {
            return ModelCache::$relations[static::class][$relationName];
        }

        $isPlural = Str::plural($relationName) === $relationName;
        if (! $isPlural && is_subclass_of(static::class, KompactPivot::class)) {
            $ownTable = (new static)->getTable();
            foreach (static::pivotEndpoints() as $endpoint) {
                if (! in_array($relationName, $endpoint['relations'], true)) {
                    continue;
                }

                $fk = $endpoint['foreign_key'] ?? ColumnCreator::relation(Str::snake($relationName));
                if (ColumnListing::has($ownTable, $fk)) {
                    return ModelCache::$relations[static::class][$relationName] = [
                        'type' => 'belongsTo',
                        'class' => $endpoint['class'],
                        'foreign_key' => $fk,
                    ];
                }
            }
        }

        $singularRelation = Str::singular($relationName);
        $parts = preg_split('/(?=[A-Z])/', $singularRelation, -1, PREG_SPLIT_NO_EMPTY);
        $originalParts = $parts;
        $modelNamespace = config('eloquent-kompact.path.model');

        while (count($parts) > 0) {
            $attemptedClassName = implode('', array_map('ucfirst', $parts));
            /** @var class-string<KompactModel> $className */
            $className = namespace_concat($modelNamespace, $attemptedClassName);

            if (class_exists($className)) {
                $prefixParts = array_slice($originalParts, 0, count($originalParts) - count($parts));
                $prefix = implode('_', array_map('strtolower', $prefixParts));

                $table = (new $className)->getTable();
                $pivotClass = null;
                if ($isPlural) {
                    if (ColumnListing::has($table, $foreignKey = Convert::modelToColumn(static::class, $prefix))) {
                        $type = 'hasMany';
                    } elseif (is_subclass_of($className, KompactPivot::class) && ($pivotFk = static::resolvePivotForeignKey($className, $table, $prefix)) !== null) {
                        $type = 'hasMany';
                        $foreignKey = $pivotFk;
                    } elseif ($morphName = static::resolveMorphName($className, $table)) {
                        $type = 'morphMany';
                        $foreignKey = $morphName;
                    } elseif ($customPivot = static::resolveCustomPivot($modelNamespace, $className, $foreignKey, $relatedKey = Convert::modelToColumn($className), $prefix)) {
                        $type = 'belongsToMany';
                        [$pivotClass, $pivotTable, $foreignKey, $relatedKey] = $customPivot;
                    } elseif (
                        Schema::hasTable($pivotTable = (new static)->joiningTable($className))
                        && ColumnListing::has($pivotTable, $foreignKey)
                        && ColumnListing::has($pivotTable, $relatedKey)
                    ) {
                        $type = 'belongsToMany';
                    } elseif ($aliasConfig = static::resolvePluralPivotAlias($modelNamespace, $singularRelation)) {
                        return ModelCache::$relations[static::class][$relationName] = $aliasConfig;
                    } else {
                        throw new BadMethodCallException(__('eloquent-kompact::messages.relation_not_resolvable', [
                            'class' => static::class,
                            'relation' => $relationName,
                            'table' => $table,
                            'column' => $foreignKey,
                            'related' => $className,
                        ]));
                    }
                } elseif (ColumnListing::has((new static)->getTable(), $foreignKey = ColumnCreator::relation(Str::snake($relationName)))) {
                    $type = 'belongsTo';
                } elseif (ColumnListing::has($table, $foreignKey = Convert::modelToColumn(static::class, $prefix))) {
                    $type = 'hasOne';
                } elseif (is_subclass_of($className, KompactPivot::class) && ($pivotFk = static::resolvePivotForeignKey($className, $table, $prefix)) !== null) {
                    $type = 'hasOne';
                    $foreignKey = $pivotFk;
                } else {
                    throw new BadMethodCallException(__('eloquent-kompact::messages.relation_not_resolvable', [
                        'class' => static::class,
                        'relation' => $relationName,
                        'table' => $table,
                        'column' => $foreignKey,
                        'related' => $className,
                    ]));
                }

                return ModelCache::$relations[static::class][$relationName] = [
                    'type' => $type,
                    'class' => $className,
                    'foreign_key' => $foreignKey,
                    ...($type === 'belongsToMany' ? ['table' => $pivotTable, 'related_key' => $relatedKey] : []),
                    ...($pivotClass ? ['pivot' => $pivotClass] : []),
                ];
            }

            array_shift($parts);
        }

        if ($isPlural && ($aliasConfig = static::resolvePluralPivotAlias($modelNamespace, $singularRelation)) !== null) {
            return ModelCache::$relations[static::class][$relationName] = $aliasConfig;
        }

        return ModelCache::$relations[static::class][$relationName] = false;
    }

    /**
     * Builds an Eloquent `Relation` instance from a resolved dynamic relation configuration.
     *
     * @param array{type: string, class: class-string<Model>, foreign_key: string, table?: string, related_key?: string, pivot?: class-string<KompactPivot>} $config
     * @return Relation
     * @throws ReflectionException
     */
    protected function buildDynamicRelation(array $config): Relation
    {
        $relation = match ($config['type']) {
            'belongsTo' => $this->belongsTo($config['class'], $config['foreign_key']),
            'belongsToMany' => $this->belongsToMany($config['class'], $config['pivot'] ?? $config['table'], $config['foreign_key'], $config['related_key']),
            'hasMany' => $this->hasMany($config['class'], $config['foreign_key'] ?? null),
            'hasOne' => $this->hasOne($config['class'], $config['foreign_key'] ?? null),
            'morphMany' => $this->morphMany($config['class'], $config['foreign_key']),
        };

        if (static::shouldIncludeSoftDeletedData($config['class']) && $relation->getQuery()->hasMacro('withTrashed')) {
            $relation->withTrashed();
        }

        return $relation;
    }

    /**
     * Get the dynamic relation resolver if defined or inherited, falling back to `AutoRelation` resolution.
     *
     * @param class-string<Model> $class
     * @param string $key
     * @return Closure|null
     * @throws BadMethodCallException
     * @throws ReflectionException
     */
    public function relationResolver($class, $key): ?Closure
    {
        if ($resolver = parent::relationResolver($class, $key)) {
            return $resolver;
        }

        if ($config = static::resolveDynamicRelationConfig($key)) {
            return fn ($model) => $model->buildDynamicRelation($config);
        }

        return null;
    }
}
