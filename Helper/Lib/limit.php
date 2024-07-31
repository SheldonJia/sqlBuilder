<?php
namespace DB\Helper\Lib;

use DB\Helper\Lib\Base\sqlBase;
use DB\Helper\sqlBuilder;
use DB\Helper\Lib\Base\base;

use Exception;

class limit extends base implements sqlBase {
    public int $offset;
    public int $count;
    /**
     * 到文本方法
     * @throws Exception
     */
    public function __toString(): string{
        return !$this->builder ? throw new Exception('Builder 未设置') : $this->get($this->builder);
    }

    public function __debugInfo(): array|null{
        return [
            'offset'=>$this->offset,
            'count'=>$this->count,
            '所属导演类'=> $this->builder ?? '暂无'
        ];
    }

    public function setBuilder(sqlBuilder $builder): static{
        parent::setBuilder($builder);
        return $this;
    }

    /**
     * set 方法
     * @param mixed ...$p 在本类中 只接受2个参数 第一个参数offset 第二个参数为count
     */
    public function set(...$p): void{
        $this->offset = $p[0];
        $this->count = $p[1];
    }

    /**
     * get 方法
     */
    public function get(sqlBuilder $builder): string{
        return ' LIMIT ' . $this->offset . ',' . $this->count;
    }
}