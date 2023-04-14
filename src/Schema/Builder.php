<?php

namespace Harish\LaravelDuckdb\Schema;

use Closure;
use Illuminate\Support\Facades\File;

class Builder extends \Illuminate\Database\Schema\PostgresBuilder
{
    public static $alwaysUsesNativeSchemaOperationsIfPossible=true;
    public function createDatabase($name)
    {
        return File::put($name, '') !== false;
    }

    public function dropDatabaseIfExists($name)
    {
        return File::exists($name)
            ? File::delete($name)
            : true;
    }

    protected function createBlueprint($table, Closure $callback = null)
    {
        $this->blueprintResolver(function ($table, $callback, $prefix) {
            return new Blueprint($table, $callback, $prefix);
        });
        return parent::createBlueprint($table, $callback);
    }
}
