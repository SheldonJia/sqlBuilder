<?php
namespace DB\Helper\Lib;


use DB\Helper\Lib\Base\sqlBase;
use DB\Helper\Lib\Base\base;
use DB\Helper\sqlBuilder;
use Exception;


class raw extends base implements sqlBase {
    private string $sql;
    private array|null $data;

    /**
     * @param string $sql
     * @param array $data
     * @throws Exception
     */
    public function __construct(string $sql, array $data = []){
        $this->set($sql,$data);
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
            '运算符号'=>'RAW',
            '值'=>$this->sql,
            '所属导演类'=> $this->builder ?? '暂无'
        ];
    }

    /**
     * 输出文本
     */
    public function toStr(sqlBuilder $builder = null): string{
        return ' ' . (empty($this->data) ? $this->sql : preg_replace('/\?/', array_map(function ($_item) use ($builder) {$builder->setBind($_item);}, $this->data), $this->sql));
    }

    /**
     * set 方法
     * @param mixed ...$p 在本类中 只接受2个参数 第一个参数为SQL语句 第二个为值
     * @throws Exception 第一参数必须为字符串
     */
    public function set(...$p): void{
        if(!is_string($p[0])) throw new Exception('第一参数必须为字符串');
        $this->sql = $p[0];
        $this->data = $p[1] ?? null;
    }

    /**
     * get 方法
     * @throws Exception
     */
    public function get(sqlBuilder $builder): string{
        return ' ' . (empty($this->data) ? $this->sql : preg_replace('/\?/', array_map(function ($_item) use ($builder) {
            $builder->setBind($_item);
            }, $this->data), $this->sql));
    }
}