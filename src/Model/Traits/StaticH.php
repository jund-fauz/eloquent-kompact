<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Query\RawQuery;
use JundFauz\EloquentKompact\Relation\Arrange;

trait StaticH
{
    /**
     * Table name of the model.
     *
     * @return string
     * @final
     */
    public static function tableName(): string
    {
        return (new static)->getTable();
    }

    /**
     * Morph alias registered for the model, or its FQCN when unmapped.
     *
     * @return int|string
     * @final
     */
    public static function morphClass(): int|string
    {
        return Relation::getMorphAlias(static::class);
    }

    /**
     * Short class name of the model without its namespace.
     *
     * @return string
     * @final
     */
    public static function baseName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Singular camelCase relation name for the model, optionally prefixed.
     *
     * @param string|null $prefix
     * @return string
     * @final
     */
    public static function singular(?string $prefix = null): string
    {
        return Convert::modelToRelation($prefix ? [$prefix => static::class] : static::class);
    }

    /**
     * Plural camelCase relation name for the model, optionally prefixed.
     *
     * @param string|null $prefix
     * @return string
     * @final
     */
    public static function plural(?string $prefix = null): string
    {
        return Convert::modelToRelation($prefix ? [$prefix => static::class] : static::class, true);
    }

    /**
     * Dot-separated relation path starting from this model through `$modelClasses`.
     *
     * @param string|array ...$modelClasses
     * @return string
     * @final
     */
    public static function relationTo(string|array ...$modelClasses): string
    {
        return static::columnTo('', ...$modelClasses);
    }

    /**
     * Dot-separated relation path starting from this model through `$modelClasses`, ending with `$columnName`.
     *
     * @param string $columnName
     * @param string|array ...$modelClasses
     * @return string
     * @final
     */
    public static function columnTo(string $columnName, string|array ...$modelClasses): string
    {
        return Arrange::relation($columnName, static::class, ...$modelClasses);
    }

    /**
     * Foreign key column name for the model, optionally prefixed.
     *
     * @param string|null $prefix
     * @return string
     * @final
     */
    public static function foreign(?string $prefix = null): string
    {
        return Convert::modelToColumn(static::class, $prefix);
    }

    /**
     * Grammar-wrapped foreign key of this model qualified with `$modelClass`'s table.
     *
     * @param class-string<Model>|string $modelClass
     * @param string|null $foreignPrefix
     * @return RawQuery
     * @final
     */
    public static function foreignFrom(string $modelClass, ?string $foreignPrefix = null): RawQuery
    {
        return self::wrapColumn(new $modelClass, static::foreign($foreignPrefix));
    }

    /**
     * Grammar-wrapped `$column` qualified with this model's table.
     *
     * @param string $column
     * @param bool $plain
     * @return RawQuery
     * @final
     */
    public static function qualify(string $column, bool $plain = false): RawQuery
    {
        /** @var Model $model */
        $model = new static;
        return $plain
            ? RawQuery::create($model->qualifyColumn($column))
            : self::wrapColumn($model, $column);
    }

    /**
     * Model FQCN corresponding to the blueprint's table under `path.model`, or null if the class does not exist.
     *
     * @param Blueprint $column
     * @return class-string<Model>|null
     * @final
     */
    public static function getModel(Blueprint $column): ?string
    {
        $tableName = $column->getTable();
        $modelClass = (string) Str::of($tableName)
            ->singular()
            ->pascal()
            ->addNamespaceAtStart(config('eloquent-kompact.path.model'));

        return class_exists($modelClass) ? $modelClass : null;
    }

    /**
     * Wraps the table-qualified `$column` of `$model` using its connection's query grammar.
     *
     * @param Model $model
     * @param string $column
     * @return RawQuery
     * @internal
     */
    private static function wrapColumn(Model $model, string $column): RawQuery
    {
        return RawQuery::create(
            $model->getConnection()->getQueryGrammar()->wrap($model->qualifyColumn($column))
        );
    }
}
