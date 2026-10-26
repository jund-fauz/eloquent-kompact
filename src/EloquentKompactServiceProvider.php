<?php

namespace JundFauz\EloquentKompact;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use JundFauz\EloquentKompact\Column\ColumnListing;
use JundFauz\EloquentKompact\Helper\Path;
use JundFauz\EloquentKompact\Model\KompactModel;
use JundFauz\EloquentKompact\Model\KompactPivot;

class EloquentKompactServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(Path::fileConfig(), 'eloquent-kompact');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(Path::lang(), 'eloquent-kompact');
        $this->loadJsonTranslationsFrom(Path::lang());

        $this->publishes([
            Path::lang() => $this->app->langPath('vendor/eloquent-kompact'),
        ], 'eloquent-kompact-lang');

        $this->publishes([
            Path::fileConfig() => config_path('eloquent-kompact.php')
        ], 'eloquent-kompact-config');

        Event::listen(MigrationsEnded::class, function () {
            ColumnListing::flush();
            KompactModel::flushKompactCache();
            KompactPivot::flushKompactCache();
        });
    }
}