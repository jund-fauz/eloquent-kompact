<?php

namespace JundFauz\EloquentKompact\Console;

use BadMethodCallException;
use Closure;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use JsonException;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Column\Creator;
use JundFauz\EloquentKompact\Model\Traits\DynamicModelRelation;
use ReflectionClass;
use ReflectionException;
use function JundFauz\EloquentKompact\comment;
use function JundFauz\EloquentKompact\full_path_on_app;
use function JundFauz\EloquentKompact\namespace_concat;
use function JundFauz\EloquentKompact\path_concat;
use function JundFauz\EloquentKompact\same_with;
use function JundFauz\EloquentKompact\str_before_last;
use function JundFauz\EloquentKompact\str_concat;
use function JundFauz\EloquentKompact\word_concat;
use function JundFauz\EloquentKompact\write_line;
use const JundFauz\EloquentKompact\DISTANCE_LINE;
use const JundFauz\EloquentKompact\NAMESPACE_DELIMITER;
use const JundFauz\EloquentKompact\NEW_LINE;

final class IdeHelperCommand extends Command
{
    protected $signature = 'kompact:ide-helper';
    protected $description = "Generate IDE autocomplete for DynamicModelRelation's relation";

    public const string Filename = '_ide_helper_kompact.php';
    protected const string MixinNamespace = 'EloquentKompact\IdeHelper';
    protected const array RelationClasses = [
        'belongsTo' => BelongsTo::class,
        'hasOne' => HasOne::class,
        'hasMany' => HasMany::class,
        'morphMany' => MorphMany::class
    ];
    protected const array SingularTypes = ['belongsTo', 'hasOne'];
    protected const string Indentation = '    ';

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    public function handle(): int
    {
        $models = $this->models();
        $blocks = [];
        $mixins = [];

        foreach ($models as $parent) {
            if (! in_array(DynamicModelRelation::class, class_uses_recursive($parent))) {
                continue;
            }

            if ($relations = $this->relationsOf($parent, $models)) {
                $blocks[] = $this->renderMixin($parent, $relations);
                $mixins[] = [
                    'classFqn' => str_concat(NAMESPACE_DELIMITER, $parent),
                    'mixinFqn' => str_concat(NAMESPACE_DELIMITER, $this->mixinFqn($parent))
                ];
            }
        }

        file_put_contents(base_path(self::Filename), $this->renderFile($blocks));
        $this->writeIdeJson($mixins);

        $this->info(word_concat(count($mixins), 'model(s) writtent to', self::Filename, 'and ide.json.'));

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

            if ($config && (is_null($expectedClass) || same_with($config['class'], $expectedClass))) {
                $relations[$name] = $config;
            }
        }

        ksort($relations);

        return $relations;
    }

    /**
     * @param string $parent
     * @param list<class-string<Model>> $models
     * @param Closure $call
     * @return Generator
     */
    protected function candidates(string $parent, array $models, Closure $call): Generator
    {
        $relationColumns = static fn (string $table): array => array_filter(
            ColumnListing::of($table),
            static fn (string $column): bool => Creator::getColumnType($column) === Creator::Relation
        );

        // belongsTo: author_user_id in its own table → authorUser
        foreach ($relationColumns((new $parent)->getTable()) as $column) {
            yield (string) Creator::getPlainColumnName($column)->camel() => null;
        }

        $parentName = Str::of($parent)->classBasename()->snake();
        $morphMap = Relation::morphMap();

        foreach ($models as $model) {
            $table = (new $model)->getTable();
            $plural = Str::of($model)->classBasename()->pluralStudly()->snake();

            // hasMany: author_user_id in the posts table → User::authorPosts
            foreach ($relationColumns($table) as $column) {
                $plain = Creator::getPlainColumnName($column);

                if ($plain->value() === $parentName->value() || $plain->endsWith("_$parentName")) {
                    yield (string) $plain->beforeLast($parentName)->add($plural)->camel() => $model;
                }
            }

            // morphMany: The runtime accepts it in any parent, so it is restricted to morphMap if one exists.
            if (empty($morphMap) || in_array($parent, $morphMap, true) && $call('resolveMorphName', $model, $table)) {
                yield (string) $plural->camel() => $model;
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

    protected function mixinFqn(string $class): string
    {
        return namespace_concat(self::MixinNamespace, $class);
    }

    protected function renderMixin(string $parent, array $relations): string
    {
        $mixin = $this->mixinFqn($parent);
        $lines = [];

        foreach ($relations as $name => ['type' => $type, 'class' => $class]) {
            $lines[] = str_concat(self::Indentation,
                ' * @property-read ',
                in_array($type, self::SingularTypes, true)
                    ? "\\$class|null"
                    : str_concat('\\', namespace_concat('Illuminate', 'Database', 'Eloquent', 'Collection'), "<int, \\$class>"),
                " \$$name"
            );
            $lines[] = str_concat(self::Indentation, ' * @method \\', self::RelationClasses[$type], "<\\$class, \\$parent> $name()");
        }

        return write_line(
            word_concat('namespace', str_before_last($mixin, '\\'), '{'),
            str_concat(self::Indentation, '/**'),
            $lines,
            word_concat(self::Indentation, '*/'),
            str_concat(self::Indentation, word_concat('class', class_basename($mixin), '{}')),
            '}'
        );
    }

    protected function renderFile(array $blocks): string
    {
        return str_concat(
            join(DISTANCE_LINE, [
                '<?php',
                write_line(
                    comment('@formatter:off'),
                    comment('phpcs:ignoreFile'),
                    comment("Generated by `php artisan kompact:ide-helper, don't edit manually.")
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

        file_put_contents($path, str_concat(
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            NEW_LINE
        ));
    }
}