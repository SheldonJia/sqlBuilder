<?php
namespace DB\Helper\Lib;

use DB\Helper\Lib\Base\base;
use DB\Helper\Lib\Base\sqlBase;
use DB\Helper\sqlBuilder;
use Exception;

class in extends base implements sqlBase {
    public mixed $data;

    /**
     * in
     * @param array $data
     * @throws Exception 如果参数1不是数组将会爆出 '传递给 set 方法的参数必须是一个数组'
     */
    public function __construct(array $data){
        $this->set($data);
    }

    /**
     * 到文本方法
     * @throws Exception
     */
    public function __toString(): string{
        return !$this->builder ? throw new Exception('Builder 未设置') : $this->get($this->builder);
    }

    public function __debugInfo(): array|null{
        return [
            '运算符号'=>'IN',
            '值'=>$this->data,
            '所属导演类'=> $this->builder ?? '暂无'
        ];
    }

    /**
     * set 方法
     * @param mixed ...$p 在本类中 只接受1个参数 为值
     * @throws Exception 如果参数1不是数组将会爆出 '传递给 set 方法的参数必须是一个数组'
     */
    public function set(...$p): void{
        if (!is_array($p[0])) {
            throw new Exception('传递给 set 方法的参数必须是一个数组');
        }
        $this->data = $p[0];
    }

    /**
     * get 方法
     * @throws Exception
     */
    public function get(sqlBuilder $builder): string{
        $_this = $this;
        try {
            $arr = array_map(function ($_item) use ($_this,$builder){
                return ($_this::isSqlKey($_item) ? $_item : $builder->setBind($_item));
            },$this->data);
            $return = ' IN(' . implode(',',$arr) . ')';
        }catch (Exception $e){
            throw new Exception('在生成 IN 条件时发生错误：' . $e->getMessage());
        }
        return $return;
    }
}