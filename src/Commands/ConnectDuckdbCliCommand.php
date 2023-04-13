<?php

namespace Harish\LaravelDuckdb\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ConnectDuckdbCliCommand extends Command
{
    protected $signature = 'laravel-duckdb:connect {connection_name} {--readonly=true}';

    protected $description = 'Connect with duckdb cli to interactive query and development.';

    private Process $process;

    public function handle(): void
    {
        $connection = config('database.connections.'.$this->argument('connection_name'));
        $isReadonly = filter_var($this->option('readonly'), FILTER_VALIDATE_BOOLEAN);
        if(!$connection || ($connection['driver']??'') !== 'duckdb') throw new \Exception("DuckDB connection named `".$this->argument('connection_name')."` not found!");

        $cmd = [
            $connection['cli_path'],
            $connection['dbfile']
        ];
        if($isReadonly) array_splice($cmd, 1, 0, '--readonly');

        $this->info('Connecting to duckdb cli `'.implode(" ", $cmd).'`');
        $this->process = new Process($cmd);
        $this->process->setTimeout(0);
        $this->process->setIdleTimeout(0);
        $this->process->setTty(Process::isTtySupported());

        $this->process->run();
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): void
    {
        $this->info('stopping...');
        $this->process->signal($signal);
    }
}
