<?php

namespace DB\Helper\Lib;

use DB\Helper\Lib\Base\base;
use DB\Helper\Lib\Base\clause;
use DB\Helper\sqlBuilder;
use Exception;

class join extends base {
    /**
     * 表名称
     */
    public string $table;

    /**
     * 表别名
     */
    public string $alias;

    /**
     * 连接类型
     */
    public string $type = 'LEFT';

    /**
     * ON 条件
     */
    public array $on = [];

    /**
     * 构造体
     * @param string $type 连接类型
     * @param string|null $table 表名称
     * @throws Exception
     */
    public function __construct(string $type, string|null $table = null, string|null $alias = null){
        $this->type = match ($type) {
            'left'=>'LEFT',
            'right'=>'RIGHT',
            'full'=>'FULL',
            'inner'=>'INNER',
            'cross'=>'CROSS',
            'left outer'=>'LEFT OUTER',
            'right outer'=>'RIGHT OUTER',
            'full outer'=>'FULL OUTER',
            default => throw new Exception('参数一为非法字符')
        };
        if(!empty($table)) {
            $this->table($table);
            $this->alias = $alias ?? $table;
        }
    }

    /**
     * 文本化
     * @return string
     * @throws Exception
     */
    public function __toString(): string{
        return !$this->builder ? throw new Exception('Builder 未设置') : $this->get($this->builder);
    }

    /**
     * 置 导演类
     * @param sqlBuilder $builder
     * @return $this
     * @throws Exception 在$this->on中 如果内部成员不是 数组或者含有setBuilder方法的类 将给出类型错误的提示
     */
    public function setBuilder(sqlBuilder $builder): static{
        parent::setBuilder($builder);
        $recursion = function ($item) use (&$recursion,$builder){
            if(is_array($item)){
                foreach ($item as $i){
                    $recursion($i);
                }
            }elseif(is_object($item) && in_array('setBuilder',get_class_methods($item))){
                $item->setBuilder($builder);
            }else{
                throw new Exception('类型错误');
            }
        };
        array_map($recursion, $this->on);
        return $this;
    }

    /**
     * 置 表
     * @param string $table 表名称
     * @return $this
     */
    public function table(string $table,string|null $alias = null): static{
        if(empty($this->table)){
            $this->table = $table;
        }
        if(!empty($alias)) $this->alias($alias);
        return $this;
    }

    /**
     * 置 表别名
     * @param string $alias 表别名
     * @return $this
     */
    public function alias(string $alias): static{
        if(empty($this->alias)){
            $this->alias = $alias;
        }
        return $this;
    }

    /**
     * 置 ON条件[and 连接]
     * @param mixed ...$params
     * @return join
     * @throws Exception
     */
    public function onAnd(...$params): static{
        clause::set($this->on[],$this->alias,' AND ',...$params);
        return $this;
    }

    /**
     * 置 ON条件[or 连接]
     * @param mixed ...$params
     * @return join
     * @throws Exception
     */
    public function onOr(...$params): static{
        clause::set($this->on[],$this->alias,' OR ',...$params);
        return $this;
    }

    /**
     * GET 方法
     * @param sqlBuilder $builder
     * @return string
     */
    public function get(sqlBuilder $builder): string{
        return ' ' . $this->type . ' JOIN ' . $this->table . ' AS ' . $this->alias . ' ON ' .  clause::get($this->on,$builder);
    }
}