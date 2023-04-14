<?php

namespace Harish\LaravelDuckdb;

use Harish\LaravelDuckdb\LaravelDuckdbModel as Model;
use Illuminate\Support\ServiceProvider;

class LaravelDuckdbServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $defaultConfig = [
            'cli_path' => base_path('vendor/bin/duckdb'),
            'cli_timeout' => 60,
            'dbfile' => storage_path('app/duckdb/duck_main.db'),
            //'database' => 'duck_main' //default to filename of dbfile, in most case no need to specify manually
            'schema' => 'main',
            'read_only' => false,
            'pre_queries' => [],
            'extensions' => []
        ];

        $this->app->resolving('db', function ($db) use ($defaultConfig) {
            $db->extend('duckdb', function ($config, $name) use ($defaultConfig) {
                $defaultConfig['database'] = pathinfo($config['dbfile'], PATHINFO_FILENAME);

                $config = array_merge($defaultConfig, $config);
                $config['name'] = $name;
                return new LaravelDuckdbConnection($config);
            });
        });
    }

    public function boot(): void
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }
}
