<?php

namespace DB\Helper;

defined('InOB-DeskT') or exit('访问无效(Access Invalid)!');

use DB\Helper\Lib\group;
use DB\Helper\Lib\in;
use DB\Helper\Lib\join;
use DB\Helper\Lib\Base\clause;
use DB\Helper\Lib\limit;
use DB\Helper\Lib\order;
use DB\Helper\Lib\raw;
use Db\MySql\MySql;
use Exception;

require_once (str_replace('\\','/',dirname(__FILE__)) . '/Lib/frame.php');

class sqlBuilder{
    /**
     * WHERE条件容器
     */
    public array $where = [];

    /**
     * HAVING条件容器
     */
    public array $having = [];

    /**
     * JOIN容器
     */
    public array $join = [];

    /**
     * 表名称
     */
    public string $table;

    /**
     * 表别名
     */
    public string $alias;

    /**
     * 字段组
     */
    private array $fields = [];

    /**
     * 预处理数据容器
     */
    static public array $bind = [];

    /**
     * 更新或新增数据
     */
    public array $data = [];

    /**
     * 排序
     */
    public order|null $order = null;

    /**
     * 分组
     */
    public group|null $group = null;

    /**
     * 分页
     */
    public limit|null $limit = null;

    /**
     * 语句类型
     */
    private string $type;

    private const bool _DEBUG_ = true;

    /**
     * 语句类型
     */
    private bool $is_lock_update = false;

    /**
     * 初始化
     * @param string $table
     * @param string $alias
     */
    public function __construct(string $table,string $alias){
        $this->table($table,$alias);
    }

    /**
     * 置 表
     * @param string $name
     * @param string $alias
     * @return sqlBuilder
     */
    public function table(string $name,string $alias): sqlBuilder{
        if(!isset($this->table)) $this->table = $name;
        if(!isset($this->alias)) $this->alias = $alias;
        return $this;
    }

    /**
     * 置 查询字段
     * @param array $fields
     * @return sqlBuilder
     */
    public function field(array $fields): sqlBuilder{
        array_push($this->fields,...$fields);
        return $this;
    }

    /**
     * 增
     * @return sqlBuilder
     */
    public function insert(): sqlBuilder{
        $this->type = 'INSERT INTO';
        return $this;
    }

    /**
     * 删
     * @param bool $isLog
     * @return sqlBuilder
     */
    public function delete(bool $isLog): sqlBuilder{
        $this->type = 'DELETE';
        if(!$isLog) {
            //这里是日志记录方法
            var_dump('delete');
        }
        return $this;
    }

    /**
     * 改
     * @return sqlBuilder
     */
    public function update(): sqlBuilder{
        $this->type = 'UPDATE';
        return $this;
    }

    /**
     * 查
     * @param array $fields
     * @return sqlBuilder
     */
    public function select(array $fields = []): sqlBuilder{
        $this->type = 'SELECT';
        if(!empty($fields)) $this->field($fields);
        return $this;
    }

    /**
     * @throws Exception
     */
    private function _assembly(): string{
        return match ($this->type) {
            'INSERT INTO' => $this->_insert(),
            'DELETE' => $this->_delete(),
            'UPDATE' => $this->_update(),
            'SELECT' => $this->_select(),
            default => throw new Exception('非法的语句类型'),
        };
    }

    private function _insert(): string{
        $L_str =  $this->type . ' `' . $this->table . '` ';
        return $L_str . '(' . implode(',',array_keys($this->data)) . ')VALUES(' . implode(',',array_values($this->data)) . ')';
    }

    private function _delete(): string{
        return  $this->type . ' ' . $this->table . '` AS `' . $this->alias . implode(' ',$this->join) . $this->whereToStr() . $this->limit;
    }

    private function _update(): string{
        // 初始化
        $L_str =  $this->type . ' `' . $this->table . '`AS `' . $this->alias . '` SET ';
        // 循环
        foreach ($this->data as $key => $value) {
            $L_str .= $key . '=' . $value . ','; // 拼接键和值，并在末尾添加逗号
        }
        // 移除最后一个逗号（如果有的话）+ where条件
        return rtrim($L_str, ',') . ' ' . $this->whereToStr();
    }

    private function _select(): string{
        $L_str =  $this->type . ' ' . implode(',',$this->fields) . ' FROM ' . '`' . $this->table . '` AS `' . $this->alias . '` ';
        $L_str .= implode(' ',$this->join);
        $L_str .= $this->whereToStr();
        $L_str .= $this->order ?? '';
        $L_str .= $this->group ?? '';
        $L_str .= $this->havingToStr();
        $L_str .= $this->limit ?? '';
        return $L_str . ($this->is_lock_update ? 'FOR UPDATE' : '');
    }

    /**
     * 执行sql
     * @throws Exception
     */
    public function execute(string $db): mixed{
        in_array($db,['MySql','SqlServer']) or throw new Exception('数据库类型不合法');
        try {
            $SQL = match ($this->type) {
                'INSERT INTO' => ['SqlStr'=>$this->_insert(),'funcName'=>'add'],
                'DELETE' => ['SqlStr'=>$this->_delete(),'funcName'=>'del'],
                'UPDATE' => ['SqlStr'=>$this->_update(),'funcName'=>'set'],
                'SELECT' => ['SqlStr'=>$this->_select(),'funcName'=>'get'],
                default => throw new Exception('非法的语句类型'),
            };
            $bind = $this->getBind();
            return call_user_func_array(['Db\MySql\\' . $db, $SQL['funcName']],[$SQL['SqlStr'],$bind]);
        }catch (Exception $e){
            if(self::_DEBUG_) echo $e->getMessage();
            throw new Exception('执行失败');
        } finally {
            static::$bind = $this->data = $this->fields = $this->where = $this->having = $this->join = [];
            $this->order = $this->group = $this->limit = null;
        }
    }

    /**
     * 置 WHERE 条件
     * @param mixed ...$params
     * @return sqlBuilder
     * @throws Exception
     * @note 当参数数量为1时
     * 可以传递一个回调函数 回调函数必须有1个 <sqlBuilder>类型的参数 为嵌套模式
     * $L_mGoods->whereOr(function (sqlBuilder $builder){$builder->where('字段名',值);}
     * 也可以传入了<raw>类型的参数，该方法将会传递一段SQL语句
     *
     * 当参数数量为2时 如果第二参数为文本型 且符合 'IS NULL','ISNULL','ISNOTNULL','IS NOT NULL','ISNOT NULL','IS NOTNULL' 的其中一种时
     * 将会得到 字段名(第一参数) IS NULL 或 字段名(第一参数) IS NOT NULL
     * 如非以上情况 第二参数将作为值使用 即为 字段名(第一参数) = 值(第二参数)
     *
     * 当参数数量为3时 第一参数最为字段名 第二参数作为关键字(=，>,<....) 第三参数作为值
     */
    public function whereAnd(...$params): static{
        clause::set($this->where[],$this->table,' AND ',...$params);
        return $this;
    }

    /**
     * 置 WHERE 条件 将以OR开头
     * @param mixed ...$params
     * @return sqlBuilder
     * @throws Exception
     * @note 当参数数量为1时
     * 可以传递一个回调函数 回调函数必须有1个 <sqlBuilder>类型的参数 为嵌套模式
     * $L_mGoods->whereOr(function (sqlBuilder $builder){$builder->where('字段名',值);}
     * 也可以传入了<raw>类型的参数，该方法将会传递一段SQL语句
     *
     * 当参数数量为2时 如果第二参数为文本型 且符合 'IS NULL','ISNULL','ISNOTNULL','IS NOT NULL','ISNOT NULL','IS NOTNULL' 的其中一种时
     * 将会得到 字段名(第一参数) IS NULL 或 字段名(第一参数) IS NOT NULL
     * 如非以上情况 第二参数将作为值使用 即为 字段名(第一参数) = 值(第二参数)
     *
     * 当参数数量为3时 第一参数最为字段名 第二参数作为关键字(=，>,<....) 第三参数作为值
     */
    public function whereOr(...$params): static{
        clause::set($this->where[],$this->table,' OR ',...$params);
        return $this;
    }

    /**
     * 置 HAVING 条件
     * @param mixed ...$params
     * @return sqlBuilder
     * @throws Exception
     * @note 当参数数量为1时
     * 可以传递一个回调函数 回调函数必须有1个 <sqlBuilder>类型的参数 为嵌套模式
     * $L_mGoods->whereOr(function (sqlBuilder $builder){$builder->where('字段名',值);}
     * 也可以传入了<raw>类型的参数，该方法将会传递一段SQL语句
     *
     * 当参数数量为2时 如果第二参数为文本型 且符合 'IS NULL','ISNULL','ISNOTNULL','IS NOT NULL','ISNOT NULL','IS NOTNULL' 的其中一种时
     * 将会得到 字段名(第一参数) IS NULL 或 字段名(第一参数) IS NOT NULL
     * 如非以上情况 第二参数将作为值使用 即为 字段名(第一参数) = 值(第二参数)
     *
     * 当参数数量为3时 第一参数最为字段名 第二参数作为关键字(=，>,<....) 第三参数作为值
     */
    public function havingAnd(...$params): static{
        clause::set($this->where[],$this->table,' AND ',...$params);
        return $this;
    }

    /**
     * 置 HAVING 条件 将以OR开头
     * @param mixed ...$params
     * @return sqlBuilder
     * @throws Exception
     * @note 当参数数量为1时
     * 可以传递一个回调函数 回调函数必须有1个 <sqlBuilder>类型的参数 为嵌套模式
     * $L_mGoods->whereOr(function (sqlBuilder $builder){$builder->where('字段名',值);}
     * 也可以传入了<raw>类型的参数，该方法将会传递一段SQL语句
     *
     * 当参数数量为2时 如果第二参数为文本型 且符合 'IS NULL','ISNULL','ISNOTNULL','IS NOT NULL','ISNOT NULL','IS NOTNULL' 的其中一种时
     * 将会得到 字段名(第一参数) IS NULL 或 字段名(第一参数) IS NOT NULL
     * 如非以上情况 第二参数将作为值使用 即为 字段名(第一参数) = 值(第二参数)
     *
     * 当参数数量为3时 第一参数最为字段名 第二参数作为关键字(=，>,<....) 第三参数作为值
     */
    public function havingOr(...$params): static{
        clause::set($this->where[],$this->table,' OR ',...$params);
        return $this;
    }

    /**
     * 置 预处理数据
     * @param mixed $value
     * @return string
     */
    public function setBind(mixed $value): string{
        static::$bind[$name = ':value' . count(static::$bind)] = (string)$value;
        return $name;
    }

    /**
     * 取 预处理数据
     * @return array
     */
    public function getBind(): array{
        return static::$bind;
    }

    /**
     * 到 文本(既转化成SQL语句)
     * @param sqlBuilder|null $builder
     * @return string
     * @throws Exception
     */
    public function toStr(sqlBuilder $builder = null): string{
//        if(!isset($this->mode)) {
//            return $this->_empty();
//        }
//        $_mode = $this->mode;
//        return $this->$_mode();
        return '';
    }

    /**
     * 置 ORDER BY
     * @param string $field
     * @param int $orderType
     * @return sqlBuilder
     */
    public function order(string $field,int $orderType): static{
        if(!isset($this->order)) $this->order = (new order())->setBuilder($this);
        $this->order->set($field,$orderType);
        return $this;
    }

    /**
     * 置 GROUP BY
     * @param string $field
     * @return sqlBuilder
     */
    public function group(string $field): sqlBuilder{
        if(!isset($this->group)) $this->group = (new group())->setBuilder($this);
        $this->group->set($field);
        return $this;
    }

    /**
     * 置 LIMIT
     * @param int $offset
     * @param int $count
     * @return sqlBuilder
     */
    public function limit(int $offset,int $count): sqlBuilder{
        if(!isset($this->limit)) $this->limit = (new limit())->setBuilder($this);
        $this->limit->set($offset,$count);
        return $this;
    }

    /**
     * 到 文本(仅where条件)
     * @return string
     */
    public function whereToStr(): string{
        $whereStr = clause::get($this->where,$this);
        if(empty($whereStr)) return '';
        return 'WHERE ' . $whereStr;
    }

    /**
     * 到 文本(仅where条件)
     * @return string
     */
    public function havingToStr():string{
        $havingStr = clause::get($this->having,$this);
        if(empty($havingStr)) return '';
        return 'WHERE ' . $havingStr;
    }

    /**
     * 到 文本(仅join条件)
     * @return string
     */
    public function joinToStr():string{
        return implode('', $this->join);
    }

    /**
     * @throws Exception
     */
//    public function getSql(): void
//    {
//        $SQL = match ($this->type) {
//            'INSERT INTO' => ['SqlStr'=>$this->_insert(),'funcName'=>'add'],
//            'DELETE' => ['SqlStr'=>$this->_delete(),'funcName'=>'del'],
//            'UPDATE' => ['SqlStr'=>$this->_update(),'funcName'=>'set'],
//            'SELECT' => ['SqlStr'=>$this->_select(),'funcName'=>'get'],
//            default => throw new Exception('非法的语句类型'),
//        };
//        $bind = $this->getBind();
//        var_dump($SQL);
//        var_dump($bind);
//    }

    public function data(string $field,string|array $value): sqlBuilder{
        // 使用循环和 str_replace 来控制替换的次数
        $func = function (string $str,array $data) {
            $_this = $this;
            $count = 0;
            while (($pos = strpos($str, '?')) !== false && $count < count($data)) {
                $str = substr_replace($str, $_this->setBind($data[$count]), $pos, strlen('?'));
                $count++;
            }
            return $str;
        };
        $this->data[$field] = is_string($value) ? $this->setBind($value) : call_user_func($func,$value[0],$value[1]);
        return $this;
    }
}
