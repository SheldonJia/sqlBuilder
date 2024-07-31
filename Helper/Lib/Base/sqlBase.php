<?php

namespace DB\Helper\Lib\Base;

use DB\Helper\sqlBuilder;

interface sqlBase
{
    public function __toString();
    public function set(...$p);
    public function get(sqlBuilder $builder);
}