<?php
namespace DB\Helper\Lib;

use DB\Helper\Lib\Base\sqlBase;
use DB\Helper\sqlBuilder;
use DB\Helper\Lib\Base\base;
use Exception;

class col extends base implements sqlBase {
    private string $name;
    private string $sym;
    private string $val;

    /**
     * @param string|array $name
     * @param string $sym
     * @param mixed $value
     * @throws Exception
     */
    public function __construct(string|array $name,string $sym = '',mixed $value = 1){
        $this->set($name,$sym,$value);
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
     * @param mixed ...$p 在本类中 只接受3个参数 第一个参数为字段名 第二个参数为符号参数 第三个为值
     */
    public function set(...$p): void{
        $this->name = is_array($p[0]) ? (('`' . $p[0][0] . '`.`' . $p[0][1] . '`') . isset($p[0][2]) ? ' AS ' . $p[0][2] : '') : $p[0];
        $this->sym = $p[1];
        !in_array($this->sym,['+','-','*','/','%']) or $this->val = $p[2];
    }

    /**
     * get 方法
     * @throws Exception
     */
    public function get(sqlBuilder $builder): string{
        $str = '';
        try {
            if(in_array($this->sym,['+','-','*','/','%'])) $str = " $this->name $this->sym " . parent::isSqlKey($this->val) ? $this->val : $builder->setBind($this->val);
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
        return $str;
    }
}