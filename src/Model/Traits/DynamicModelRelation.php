<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Column\Creator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use function array_key_exists;
use function count;
use function sprintf;

trait DynamicModelRelation
{
    const string basicModelNamespace = '\App\Database\Models\\';

    protected static array $dynamicRelationCache = [];

    /**
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
     * @throws ReflectionException
     */
    protected static function resolveDynamicRelationConfig(string $relationName): array|false
    {
        if (array_key_exists($relationName, self::$dynamicRelationCache[static::class] ?? [])) {
            return self::$dynamicRelationCache[static::class][$relationName];
        }

        $isPlural = Str::plural($relationName) === $relationName;
        $singularRelation = Str::singular($relationName);
        $parts = preg_split('/(?=[A-Z])/', $singularRelation, -1, PREG_SPLIT_NO_EMPTY);
        $originalParts = $parts;

        while (count($parts) > 0) {
            $attemptedClassName = implode('', array_map(ucfirst(...), $parts));
            $className = self::basicModelNamespace . $attemptedClassName;

            if (class_exists($className)) {
                $prefixParts = array_slice($originalParts, 0, count($originalParts) - count($parts));
                $prefix = implode('_', array_map(strtolower(...), $prefixParts));

                if ($isPlural) {
                    $parentModelName = Str::snake(class_basename(static::class));
                    $foreignKey = Creator::relation($prefix ? "{$prefix}_$parentModelName" : $parentModelName);
                    $table = (new $className)->getTable();

                    if (ColumnListing::has($table, $foreignKey)) {
                        $type = 'hasMany';
                    } elseif ($morphName = static::resolveMorphName($className, $table)) {
                        $type = 'morphMany';
                        $foreignKey = $morphName;
                    } else {
                        throw new BadMethodCallException(sprintf(
                            'Relasi %s::%s tidak bisa ditebak: kolom %s.%s tidak ada dan %s tidak punya morphTo tunggal.',
                            static::class, $relationName, $table, $foreignKey, $className
                        ));
                    }
                } else {
                    $foreignKey = Creator::relation(Str::snake($relationName));
                    $type = 'belongsTo';
                }

                return self::$dynamicRelationCache[static::class][$relationName] = [
                    'type' => $type,
                    'class' => $className,
                    'foreign_key' => $foreignKey,
                ];
            }

            array_shift($parts);
        }

        return self::$dynamicRelationCache[static::class][$relationName] = false;
    }

    protected function buildDynamicRelation(array $config): Relation
    {
        return match ($config['type']) {
            'belongsTo' => $this->belongsTo($config['class'], $config['foreign_key']),
            'hasMany' => $this->hasMany($config['class'], $config['foreign_key'] ?? null),
            'hasOne' => $this->hasOne($config['class'], $config['foreign_key'] ?? null),
            'morphMany' => $this->morphMany($config['class'], $config['foreign_key'])
        };
    }

    public function __call($method, $parameters)
    {
        if ($config = static::resolveDynamicRelationConfig($method)) {
            return $this->buildDynamicRelation($config);
        }

        return parent::__call($method, $parameters);
    }

    public function getRelationValue($key)
    {
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return parent::getRelationValue($key);
        }

        if ($config = static::resolveDynamicRelationConfig($key)) {
            $relation = $this->buildDynamicRelation($config);

            return tap($relation->getResults(), function ($results) use ($key) {
                $this->setRelation($key, $results);
            });
        }

        return parent::getRelationValue($key);
    }

    protected static function hasDynamicRelation(string $relationName): bool
    {
        $isPlural = Str::plural($relationName) === $relationName;
        $singularRelation = $isPlural ? Str::singular($relationName) : $relationName;

        $parts = preg_split('/(?=[A-Z])/', $singularRelation, flags: PREG_SPLIT_NO_EMPTY);
        while (count($parts) > 0) {
            $attemptedClassName = implode('', array_map(ucfirst(...), $parts));
            if (class_exists(self::basicModelNamespace . $attemptedClassName)) {
                return true;
            }
            array_shift($parts);
        }

        return false;
    }

    public function relationResolver($class, $key)
    {
        if ($resolver = parent::relationResolver($class, $key)) {
            return $resolver;
        }

        if (static::hasDynamicRelation($key)) {
            return fn ($model) => $model->$key();
        }

        return null;
    }
}
