<?php

namespace JundFauz\EloquentKompact\Console;

use BadMethodCallException;
use Closure;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str as SupportStr;
use JsonException;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Column\ColumnCreator;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Model\KompactPivot;
use JundFauz\EloquentKompact\Model\Traits\AutoRelation;
use ReflectionClass;
use ReflectionException;
use function JundFauz\EloquentKompact\Array\merge_as_array;
use function JundFauz\EloquentKompact\Array\pull_list;
use function JundFauz\EloquentKompact\comment;
use function JundFauz\EloquentKompact\full_path_on_app;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\String\concat;
use function JundFauz\EloquentKompact\String\empty_if_false;
use function JundFauz\EloquentKompact\String\str;
use function JundFauz\EloquentKompact\word_concat;
use function JundFauz\EloquentKompact\write_line;
use const JundFauz\EloquentKompact\DISTANCE_LINE;
use const JundFauz\EloquentKompact\NAMESPACE_DELIMITER;
use const JundFauz\EloquentKompact\NEW_LINE;

final class IdeHelperCommand extends Command
{
    protected $signature = 'make:ide-helper';
    protected $description = "Generate IDE autocomplete for AutoRelation's relation";

    public const string Filename = '_ide_helper_kompact.php';
    protected const string MixinNamespace = 'EloquentKompact\IdeHelper';
    protected const string MixinPrefix = '_EK_';
    protected const array RelationClasses = [
        'belongsTo' => BelongsTo::class,
        'belongsToMany' => BelongsToMany::class,
        'hasOne' => HasOne::class,
        'hasMany' => HasMany::class,
        'morphMany' => MorphMany::class
    ];
    protected const array SingularTypes = ['belongsTo', 'hasOne'];
    protected const string Indentation = '    ';

    /**
     * Generates `_ide_helper_kompact.php` and updates `ide.json` with dynamic relation mixins.
     *
     * @return int
     * @throws ReflectionException
     * @throws JsonException
     */
    public function handle(): int
    {
        $models = $this->models();
        $blocks = [];
        $mixins = [];

        foreach ($models as $parent) {
            if (! in_array(AutoRelation::class, class_uses_recursive($parent))) {
                continue;
            }

            if ($relations = $this->relationsOf($parent, $models)) {
                $blocks[] = $this->renderMixin($parent, $relations);
                $mixins[] = [
                    'classFqn' => concat(NAMESPACE_DELIMITER, $parent),
                    'mixinFqn' => concat(NAMESPACE_DELIMITER, $this->mixinFqn($parent))
                ];
            }
        }

        file_put_contents(base_path(self::Filename), $this->renderFile($blocks));
        $this->writeIdeJson($mixins);

        $this->info(word_concat(count($mixins), 'model(s) written to', self::Filename, 'and ide.json.'));

        return self::SUCCESS;
    }

    /**
     * The model is located directly under `path.model`, because that is the only place where the resolver looks for the class.
     *
     * @return list<class-string<Model>>
     * @throws ReflectionException
     */
    protected function models(): array
    {
        $namespace = config('eloquent-kompact.path.model');
        $models = [];

        foreach (glob(path_concat(full_path_on_app($namespace), '*.php')) ?: [] as $file) {
            $class = namespace_concat($namespace, pathinfo($file, PATHINFO_FILENAME));

            if (is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract()) {
                $models[] = $class;
            }
        }

        return $models;
    }

    /**
     * The opposite of `resolveDynamicRelationConfig()`: the relation name is inferred from the schema,
     * then filtered through its original resolver so that the result is exactly the same as at runtime.
     *
     * @param string $parent
     * @param array $models
     * @return array<string, array{type: string, class: string, foreign_key: string}>
     */
    protected function relationsOf(string $parent, array $models): array
    {
        $call = $this->scoped($parent);
        $relations = [];

        foreach ($this->candidates($parent, $models, $call) as $name => $expectedClass) {
            // Relationships defined as methods are recognized by the IDE and executed at runtime.
            if (method_exists($parent, $name)) {
                continue;
            }

            try {
                $config = $call('resolveDynamicRelationConfig', $name);
            } catch (BadMethodCallException $exception) {
                $this->warn($exception->getMessage());
                continue;
            }

            if ($config and (is_null($expectedClass) or same_with($config['class'], $expectedClass))) {
                $relations[$name] = merge_as_array($config, ['builder' => $this->builderOf($config['class'])]);
            }
        }

        ksort($relations);

        return $relations;
    }

    /**
     * Yields candidate dynamic relation names mapped to their expected related model class (or null for `belongsTo`).
     *
     * @param string $parent
     * @param list<class-string<Model>> $models
     * @param Closure $call
     * @return Generator
     */
    protected function candidates(string $parent, array $models, Closure $call): Generator
    {
        $relationColumns = static fn (string $table): array => array_filter(
            ColumnListing::of($table),
            static fn (string $column): bool => ColumnCreator::getColumnType($column) === ColumnCreator::Relation
        );

        $parentInstance = new $parent;
        $parentTable = $parentInstance->getTable();
        $tables = Schema::getTableListing(schemaQualified: false);

        // KompactPivot own belongsTo endpoints (including custom foreign keys and aliases)
        if (is_subclass_of($parent, KompactPivot::class) && in_array($parentTable, $tables, true)) {
            foreach ($parent::pivotEndpoints() as $endpoint) {
                foreach ($endpoint['relations'] as $rel) {
                    $fk = $endpoint['foreign_key'] ?? ColumnCreator::relation(SupportStr::snake($rel));
                    if (ColumnListing::has($parentTable, $fk)) {
                        yield $rel => $endpoint['class'];
                    }
                }
            }
        }

        // belongsTo: author_user_id in its own table → authorUser
        foreach ($relationColumns($parentTable) as $column) {
            yield (string) ColumnCreator::getPlainColumnName($column)->camel() => null;
        }

        $parentName = Str::of($parent)->classBasename()->snake();
        $morphMap = Relation::morphMap();
        $customPivots = array_values(array_filter(
            $models,
            static fn (string $class): bool => is_subclass_of($class, KompactPivot::class) && count($class::pivotEndpoints()) >= 2
        ));

        foreach ($models as $model) {
            $table = (new $model)->getTable();
            $base = Str::of($model)->classBasename();
            $singular = $base->snake();
            $plural = $base->pluralStudly()->snake();

            // hasOne & hasMany: author_user_id in the posts table → User::authorPost, User::authorPosts
            foreach ($relationColumns($table) as $column) {
                $plain = ColumnCreator::getPlainColumnName($column);

                if ($plain->value() === $parentName->value() || $plain->endsWith("_$parentName")) {
                    $prefix = $plain->beforeLast($parentName);

                    yield (string) $prefix->add($singular)->camel() => $model;
                    yield (string) $prefix->add($plural)->camel() => $model;
                }
            }

            // hasOne & hasMany onto KompactPivot models via custom endpoint foreign keys
            if (in_array($model, $customPivots, true) && in_array($table, $tables, true)) {
                $parentEndpoints = [];
                foreach ($model::pivotEndpoints() as $ep) {
                    if (! same_with($ep['class'], $parent)) {
                        continue;
                    }

                    $fk = $ep['foreign_key'] ?? Convert::modelToColumn($parent);
                    if (ColumnListing::has($table, $fk)) {
                        $parentEndpoints[] = ['fk' => $fk, 'foreign_key' => $ep['foreign_key'], 'relations' => $ep['relations']];
                    }
                }

                if (count($parentEndpoints) === 1 && $parentEndpoints[0]['foreign_key'] !== null) {
                    yield (string) $singular->camel() => $model;
                    yield (string) $plural->camel() => $model;
                } elseif (count($parentEndpoints) > 1) {
                    $nameMatchedCount = 0;
                    foreach ($parentEndpoints as $ep) {
                        foreach ($ep['relations'] as $rel) {
                            if (str_starts_with($singular->value(), SupportStr::snake($rel).'_')) {
                                $nameMatchedCount++;
                                break;
                            }
                        }
                    }

                    if ($nameMatchedCount === 1) {
                        yield (string) $singular->camel() => $model;
                        yield (string) $plural->camel() => $model;
                    }
                }

                foreach ($parentEndpoints as $ep) {
                    if ($ep['foreign_key'] === null) {
                        continue;
                    }

                    foreach ($ep['relations'] as $rel) {
                        $relSnake = SupportStr::snake($rel);
                        if (str_starts_with($singular->value(), "{$relSnake}_")) {
                            continue;
                        }

                        $relFk = ColumnCreator::relation($relSnake);
                        $matchingCount = 0;
                        foreach ($parentEndpoints as $candidate) {
                            if (in_array($rel, $candidate['relations'], true) || $candidate['fk'] === $relFk) {
                                $matchingCount++;
                            }
                        }

                        if ($matchingCount === 1) {
                            yield SupportStr::camel("{$relSnake}_{$singular}") => $model;
                            yield SupportStr::camel("{$relSnake}_{$plural}") => $model;
                        }
                    }
                }
            }

            // belongsToMany: standard joiningTable or custom KompactPivot::$pivotBetween
            $pivotTables = [$parentInstance->joiningTable($model)];
            foreach ($customPivots as $pivotClass) {
                if ($pivotClass::connects($parent, $model)) {
                    $pivotTables[] = $pivotClass::tableName();

                    $customPivotTable = $pivotClass::tableName();
                    if (! same_with($parent, $model) && in_array($customPivotTable, $tables, true)) {
                        $selfValid = [];
                        $relatedValid = [];
                        foreach ($pivotClass::pivotEndpoints() as $ep) {
                            if (same_with($ep['class'], $parent)) {
                                $fk = $ep['foreign_key'] ?? Convert::modelToColumn($parent);
                                if (ColumnListing::has($customPivotTable, $fk)) {
                                    $selfValid[] = ['fk' => $fk, 'foreign_key' => $ep['foreign_key'], 'relations' => $ep['relations']];
                                }
                            } elseif (same_with($ep['class'], $model)) {
                                $rk = $ep['foreign_key'] ?? Convert::modelToColumn($model);
                                if (ColumnListing::has($customPivotTable, $rk)) {
                                    $relatedValid[] = ['fk' => $rk, 'foreign_key' => $ep['foreign_key'], 'relations' => $ep['relations']];
                                }
                            }
                        }

                        if (count($selfValid) === 1 && count($relatedValid) === 1) {
                            yield (string) $plural->camel() => $model;
                        } elseif (count($relatedValid) === 1 && count($selfValid) > 1) {
                            foreach ($selfValid as $ep) {
                                if ($ep['foreign_key'] !== null) {
                                    foreach ($ep['relations'] as $rel) {
                                        yield SupportStr::camel(SupportStr::snake($rel)."_{$plural}") => $model;
                                    }
                                }
                            }
                        } elseif (count($selfValid) === 1 && count($relatedValid) > 1) {
                            foreach ($relatedValid as $ep) {
                                if ($ep['foreign_key'] !== null) {
                                    foreach ($ep['relations'] as $rel) {
                                        yield SupportStr::camel(SupportStr::snake($rel)."_{$plural}") => $model;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach (array_unique($pivotTables) as $pivotTable) {
                if (! in_array($pivotTable, $tables, true)) {
                    continue;
                }

                foreach ($relationColumns($pivotTable) as $column) {
                    $plain = ColumnCreator::getPlainColumnName($column);

                    if ($plain->value() === $parentName->value() || $plain->endsWith("_$parentName")) {
                        $prefix = $plain->beforeLast($parentName);

                        yield (string) $prefix->add($plural)->camel() => $model;
                    }
                }
            }

            // morphMany: The runtime accepts it in any parent, so it is restricted to morphMap if one exists.
            if (blank($morphMap) || in_array($parent, $morphMap, true) && $call('resolveMorphName', $model, $table)) {
                yield (string) $plural->camel() => $model;
            }
        }

        // belongsToMany plural endpoint aliases on custom KompactPivots (e.g. ParentLedger::subs())
        $modelNamespace = config('eloquent-kompact.path.model');
        foreach ($customPivots as $pivotClass) {
            $pivotTable = $pivotClass::tableName();
            if (! in_array($pivotTable, $tables, true)) {
                continue;
            }

            $endpoints = $pivotClass::pivotEndpoints();
            foreach ($endpoints as $targetIdx => $targetEp) {
                if ($targetEp['foreign_key'] === null || ! ColumnListing::has($pivotTable, $targetEp['foreign_key'])) {
                    continue;
                }

                $hasSelfOther = false;
                foreach ($endpoints as $selfIdx => $selfEp) {
                    if ($selfIdx === $targetIdx || ! same_with($selfEp['class'], $parent)) {
                        continue;
                    }

                    $fk = $selfEp['foreign_key'] ?? Convert::modelToColumn($parent);
                    if (ColumnListing::has($pivotTable, $fk)) {
                        $hasSelfOther = true;
                        break;
                    }
                }

                if (! $hasSelfOther) {
                    continue;
                }

                foreach ($targetEp['relations'] as $rel) {
                    $pluralRel = SupportStr::plural($rel);
                    if ($call('resolvePluralPivotAlias', $modelNamespace, SupportStr::singular($pluralRel)) !== null) {
                        yield $pluralRel => $targetEp['class'];
                    }
                }
            }
        }
    }

    /**
     * The caller of a protected static method of a trait using `static:: = $class`.
     *
     * @param string $class
     * @return Closure
     */
    protected function scoped(string $class): Closure
    {
        return Closure::bind(
            static fn (string $method, mixed ...$args) => $class::{$method}(...$args),
            null,
            $class
        );
    }

    /**
     * The builder that the related model really uses. Eloquent's own one is left out,
     * because `Relation` already exposes it through its `@mixin`.
     *
     * @param class-string<Model> $class
     * @return class-string<Builder>|null
     */
    protected function builderOf(string $class): ?string
    {
        $builder = get_class((new $class)->newModelQuery());

        return same_with($builder, Builder::class) ? null : $builder;
    }

    /**
     * Fully qualified helper mixin class name for `$class`.
     *
     * @param string $class
     * @return string
     */
    protected function mixinFqn(string $class): string
    {
        return namespace_concat(
            self::MixinNamespace,
            str('before_last', $class, NAMESPACE_DELIMITER),
            Str::of($class)->classBasename()->addAtStart(self::MixinPrefix)
        );
    }

    /**
     * Renders the namespace block and docblock for a model's dynamic relation mixin class.
     *
     * @param string $parent
     * @param array<string, array<string, mixed>> $relations
     * @return string
     */
    protected function renderMixin(string $parent, array $relations): string
    {
        $mixin = $this->mixinFqn($parent);
        $lines = [];

        foreach ($relations as $name => $relation) {
            [$type, $class, $builder, $pivot] = pull_list($relation, 'type', 'class', 'builder', 'pivot');

            $lines[] = concat(self::Indentation,
                ' * @property-read \\',
                in_array($type, self::SingularTypes, true)
                    ? "$class|null"
                    : concat(namespace_concat('Illuminate', 'Database', 'Eloquent', 'Collection'), "<int, \\$class>"),
                " \$$name"
            );
            $generics = $pivot ? "<\\$class, \\$parent, \\$pivot>" : "<\\$class, \\$parent>";
            $lines[] = word_concat(self::Indentation,  concat('* @method \\', self::RelationClasses[$type], $generics, empty_if_false($builder, "|\\$builder")), "$name()");
        }

        return write_line(
            word_concat('namespace', str('before_last', $mixin, '\\'), '{'),
            concat(self::Indentation, '/**'),
            $lines,
            word_concat(self::Indentation, '*/'),
            concat(self::Indentation, word_concat('class', class_basename($mixin), '{}')),
            '}'
        );
    }

    /**
     * Renders the complete `_ide_helper_kompact.php` file content from rendered mixin blocks.
     *
     * @param array<int, string> $blocks
     * @return string
     */
    protected function renderFile(array $blocks): string
    {
        return concat(
            join(DISTANCE_LINE, [
                write_line(
                    '<?php',
                    '/** @noinspection all */'
                ),
                write_line(
                    comment('@formatter:off'),
                    comment('phpcs:ignoreFile'),
                    comment("Generated by `php artisan kompact:ide-helper`, don't edit manually.")
                ),
                ...$blocks
            ]),
            NEW_LINE
        );
    }

    /**
     * Register the mixin with Laravel Idea; third-party `classMixins` entries are left as is.
     * @throws JsonException
     * @param array $mixins
     * @return void
     */
    protected function writeIdeJson(array $mixins):void
    {
        $path = base_path('ide.json');

        $json = is_file($path)
            ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR)
            : ['$schema' => 'https://laravel-ide.com/schema/laravel-ide-v2.json'];

        $kept = array_filter(
            $json['helperCode']['classMixins'] ?? [],
            static fn (array $mixin) => ! Str::of($mixin['mixinFqn'] ?? '')
                ->ltrim('\\')
                ->startsWith(self::MixinNamespace)
        );

        $json['helperCode']['classMixins'] = [...array_values($kept), ...$mixins];

        file_put_contents($path, concat(
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            NEW_LINE
        ));
    }
}