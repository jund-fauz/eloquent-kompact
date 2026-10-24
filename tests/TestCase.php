<?php

namespace JundFauz\EloquentKompact\Tests;

use JundFauz\EloquentKompact\EloquentKompactServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [EloquentKompactServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    /**
     * Switches the default connection to the given driver without connecting to a real database
     */
    protected function useDriver(string $driver): void
    {
        config([
            "database.connections.fake_$driver" => ['driver' => $driver, 'database' => 'fake'],
            'database.default' => "fake_$driver",
        ]);
    }
}
