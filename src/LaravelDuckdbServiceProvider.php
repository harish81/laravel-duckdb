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
            'pre_queries' => [],
            'extensions' => []
        ];

        $this->app->resolving('db', function ($db) use ($defaultConfig) {
            $db->extend('duckdb', function ($config, $name) use ($defaultConfig) {
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
