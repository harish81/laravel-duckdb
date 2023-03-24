<?php

namespace Harish\LaravelDuckdb\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Support\Str;

class Grammar extends PostgresGrammar
{
    protected function compileFrom(Builder $query, $table)
    {
        if($this->isExpression($table)) {
            return parent::compileFrom($query, $table);
        }
        if (stripos($table, ' as ') !== false) {
            $segments = preg_split('/\s+as\s+/i', $table);
            return "from ".$this->wrapFromClause($segments[0], true)
                    ." as "
                    .$this->wrapFromClause($segments[1]);
        }

        return "from ".$this->wrapFromClause($table, true);

    }

    private function wrapFromClause($value, $prefixAlias = false){
        if(!Str::endsWith($value, ')')){//is function
            return $this->quoteString(($prefixAlias?$this->tablePrefix:'').$value);
        }
        return  ($prefixAlias?$this->tablePrefix:'').$value;
    }
}
