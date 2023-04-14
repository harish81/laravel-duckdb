# DuckDB CLI wrapper to interact with duckdb databases through laravel query builder.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/harish81/laravel-duckdb.svg?style=flat-square)](https://packagist.org/packages/harish81/laravel-duckdb)
[![Total Downloads](https://img.shields.io/packagist/dt/harish81/laravel-duckdb.svg?style=flat-square)](https://packagist.org/packages/harish81/laravel-duckdb)

https://github.com/duckdb/duckdb
- Download CLI (either)
    - https://duckdb.org/docs/installation/
    - https://github.com/duckdb/duckdb/releases/latest
    - run `php artisan laravel-duckdb:download-cli` (Experimental)

## Support us

## Installation

You can install the package via composer:

```bash
composer require harish81/laravel-duckdb
```

## Usage

- Connect
```php
'connections' => [
    'my_duckdb' => [
        'driver' => 'duckdb',
        'cli_path' => env('DUCKDB_CLI_PATH', base_path('vendor/bin/duckdb')),
        //'dbfile' => env('DUCKDB_DB_FILE', '/tmp/duck_main.db'),
    ],
...
```

- Examples
```php
# Using DB facade
DB::connection('my_duckdb')
    ->table(base_path('genderdata.csv'))
    ->where('Gender', '=', 'M')
    ->limit(10)
    ->get();
```
```php
# Using Raw queries
DB::connection('my_duckdb')
    ->select("select * from '".base_path('genderdata.csv')."' limit 5")
```

```php
# Using Eloquent Model
class GenderDataModel extends \Harish\LaravelDuckdb\LaravelDuckdbModel
{
    protected $connection = 'my_duckdb';
    public function __construct()
    {
        $this->table = base_path('genderdata.csv');
    }
}
...
GenderDataModel::where('Gender','M')->first()
```

## Advanced Usage
You can install duckdb extensions too.

### Query data from s3 files directly.

- in `database.php`
```php
'connections' => [
    'my_duckdb' => [
        'driver' => 'duckdb',
        'cli_path' => env('DUCKDB_CLI_PATH', base_path('vendor/bin/duckdb')),
        'cli_timeout' => 0, //0 to disable timeout, default to 1 Minute (60s)
        'dbfile' => env('DUCKDB_DB_FILE', storage_path('app/duckdb/duck_main.db')),
        'pre_queries' => [
            "SET s3_region='".env('AWS_DEFAULT_REGION')."'",
            "SET s3_access_key_id='".env('AWS_ACCESS_KEY_ID')."'",
            "SET s3_secret_access_key='".env('AWS_SECRET_ACCESS_KEY')."'",
        ],
        'extensions' => ['httpfs'],
    ],
    ...
```

- Query data
```php
DB::connection('my_duckdb')
  ->select("SELECT * FROM read_csv_auto('s3://my-bucket/test-datasets/example1/us-gender-data-2022.csv') LIMIT 10")
```
### Writing a migration
```php
return new class extends Migration {
    protected $connection = 'my_duckdb';
    public function up(): void
    {
        DB::connection('my_duckdb')->statement('CREATE SEQUENCE people_sequence');
        Schema::create('people', function (Blueprint $table) {
            $table->id()->default(new \Illuminate\Database\Query\Expression("nextval('people_sequence')"));
            $table->string('name');
            $table->integer('age');
            $table->integer('rank');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
        DB::connection('my_duckdb')->statement('DROP SEQUENCE people_sequence');
    }
};
```

### Readonly Connection - A solution to concurrent query.
- in `database.php`
```php
    'connections' => [
        'my_duckdb' => [
            'driver' => 'duckdb',
            'cli_path' => env('DUCKDB_CLI_PATH', base_path('vendor/bin/duckdb')),
            'cli_timeout' => 0,
            'dbfile' => env('DUCKDB_DB_FILE', storage_path('app/duckdb/duck_main.db')),
            'schema' => 'main',
            'read_only' => true,
            'pre_queries' => [
                "SET s3_region='".env('AWS_DEFAULT_REGION')."'",
                "SET s3_access_key_id='".env('AWS_ACCESS_KEY_ID')."'",
                "SET s3_secret_access_key='".env('AWS_SECRET_ACCESS_KEY')."'",
            ],
            'extensions' => ['httpfs', 'postgres_scanner'],
        ],
        ...
```


## Testing

- Generate test data
```bash
# Syntax: ./data-generator.sh <lines> <file-to-save.csv>
./data-generator.sh 100 _test-data/test.csv
./data-generator.sh 90000000 _test-data/test_big_file.csv
```

- Run Test case
```bash
composer test
```

## Limitations & FAQ

-  https://duckdb.org/faq

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [harish](https://github.com/harish81)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
