<?php
namespace DB\Helper\Lib;

use DB\Helper\Lib\Base\sqlBase;
use DB\Helper\sqlBuilder;
use DB\Helper\Lib\Base\base;

use Exception;

class def extends base implements sqlBase {
    public string $sym;
    public mixed $val;

    /**
     * 构造方法
     * @param string $sym 符号
     * @param mixed $val 值
     * @throws Exception 第一参数不能接受除：=,!=,<>,<,<=,>,>= 以外的值
     */
    public function __construct(string $sym,mixed $val){
        $this->set($sym,$val);
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
            '运算符号'=>$this->sym,
            '值'=>$this->val,
            '所属导演类'=> $this->builder ?? '暂无'
        ];
    }

    /**
     * set 方法
     * @param mixed ...$p 在本类中 只接受2个参数 第一个参数为符号参数 第二个为值
     * @throws Exception 第一参数不能接受除：=,!=,<>,<,<=,>,>= 以外的值
     */
    public function set(...$p): void{
        if(!in_array($p[0],['=','!=','<>','<','<=','>','>='])) throw new Exception('第一参数不能接受除：=,!=,<>,<,<=,>,>= 以外的值');
        list($this->sym,$this->val) = [$p[0],$p[1]];
    }

    /**
     * get 方法
     * @throws Exception
     */
    public function get(sqlBuilder $builder): string{
        try {
            $return = $this->sym . (parent::isSqlKey($this->val) ? $this->val : $builder->setBind($this->val));
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
        return $return;
    }
}