<?php

namespace JundFauz\EloquentKompact;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Console\ColumnBuilderCommand;
use JundFauz\EloquentKompact\Console\IdeHelperCommand;
use JundFauz\EloquentKompact\Console\MathCommand;
use JundFauz\EloquentKompact\Internal\Path\Path;
use JundFauz\EloquentKompact\Model\ModelCache;
use JundFauz\EloquentKompact\Schema\Blueprint;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;
use function config;
use function JundFauz\EloquentKompact\String\concat;

/** @final */
class EloquentKompactServiceProvider extends ServiceProvider
{
    /**
     * Merges the default `eloquent-kompact` package configuration and binds the Kompact schema `Blueprint`.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(Path::fileConfig(), 'eloquent-kompact');
        $this->app->bind(BaseBlueprint::class, Blueprint::class);
    }

    /**
     * Bootstraps translations, publishable assets, Artisan commands, morph map, and migration listeners.
     *
     * @return void
     * @throws Exception
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(Path::lang(), 'eloquent-kompact');
        $this->loadJsonTranslationsFrom(Path::lang());
        $this->publish();
        $this->configureArtisan();
        $this->configureMorphMap();
        $this->eventListener();
    }

    /**
     * Registers publishable translation and configuration files.
     *
     * @return void
     */
    public function publish(): void
    {
        $this->publishes([
            Path::lang() => $this->app->langPath('vendor/eloquent-kompact'),
        ], 'eloquent-kompact-lang');

        $this->publishes([
            Path::fileConfig() => config_path('eloquent-kompact.php')
        ], 'eloquent-kompact-config');
    }

    /**
     * Registers package Artisan commands when running in the console.
     *
     * @return void
     */
    public function configureArtisan(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                IdeHelperCommand::class,
                ColumnBuilderCommand::class,
                MathCommand::class
            ]);
        }
    }

    /**
     * Enforces Eloquent's morph map from concrete models under `path.model` when configured.
     *
     * @return void
     * @throws Exception
     */
    protected function configureMorphMap(): void
    {
        if ($config = config('eloquent-kompact.morph_model_use')) {
            if (is_not('in_array', $config, ['table', 'model'], true)) {
                throw new Exception("$config as morph doesn't available yet. Request new feature at https://github.com/jund-fauz/eloquent-kompact/issues/new or give your contribution at https://github.com/jund-fauz/eloquent-kompact/pulls");
            }
            $modelNamespace = config('eloquent-kompact.path.model');
            $namespace = ! Str::endsWith($modelNamespace, NAMESPACE_DELIMITER) ? concat($modelNamespace, NAMESPACE_DELIMITER) : $modelNamespace;
            $path = full_path_on_app($modelNamespace);

            if (is_not('dir', $path)) {
                throw new Exception("$path doesn't exist in your current codebase.");
            }

            $map = collect(File::files($path))
                ->map(fn (SplFileInfo $file) => "$namespace{$file->getFilenameWithoutExtension()}")
                ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract())
                ->mapWithKeys(function (string $class) use ($config) {
                    return [match ($config) {
                        'table' => (new ReflectionClass($class))->newInstanceWithoutConstructor()->getTable(),
                        'model' => class_basename($class)
                    } => $class];
                })
                ->all();

            Relation::enforceMorphMap($map);
        }
    }

    /**
     * Listens for `MigrationsEnded` to flush static caches and regenerate local column builders and IDE helpers.
     *
     * @return void
     */
    public function eventListener(): void
    {
        Event::listen(MigrationsEnded::class, function () {
            ColumnListing::flush();
            ModelCache::flush();

            if ($this->app->isLocal()) {
                foreach (ColumnBuilderCommand::generatedNamespaces() as $namespace) {
                    Artisan::call(ColumnBuilderCommand::class, ['--output' => $namespace]);
                }
            }

            if ($this->app->isLocal() && config('eloquent-kompact.ide-helper.auto_generate')) {
                Artisan::call(IdeHelperCommand::class);
            }
        });
    }
}