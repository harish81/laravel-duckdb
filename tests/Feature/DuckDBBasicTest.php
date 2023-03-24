<?php

namespace Harish\LaravelDuckdb\Tests\Feature;

use Harish\LaravelDuckdb\LaravelDuckdbModel;
use Harish\LaravelDuckdb\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

//Test model
class DuckTestDataModel extends \Harish\LaravelDuckdb\LaravelDuckdbModel
{
    protected $connection = 'my_duckdb';
    public function __construct()
    {
        $this->table = '/var/www/harish-duckdb/_test-data/test.csv';
    }
}

class DuckDBBasicTest extends TestCase
{
    public function test_cli_download(){
        Artisan::call('download:duckdb-cli');
        $this->assertFileExists(base_path('vendor/bin/duckdb'));
    }
    public function test_simple()
    {
        $rs = DB::connection('my_duckdb')->selectOne('select 1');
        $this->assertArrayHasKey(1, $rs);
    }

    public function test_read_csv(){
        $rs = DB::connection('my_duckdb')
            ->table($this->getPackageBasePath('_test-data/test.csv'))
            ->get();

        $this->assertNotEmpty($rs);
    }

    public function test_eloquent_model(){

        $rs = DuckTestDataModel::where('VALUE_IN_EUROS',59712)
            ->first()->toArray();
        $this->assertNotEmpty($rs);
    }
}
