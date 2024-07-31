<?php
namespace DB\Helper\Lib;

use DB\Helper\Lib\Base\sqlBase;
use DB\Helper\sqlBuilder;
use DB\Helper\Lib\Base\base;

use Exception;

class group extends base implements sqlBase {
    public array $val;

    /**
     * 到文本方法
     * @throws Exception
     */
    public function __toString(): string{
        return !$this->builder ? throw new Exception('Builder 未设置') : $this->get($this->builder);
    }

    public function __debugInfo(): array|null{
        return [
            '值'=>$this->val,
            '所属导演类'=> $this->builder ?? '暂无'
        ];
    }

    public function setBuilder(sqlBuilder $builder): static{
        parent::setBuilder($builder);
        return $this;
    }

    /**
     * set 方法
     * @param mixed ...$p 在本类中 只接受1个参数 第一个参数为字段名
     */
    public function set(...$p): void{
        $this->val[] = $p[0];
    }

    /**
     * get 方法
     */
    public function get(sqlBuilder $builder): string{
        return ' GROUP BY ' . implode(',',$this->val);
    }
}