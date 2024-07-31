<?php
// 加载接口
require_once (str_replace('\\','/',dirname(__FILE__)) . '/Base/sqlBase.php');
// 加载基类
require_once (str_replace('\\','/',dirname(__FILE__)) . '/Base/base.php');
// 加载子句方法库
require_once (str_replace('\\','/',dirname(__FILE__)) . '/Base/clause.php');
// 加载该目录下所有文件
array_map(
    function ($_dir){require_once ($_dir);},
    glob(str_replace('\\','/',dirname(__FILE__)).'/*.php')
);