<?php

namespace Harish\LaravelDuckdb;

use Harish\LaravelDuckdb\Query\Builder;
use Harish\LaravelDuckdb\Query\Grammar as QueryGrammar;
use Harish\LaravelDuckdb\Query\Processor;
use Harish\LaravelDuckdb\Schema\Grammar as SchemaGrammar;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class LaravelDuckdbConnection extends PostgresConnection
{
    private $installed_extensions = [];
    public function __construct($config)
    {
        $this->database = $config['database'];
        $this->config = $config;
        $this->config['dbfile'] = $config['dbfile'];

        $this->useDefaultPostProcessor();
        $this->useDefaultSchemaGrammar();
        $this->useDefaultQueryGrammar();

        $this->ensureDuckdbDirectory();
        $this->ensureDuckCliExists();
        $this->installExtensions();
    }

    public function query()
    {
        return $this->getDefaultQueryBuilder();
    }

    public function table($table, $as = null)
    {
        return $this->query()->from($table, $as);
    }

    private function quote($str)
    {
        if(extension_loaded('sqlite3')){
            return "'".\SQLite3::escapeString($str)."'";
        }
        if(extension_loaded('pdo_sqlite')){
            return (new \PDO('sqlite::memory:'))->quote($str);
        }

        return "'".preg_replace("/'/m", "''", $str)."'";
    }

    private function getDuckDBCommand($query, $bindings = [], $safeMode=false){
        $escapeQuery = $query;
        $countBindings = count($bindings??[]);
        if($countBindings>0){
            foreach ($bindings as $index => $val) {
                $escapeQuery = Str::replaceFirst('?', $this->quote($val), $escapeQuery);
            }
        }

        //disable progressbar on long queries
        $disable_progressbar = "SET enable_progress_bar=false";
        $preQueries = [$disable_progressbar];
        foreach ($this->installed_extensions as $extension) {
            $preQueries[] = "LOAD '$extension'";
        }

        $preQueries = array_merge($preQueries, $this->config['pre_queries']??[]);
        $cmdParams = [
            $this->config['cli_path'],
            $this->config['dbfile'],
        ];
        if($this->config['read_only']) array_splice($cmdParams, 1, 0, '--readonly');
        if(!$safeMode) $cmdParams = array_merge($cmdParams, $preQueries);
        $cmdParams = array_merge($cmdParams, [
            "$escapeQuery",
            "-json"
        ]);
        return $cmdParams;
    }

    private function installExtensions(){
        if(empty($this->config['extensions']??[])) return;

        $cacheKey = $this->config['name'].'_duckdb_extensions';
        $duckdb_extensions = Cache::rememberForever($cacheKey, function (){
            return $this->executeDuckCliSql("select * from duckdb_extensions()", [], true);
        });
        $sql = [];
        $tobe_installed_extensions = [];
        foreach ($this->config['extensions'] as $extension_name) {
            $ext = collect($duckdb_extensions)->where('extension_name', $extension_name)->first();
            if($ext){
                if(!$ext['installed'])
                    $sql[$extension_name] = "INSTALL '$extension_name'";

                $tobe_installed_extensions[] = $extension_name;
            }
        }
        if(!empty($sql)) Cache::forget($cacheKey);
        foreach ($sql as $ext_name=>$sExtQuery) {
            $this->executeDuckCliSql($sExtQuery, [], true);
        }
        $this->installed_extensions=$tobe_installed_extensions;
    }

    private function ensureDuckCliExists(){
        if(!file_exists($this->config['cli_path'])){
            throw new FileNotFoundException("DuckDB CLI Not Found. Make sure DuckDB CLI exists and provide valid `cli_path`. Download CLI From https://duckdb.org/docs/installation/index or run `artisan laravel-duckdb:download-cli`");
        }
    }

    private function ensureDuckdbDirectory(){
        if(!is_dir(storage_path('app/duckdb'))){
            if (!mkdir($duckDirectory = storage_path('app/duckdb')) && !is_dir($duckDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $duckDirectory));
            }
        }
    }

    private function executeDuckCliSql($sql, $bindings = [], $safeMode=false){

        $command = $this->getDuckDBCommand($sql, $bindings, $safeMode);
        $process = new Process($command);
        $process->setTimeout($this->config['cli_timeout']);
        $process->setIdleTimeout(0);
        $process->run();

        if (!$process->isSuccessful()) {
            $err = $process->getErrorOutput();
            if(str_starts_with($err, 'Error:')){
                $finalErr = trim(substr_replace($err, '', 0, strlen('Error:')));
                throw new QueryException($this->getName(), $sql, $bindings, new \Exception($finalErr));
            }

            throw new ProcessFailedException($process);
        }

        $raw_output = trim($process->getOutput());
        return json_decode($raw_output, true)??[];
    }

    private function runQueryWithLog($query, $bindings=[]){
        $start = microtime(true);

        //execute
        $result = $this->executeDuckCliSql($query, $bindings);

        $this->logQuery(
            $query, [], $this->getElapsedTime($start)
        );

        return $result;
    }

    public function statement($query, $bindings = [])
    {
        $this->runQueryWithLog($query, $bindings);

        return true;
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->runQueryWithLog($query, $bindings);
    }

    public function affectingStatement($query, $bindings = [])
    {
        //for update/delete
        //todo: we have to use : returning * to get list of affected rows; currently causing error;
        return $this->runQueryWithLog($query, $bindings);
    }

    private function getDefaultQueryBuilder(){
        return new Builder($this, $this->getDefaultQueryGrammar(), $this->getDefaultPostProcessor());
    }

    public function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    public function getDefaultPostProcessor()
    {
        return new Processor();
    }

    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new \Harish\LaravelDuckdb\Schema\Builder($this);
    }

    public function useDefaultSchemaGrammar()
    {
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar;
    }

    /**
     * Get the schema grammar used by the connection.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    public function getSchemaGrammar()
    {
        return $this->schemaGrammar;
    }
}
