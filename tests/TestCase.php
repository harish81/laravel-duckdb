<?php

namespace Harish\LaravelDuckdb\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    private $basePath;

    protected function getPackageBasePath($path){
        //return $this->basePath.'/'.$path;
        return '/var/www/harish-duckdb'.'/'.$path;
    }
    protected function setUp(): void
    {
        $this->basePath = __DIR__;
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Harish\LaravelDuckdb\LaravelDuckdbPackageServiceProvider::class,
            \Harish\LaravelDuckdb\LaravelDuckdbServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'ZsZewWyUJ5FsKp9lMwv4tYbNlegQilM7');

        $app['config']->set('database.connections.my_duckdb', [
            'driver' => 'duckdb',
            'cli_path' => base_path('vendor/bin/duckdb'),
            'cli_timeout' => 0,
            'dbfile' => '/tmp/duck_main.db',
        ]);
    }
}
