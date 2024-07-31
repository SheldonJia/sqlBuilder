<?php
namespace DB\Helper\Lib;

use DB\Helper\Lib\Base\base;
use DB\Helper\Lib\Base\sqlBase;
use DB\Helper\sqlBuilder;
use Exception;


/**
 *
 */
class like extends base implements sqlBase {
    private mixed $data;

    /**
     * LIKE
     * @param mixed $data
     */
    public function __construct(mixed $data){
        $this->set($data);
    }

    /**
     * 到文本方法
     * @throws Exception 第一参数不能接受除：=,!=,<>,<,<=,>,>= 以外的值
     */
    public function __toString(): string{
        return !$this->builder ? throw new Exception('Builder 未设置') : $this->get($this->builder);
    }

    public function __debugInfo(): array|null{
        return [
            '运算符号'=>'LIKE',
            '值'=>$this->data,
            '所属导演类'=> $this->builder ?? '暂无'
        ];
    }

    /**
     * set 方法
     * @param mixed ...$p 在本类中 只接受1个参数 为值
     */
    public function set(...$p): void{
        $this->data = $p[0];
    }

    /**
     * get 方法
     * @throws Exception
     */
    public function get(sqlBuilder $builder): string{
        try {
            $return = ' LIKE ' . (parent::isSqlKey($this->data) ? $this->data : $builder->setBind((string)$this->data));
        }catch (Exception $e){
            throw new Exception('在生成 LIKE 条件时发生错误：' . $e->getMessage());
        }
        return $return;
    }
}