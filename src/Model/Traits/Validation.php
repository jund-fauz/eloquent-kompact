<?php

namespace JundFauz\EloquentKompact\Model\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use JundFauz\EloquentKompact\Column\ColumnCheck;
use ReflectionException;
use ReflectionMethod;

trait Validation
{
    /** @var array<class-string, list<class-string>> */
    protected static array $ruleLayerCache = [];

    protected static function gramMaxRule(): float
    {
        return 99_999.9999999;
    }

    protected static function pcsMaxRule(): int
    {
        return 4_294_967_295;
    }

    /**
     * Rules of every fillable column and every column declared by `defineRules()`.
     *
     * `defineRules()` of each class in the hierarchy is merged, from the model itself up to its
     * ancestors and then the defaults of `defaultRules()`, so a rule is only added when the more
     * specific layer has no rule with the same name. The rules declared by an ancestor are only
     * applied to the columns of the model, while the ones declared by the model itself are
     * always applied.
     *
     * @return array<string, array<int, mixed>>
     * @throws ReflectionException
     */
    public static function rules(?Model $record = null): array
    {
        $model = new static;
        $columns = $model->getFillable();
        $layers = [];

        foreach (static::ruleLayers() as $class) {
            $layer = (new ReflectionMethod($class, 'defineRules'))->getClosure($model)($record);

            if ($class !== static::class) {
                $layer = array_intersect_key($layer, array_flip($columns));
            }

            $layers[] = $layer;
        }

        $rules = [];
        foreach (array_unique(array_merge($columns, ...array_map(array_keys(...), $layers))) as $column) {
            $columnRules = [];

            foreach ($layers as $layer) {
                self::injectRules($columnRules, ...self::normalizeRules($layer[$column] ?? []));
            }

            self::injectRules($columnRules, ...self::normalizeRules(static::defaultRules($column, $record)));

            if (self::isInRules('string', $columnRules)) {
                self::injectRules($columnRules, 'max:255');
            }

            $rules[$column] = $columnRules;
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defineRules(?Model $record = null): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function defaultRules(string $column, ?Model $record = null): array
    {
        return match (true) {
            ColumnCheck::isRelation($column) => ['integer'],
            ColumnCheck::isGram($column) => ['numeric', 'min:0.001', 'decimal:0,3', 'max:' . static::gramMaxRule()],
            ColumnCheck::isPcs($column) => ['integer', 'min:0', 'max:' . static::pcsMaxRule()],
            ColumnCheck::isPercentage($column) => ['numeric', 'min:0', 'max:100'],
            ColumnCheck::isBoolean($column) => ['boolean'],
            default => [],
        };
    }

    protected static function uniqueRule(string $column, ?Model $record = null): Unique
    {
        return Rule::unique((new static)->getTable(), $column)->ignore($record);
    }

    /**
     * Classes declaring their own defineRules(), from the model itself up to its ancestors.
     *
     * @return list<class-string>
     */
    protected static function ruleLayers(): array
    {
        if (isset(static::$ruleLayerCache[static::class])) {
            return static::$ruleLayerCache[static::class];
        }

        $layers = [];
        for ($class = static::class; $class; $class = get_parent_class($class)) {
            if (method_exists($class, 'defineRules')
                && (new ReflectionMethod($class, 'defineRules'))->getDeclaringClass()->getName() === $class) {
                $layers[] = $class;
            }
        }

        return static::$ruleLayerCache[static::class] = $layers;
    }

    /**
     * @return array<int, mixed>
     */
    private static function normalizeRules(mixed $rules): array
    {
        return match (true) {
            is_string($rules) => explode('|', $rules),
            is_array($rules) => $rules,
            default => [$rules],
        };
    }

    private static function injectRules(array &$fieldRules, mixed ...$rulesToInject): void
    {
        foreach ($rulesToInject as $rule) {
            if (! is_string($rule) || ! self::isInRules($rule, $fieldRules)) {
                $fieldRules[] = $rule;
            }
        }
    }

    private static function isInRules(string $rule, array $fieldRules): bool
    {
        $prefix = str_contains($rule, ':') ? explode(':', $rule)[0] : $rule;

        foreach ($fieldRules as $fieldRule) {
            if (is_string($fieldRule) && explode(':', $fieldRule)[0] === $prefix) {
                return true;
            }
        }

        return false;
    }
}
