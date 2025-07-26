<?php

namespace Ottosmops\Settings\Tests;

use Ottosmops\Settings\SettingsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        
        // Load migrations and run them
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate')->run();
    }

    protected function getPackageProviders($app)
    {
        return [
            SettingsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);
    }
}
