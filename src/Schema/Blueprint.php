<?php

namespace JundFauz\EloquentKompact\Schema;

use BackedEnum;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\Str as SupportStr;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnCheck;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Column\Config;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Query\RawQuery;
use LogicException;

/**
 * Extended Laravel Schema `Blueprint` providing Kompact token-suffixed column helpers and dynamic plural calls.
 *
 * @method array<int, ColumnDefinition> strings(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> grams(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> quantities(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> percentages(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> booleans(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> booleanCols(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> times(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> createTimestamps(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> createForeignIds(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> stringCastsEnums(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 * @method array<int, ColumnDefinition> stringCastsArrays(array<int|string, mixed> $columns, ?array<string, mixed> $config = null)
 */
class Blueprint extends BaseBlueprint
{
    protected const array CreatorNamedPlurals = ['times', 'booleans'];

    /**
     * Dynamically handle pluralized column-creation calls and registered macros on the blueprint.
     *
     * @param string $method
     * @param array<int, mixed> $parameters
     * @return mixed
     */
    public function __call($method, $parameters): mixed
    {
        if (! static::hasMacro($method) && ($singular = $this->resolveSingular($method)) !== null) {
            return $this->many($method, $singular, ...$parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Resolve the singular blueprint method or macro name for a plural dynamic call, or `null` if none exists.
     *
     * @param string $method
     * @return string|null
     */
    protected function resolveSingular(string $method): ?string
    {
        foreach ([SupportStr::singular($method), SupportStr::chopEnd($method, 's')] as $singular) {
            if ($singular !== $method && (method_exists($this, $singular) || static::hasMacro($singular))) {
                return $singular;
            }
        }

        return null;
    }

    /**
     * Invoke a singular column helper across multiple column entries and optionally apply batch modifiers.
     *
     * @param string $plural
     * @param string $singular
     * @param array<int|string, mixed> $columns
     * @param array<string, string|array<int|string, mixed>>|null $config
     * @return array<int, ColumnDefinition>
     */
    protected function many(string $plural, string $singular, array $columns, ?array $config = null): array
    {
        $isCreatorNamed = in_array($plural, self::CreatorNamedPlurals, true);
        $definitions = [];

        foreach ($columns as $secondParameter => $column) {
            $parameters = [$isCreatorNamed ? ColumnCreator::{$singular}($column) : $column];
            if (is_string($secondParameter)) {
                $parameters[] = $secondParameter;
            }

            $columnCountBefore = count($this->columns);
            $this->{$singular}(...$parameters);
            array_push($definitions, ...array_slice($this->columns, $columnCountBefore));
        }

        if ($config) {
            Config::apply($definitions, $config);
        }

        return $definitions;
    }

    /**
     * Create a `VARCHAR` column suffixed with the enum token and an optional scalar or `BackedEnum` default value.
     *
     * @param string $column
     * @param mixed $defaultValue
     * @return ColumnDefinition
     */
    public function stringCastsEnum(string $column, mixed $defaultValue = null): ColumnDefinition
    {
        $col = $this->string(ColumnCreator::enum($column));
        if ($defaultValue !== null) {
            $col->default($defaultValue instanceof BackedEnum ? $defaultValue->value : $defaultValue);
        }

        return $col;
    }

    /**
     * Create a `VARCHAR` column suffixed with the array token.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function stringCastsArray(string $column): ColumnDefinition
    {
        return $this->string(ColumnCreator::array($column));
    }

    /**
     * Create a `DECIMAL` column suffixed with the gram token.
     *
     * @param string $column
     * @param int $total
     * @param int $places
     * @return ColumnDefinition
     */
    public function gram(string $column, int $total = 12, int $places = 7): ColumnDefinition
    {
        return $this->decimal(ColumnCreator::gram($column), $total, $places);
    }

    /**
     * Create an unsigned integer (or unsigned big integer when `$big` is `true`) column suffixed with the pcs token.
     *
     * @param string $column
     * @param bool $big
     * @return ColumnDefinition
     */
    public function pcs(string $column, bool $big = false): ColumnDefinition
    {
        $name = ColumnCreator::pcs($column);

        return $big ? $this->unsignedBigInteger($name) : $this->unsignedInteger($name);
    }

    /**
     * Create the standard `quantity_gram` and `quantity_pcs` column pair and optionally apply batch modifiers.
     *
     * @param array<string, string|array<int|string, mixed>>|null $config
     * @param int $gramTotalDigit
     * @param bool $bigPcs
     * @return array{0: ColumnDefinition, 1: ColumnDefinition}
     */
    public function quantity(?array $config = null, int $gramTotalDigit = 12, bool $bigPcs = false): array
    {
        $columns = [$this->gram('quantity', $gramTotalDigit), $this->pcs('quantity', $bigPcs)];
        if ($config) {
            Config::apply($columns, $config);
        }

        return $columns;
    }

    /**
     * Create a `DECIMAL` column suffixed with the percentage token, unsigned unless `$canBeNegative` is `true`.
     *
     * @param string $column
     * @param bool $canBeNegative
     * @param int $total
     * @param int $places
     * @return ColumnDefinition
     */
    public function percentage(string $column, bool $canBeNegative = false, int $total = 6, int $places = 2): ColumnDefinition
    {
        $result = $this->decimal(ColumnCreator::percentage($column), $total, $places);
        if (! $canBeNegative) {
            $result->unsigned();
        }

        return $result;
    }

    /**
     * Create a constrained foreign ID column referencing `$model`'s table, appending the relation token if needed.
     *
     * @param class-string $model
     * @param string|null $customName
     * @param array<int|string, mixed>|string|null $config
     * @return ForeignKeyDefinition
     */
    public function createForeignId(string $model, ?string $customName = null, array|string|null $config = null): ForeignKeyDefinition
    {
        if ($customName !== null && ! ColumnCheck::isRelation($customName)) {
            $customName = ColumnCreator::relation($customName);
        }

        $tableName = method_exists($model, 'tableName') ? $model::tableName() : (new $model)->getTable();
        $column = $this->foreignId($customName ?? ColumnCreator::relation(SupportStr::singular($tableName)));
        if ($config) {
            Config::applies($column, $config);
        }

        return $column->constrained($tableName);
    }

    /**
     * Add nullable `created_at`, `updated_at`, and `deleted_at` timestamp columns to the table.
     *
     * @return void
     */
    public function completeTimestampColumns(): void
    {
        $this->timestamps();
        $this->softDeletes();
    }

    /**
     * Create a `TIMESTAMP` column suffixed with the timestamp token and optionally configure it via callback or `Config`.
     *
     * @param string $columnName
     * @param array<int|string, mixed>|string|callable|null $config
     * @return ColumnDefinition
     */
    public function createTimestamp(string $columnName, array|string|callable|null $config = null): ColumnDefinition
    {
        $column = $this->timestamp(ColumnCreator::timestamp($columnName));
        if ($config !== null) {
            if (! is_string($config) && is_callable($config)) {
                $config($column);
            } else {
                Config::applies($column, $config);
            }
        }

        return $column;
    }

    /**
     * Create a `BOOLEAN` column suffixed with the boolean token.
     *
     * @param string $columnName
     * @return ColumnDefinition
     */
    public function booleanCol(string $columnName): ColumnDefinition
    {
        return $this->boolean(ColumnCreator::boolean($columnName));
    }

    /**
     * Create a generated `viewed_id` column formatted using the table model's `ViewedId` prefix and source column configuration.
     *
     * @param int|null $minus
     * @param string|null $columnName
     * @param string|null $enumColumn
     * @return ColumnDefinition
     * @throws LogicException
     */
    public function viewedId(?int $minus = null, ?string $columnName = null, ?string $enumColumn = null): ColumnDefinition
    {
        $model = KompactModel::getModel($this);
        if ($model === null) {
            throw new LogicException("Cannot resolve model class for table [{$this->getTable()}] under config 'eloquent-kompact.path.model'.");
        }

        $prefix = method_exists($model, 'getPrefixId') ? $model::getPrefixId() : null;
        $sourceColumn = method_exists($model, 'getSourceColumnNameForViewedId') ? $model::getSourceColumnNameForViewedId() : 'id';
        $sourceExpression = (string) Str::of($sourceColumn)->concat(Str::of(" - $minus")->emptyIfFalse($minus));

        if (is_string($prefix)) {
            $expression = RawQuery::create(RawQuery::string("$prefix-"))->concat($sourceExpression);
        } elseif (is_array($prefix)) {
            $expression = RawQuery::create($enumColumn ?? ColumnCreator::enum('type'))
                ->switchCase($prefix)
                ->concat(RawQuery::string('-'), $sourceExpression);
        } else {
            $expression = RawQuery::create($sourceExpression);
        }

        $column = $this->string($columnName ?? ColumnCreator::relation('viewed'));
        $sourceColumn === 'id' ? $column->virtualAs($expression) : $column->storedAs($expression);

        return $column;
    }
}
