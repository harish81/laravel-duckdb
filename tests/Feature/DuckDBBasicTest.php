<?php

namespace Harish\LaravelDuckdb\Tests\Feature;

use Harish\LaravelDuckdb\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

//Test model
class DuckTestDataModel extends \Harish\LaravelDuckdb\LaravelDuckdbModel
{
    protected $connection = 'my_duckdb';
    public function __construct()
    {
        $this->table = realpath(__DIR__.'/../../_test-data/test.csv');
    }
}

class DuckDBBasicTest extends TestCase
{
    public function test_cli_download(){
        Artisan::call('laravel-duckdb:download-cli');
        $this->assertFileExists(base_path('vendor/bin/duckdb'));
    }

    /*public function test_connect_command_download(){
        $opt = Artisan::call('laravel-duckdb:connect', ['connection_name' => 'my_duckdb']);
        $this->assertEquals(1, $opt);
    }*/

    public function test_simple()
    {
        $rs = DB::connection('my_duckdb')->selectOne('select 1');
        $this->assertArrayHasKey(1, $rs);
    }

    public function test_binding_escape_str(){
        $str = "Co'mpl''ex` \"st'\"ring \\0 \\n \\r \\t `myworld`";
        $rs = DB::connection('my_duckdb')->selectOne('select ? as one', [$str]);

        $this->assertEquals($str, $rs['one']);
    }

    public function test_read_csv(){
        $rs = DB::connection('my_duckdb')
            ->table($this->getPackageBasePath('_test-data/test.csv'))
            ->get();

        $this->assertNotEmpty($rs);
    }

    public function test_eloquent_model(){

        $rs = DuckTestDataModel::where('VALUE','>',59712)
            ->first()->toArray();
        $this->assertNotEmpty($rs);
    }
}
