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

        $distinct_foo_codes = DB::connection('my_duckdb')->select("select DISTINCT FOO_CODE as FOO_CODE from test_big_file");
        $distinct_foo_codes = collect($distinct_foo_codes)->flatten()->toArray();
        $countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");

        $final_foo_tbl = [];

        foreach ($distinct_foo_codes as $foo_code) {
            $final_foo_tbl[] = [
                'CODE' => $foo_code,
                'COUNTRY' => str_replace("'", "''", $countries[array_rand($countries)]),
            ];
        }

        DB::connection('my_duckdb')->statement("drop table if exists foo_locations");
        DB::connection('my_duckdb')->statement("create table foo_locations(CODE text, COUNTRY text)");
        DB::connection('my_duckdb')->table('foo_locations')->insert($final_foo_tbl);
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
        $this->assertGreaterThan(1000000, $rs['total_count']);
    }

    public function test_groupby_query(){
        $rs = DB::connection('my_duckdb')
            ->select("select upper(PERSON) as person_name, CAST(SUM(VALUE) as hugeint) as sum_value, count(*) as total_rec from test_big_file
                group by person_name
                order by person_name");

        $this->assertLessThanOrEqual(10, count($rs));
    }

    public function test_join_query(){
        $rs = DB::connection('my_duckdb')
            ->select("select FOO_CODE, CAST(SUM(VALUE) as hugeint) as sum_value, count(*) as total_rec, COUNTRY
                from test_big_file
                left join foo_locations on FOO_CODE = CODE
                group by FOO_CODE,COUNTRY
                order by FOO_CODE");

        $this->assertNotEmpty($rs);
    }

    public function test_summarize_table(){
        $rs = DB::connection('my_duckdb')
            ->select("SUMMARIZE test_big_file");

        $this->assertTrue(count($rs)>0 && array_key_exists('approx_unique', $rs[0]));
    }
}
