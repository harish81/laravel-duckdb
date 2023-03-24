# DuckDB CLI wrapper to interact with duckdb databases through laravel query builder.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/harish81/laravel-duckdb.svg?style=flat-square)](https://packagist.org/packages/harish81/laravel-duckdb)
[![Total Downloads](https://img.shields.io/packagist/dt/harish81/laravel-duckdb.svg?style=flat-square)](https://packagist.org/packages/harish81/laravel-duckdb)

https://github.com/duckdb/duckdb
- Download CLI
    - https://duckdb.org/docs/installation/
    - https://github.com/duckdb/duckdb/releases/latest

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
class GenderDataModel extends \HarishDuckDB\LaravelDuckdbModel
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


## Testing

```bash
composer test
```

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
