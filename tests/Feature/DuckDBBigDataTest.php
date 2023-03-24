<?php

namespace Harish\LaravelDuckdb\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Harish\LaravelDuckdb\Tests\TestCase;

class DuckDBBigDataTest extends TestCase
{
    public function test_import_csv()
    {
        $this->assertTrue(
            DB::connection('my_duckdb')->statement("create or replace table test_big_file as select * from '".$this->getPackageBasePath('_test-data/test_big_file.csv')."'")
        );
    }

    public function test_simple_query(){
        $rawQueryRs = DB::connection('my_duckdb')->select("select * from test_big_file limit 1000");
        $this->assertCount(1000, $rawQueryRs);

        $dbFQuery = DB::connection('my_duckdb')
            ->table('test_big_file')
            ->limit(1000)
            ->get()->toArray();
        $this->assertCount(1000, $dbFQuery);
    }

    public function test_count_query(){
        $rs = DB::connection('my_duckdb')->selectOne("select count(*) as total_count from test_big_file");
        $this->assertEquals(24754705, $rs['total_count']);
    }

    public function test_groupby_query(){
        $rs = DB::connection('my_duckdb')
            ->select("select substr(PRODUCT_NC, 1, 2) as hs_chapter, CAST(SUM(VALUE_IN_EUROS) as bigint) as sum_value, count(*) as total_rec from test_big_file
                group by hs_chapter
                order by hs_chapter");

        $this->assertCount(98, $rs);
    }
}
