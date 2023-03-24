<?php

namespace Harish\LaravelDuckdb\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadDuckDBCliCommand extends Command
{
    protected $signature = 'download:duckdb-cli';

    protected $description = 'Download DuckDB Cli';

    public function handle(): void
    {
        $os = strtolower(php_uname('s'));
        $arch = strtolower(php_uname('m'));

        $this->info("OS: $os, Architecture: $arch");
        $this->newLine();

        if(in_array($os, ['linux'])){ //linux
            if(in_array($arch, ['x86_64', 'amd64'])){
                $this->downloadCli('linux', 'amd64');
            }else{
                $this->downloadCli('linux', $arch);
            }
        } elseif (in_array($os, ['darwin'])){
            $this->downloadCli('osx', 'universal');
        }else{
            throw new \Exception('Not Supported! Currently Only linux, mac supported. Try manually downloading cli from: https://duckdb.org/docs/installation/');
            return;
        }
    }

    private function downloadCli($os, $arch){
        $duck_base_url = "https://github.com/duckdb/duckdb/releases/latest/download/duckdb_cli-__OS__-__PLATEFORM__.zip";

        $url = str_replace(array('__OS__', '__PLATEFORM__'), array($os, $arch), $duck_base_url);

        $this->info("Downloading cli($url)...");
        $this->newLine();
        $res = Http::timeout(10*60)
                ->retry(2, 100)
                ->get($url);

        $content = $res->body();
        //'vendor/bin/duckdb'
        file_put_contents('/tmp/duckdb_cli.zip', $content);

        $this->info('Extracting cli...');
        $this->newLine();
        $zip = new \ZipArchive();
        $zipRes = $zip->open('/tmp/duckdb_cli.zip');
        if($zipRes){
            $zip->extractTo(base_path('vendor/bin/'));
            $zip->close();

            chmod(base_path('vendor/bin/duckdb'), 0755);
            $this->info('Done! cli located at `'.base_path('vendor/bin/duckdb').'`');
        }
    }
}
