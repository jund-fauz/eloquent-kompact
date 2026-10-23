<?php

namespace JundFauz\EloquentKompact;

use Illuminate\Support\ServiceProvider;

class EloquentKompactServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'eloquent-kompact');
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/eloquent-kompact'),
        ], 'eloquent-kompact-lang');
    }
}