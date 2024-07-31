<?php

namespace DB\Helper\Lib;

use DB\Helper\Lib\Base\base;
use DB\Helper\sqlBuilder;
use Exception;

class where extends base {
    private string $table;

    private mixed $field;
    private mixed $rightPart;
    private string $com;

    public function __construct(string $table){
        $this->table = $table;
    }

    public function __toString(): string{
        return $this->get();
    }

    public function __debugInfo(): array|null{
        return [
            '表名'=>$this->table,
            '字段'=>$this->field,
            '右侧信息'=>$this->rightPart,
            '连接符'=>$this->com,
            '所属导演类'=> $this->builder ?? '暂无'
        ];
    }

    public function setBuilder(sqlBuilder $builder): static{
        parent::setBuilder($builder);
        $this->rightPart->setBuilder($builder);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function set(mixed $key, string $sym, mixed $val, string $com = ' AND '): static{
        $this->field = $key;
        $this->rightPart = parent::choose($sym,$val);
        $this->com = $com;
        return $this;
    }

    public function get(): string{
        return $this->rightPart instanceof raw ? $this->rightPart : '`' . $this->table . '`.`' . $this->field . '`' . $this->rightPart;
    }

    public function getCom(): string{
        return $this->com;
    }
}