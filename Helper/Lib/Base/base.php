<?php

namespace DB\Helper\Lib\Base;

use DB\Helper\Lib\def;
use DB\Helper\Lib\in;
use DB\Helper\Lib\like;
use DB\Helper\Lib\notIn;
use DB\Helper\Lib\raw;
use DB\Helper\sqlBuilder;
use Exception;

class base{
    protected sqlBuilder|null $builder = null;

    public const bool IS_SQL_KEY = true;

    static protected function isSqlKey(mixed $item): bool{
        return (is_object($item) && defined(get_class($item) . '::IS_SQL_KEY') && $item::IS_SQL_KEY === true);
    }

    public function setBuilder(sqlBuilder $builder): static{
        if(empty($this->builder)) $this->builder = $builder;
        return $this;
    }

    /**
     * @throws Exception
     */
    static protected function choose(string $sym, mixed $val): object{
        return match ($sym) {
            '=', '!=', '<>', '<', '<=', '>', '>=' => new def($sym,$val),
            'LIKE' => new like($val),
//            'BETWEEN', 'BETWEEN AND', 'BETWEENAND' => (new between(...$this->value))->toStr($this->builder),
            'IN' => new in($val),
            'NOTIN', 'NOT IN' => new notIn($val),
//          'IS NULL', 'ISNULL' => (new nothing())->toStr($this->builder),
//          'ISNOTNULL', 'IS NOT NULL', 'ISNOT NULL', 'IS NOTNULL' => (new notNothing())->toStr($this->builder),
//          'REGEXP' => (new regExp($this->value))->toStr($this->builder),
            'RAW' => $val,
            default => throw new Exception('参数一为非法字符')
        };
    }
}