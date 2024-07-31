<?php

namespace DB\Helper\Lib\Base;

use DB\Helper\Lib\raw;
use DB\Helper\Lib\where;
use DB\Helper\sqlBuilder;
use Exception;

// 子句生成类
class clause{
    /**
     * 公用 置 方法
     * @param mixed &$container
     * @param string $table
     * @param string $com
     * @param mixed ...$params
     * @throws Exception
     */
    static public function set(mixed &$container, string $table, string $com,...$params): void{
        //取参数数量
        $paramsL = count($params);
        // 单个参数的情况
        if ($paramsL === 1) {
            // 闭包或回调函数
            if (is_callable($params[0])) {
                // 执行回调函数
                $params[0]($L_container = new sqlBuilder($table,$table));
                // 截取执行过后的数据填充当前数据
                $container = $L_container->where;
                // raw 对象
            } elseif ($params[0] instanceof raw) {
                // 创建RAW对象填充当前数据
                $container = (new where($table))->set('', 'RAW', $params[0], $com);
                // 参数不合法
            } else {
                throw new Exception('第一个参数必须是一个回调函数或RAW对象');
            }
            // 两个参数的情况
        } elseif ($paramsL === 2) {
            $operator = strtoupper($params[1]);
            if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) { // 空值检查
                $container = (new where($table))->set($params[0], $operator, '', $com);
                // 默认为等于
            } else {
                $container = (new where($table))->set($params[0], '=', $params[1], $com);
            }
            // 三个参数的情况
        } elseif ($paramsL === 3) {
            [$field, $operator, $value] = $params;

            $operator = strtoupper(trim($operator));

            $validOperators = ['=', '!=', '<>', '<', '<=', '>', '>=', 'IN', 'NOT IN', 'BETWEEN', 'REGEXP', 'LIKE'];

            if (!in_array($operator, $validOperators)) throw new Exception('第三个参数必须是有效的比较运算符');

            $container = (new where($table))->set($field, $operator, $value, $com);
            // 参数数量不合法
        } else {
            throw new Exception('参数数量不正确，必须为1、2或3个');
        }
    }

    /**
     * 公用 取 方法
     * @param array $params
     * @param sqlBuilder $builder
     * @return string
     */
    static public function get(array $params, sqlBuilder $builder): string{
        $str = '';
        foreach ($params as $item){
            if(is_array($item)){
                $str .= (method_exists($item[0], 'getCom') ? $item[0]->getCom() : '') . '(' . self::get($item,$builder) . ')';
            }else{
                $str .= $item->getCom() . $item->setBuilder($builder);
            }
        }
        return trim($str,' ANDOR');
    }
}