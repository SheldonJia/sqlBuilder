<?php
namespace DB\Helper\Core\Lib;

use DB\Helper\Lib\Base\sqlBase;
use DB\Helper\sqlBuilder;
use DB\Helper\Lib\Base\base;
use Exception;

class count extends base implements sqlBase{
    public mixed $val;
    public string $alias;

    /**
     * COUNT()
     * @param mixed $val
     * @param string $alias
     */
    public function __construct(mixed $val, string $alias = 'count'){
        self::set($val,$alias);
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
            '别名'=>$this->alias,
            '值'=>$this->val,
            '所属导演类'=> $this->builder ?? '暂无'
        ];
    }

    /**
     * set 方法
     * @param mixed ...$p 在本类中 只接受2个参数 第一个参数为字段名或语句 第二个参数为别名
     */
    public function set(...$p): void{
        $$this->val = $p[0];
        $this->alias = $p[1];
    }

    /**
     * get 方法
     * @throws Exception
     */
    public function get(sqlBuilder $builder): string{
        $str = '';
        try {
            $str .= 'COUNT(' . parent::isSqlKey($this->val) ? $this->val : $builder->setBind($this->val) . ')' . ($this->alias ? (' AS `' . $this->alias . '`') : '');
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
        return $str;
    }

}