<?php

namespace Harish\LaravelDuckdb;

use Harish\LaravelDuckdb\Commands\ConnectDuckdbCliCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Harish\LaravelDuckdb\Commands\DownloadDuckDBCliCommand;

class LaravelDuckdbPackageServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-duckdb')
            ->hasCommand(DownloadDuckDBCliCommand::class)
            ->hasCommand(ConnectDuckdbCliCommand::class);
    }
}
