<?php
/**
* @description 正则模板
*/
return  [
    'regexp'    =>  [
        //sql语句检查合法性，过滤不允许的操作
        'unable_operation'   =>  [
            '/databases/i',
            '/^sleep\s*/i',
            '/\s+count.*\s+user+\s.*/i',
            '/\s+count.*\s+user_info+\s.*/i',
            '/\s+count.*\s+qccr\.user+\s.*/i',
            '/\s+count.*\s+membercenter\.user_info+\s.*/i',
            //'/.*--.*/i',
            '/\s+limit\s+/i',
        ],

        //oms库的数据查询增加对敏感金额数据的统计限制
        'oms_limit_money'   =>  '/(\s|,)+(count|sum|avg)\(\s*(suggest_price|evaluate)\s*\)\s+\w+\s+(realtime_inventory|opening_inventory)\s*/i',

        //ordercenter库订单中心敏感金额数据统计限制
        'ordercenter_limit_money' =>  '/(\s|,)+(count|sum)\(\s*(market_cost|original_cost|sale_costreal_cost|market_cost|original_cost|sale_cost|coupon_apportion|market_cost|orig_cost|real_cost|sprice|signed_sprice|award_sprice|store_award_sprice|coupon_apportion|original_cost|sale_cost)\s*\)\s+\w+\s+(orders|order_goods|order_server|goods_sku_order)\s*/i',

        //delete语句必须存在where条件
        'delete_limit'  =>  [
            'condition' =>  '/delete\s+/',
            'limit' =>  '/delete\s+([\s\S]*)\s+where\s+\w+([\s\S]*)/',
        ],

        //update语句必须存在where条件
        'update_limit'  =>  [
            'condition' =>  '/update\s+/',
            'limit' =>  '/update\s+([\s\S]*)\s+where\s+\w+([\s\S]*)/',
        ],

        //不能编辑表字段
        'edit_column_limit'    =>  '/^alter\s+([\s\S]*)\s+(drop|change)\s*/',

        //表操作规则关键字
        'rule_key_words'    =>  'select|delete|insert|update|drop|create|alter|rename|truncate|explain|optimize|show|analyze|desc|describe|grant|call|replace|set|change|execute|declare|revoke',

        //统一注释模板
        'note_same'  =>  '/^;(?:\n|\s)*?(?:delete|insert|update|drop|create|alter|rename|truncate|optimize|analyze){1}?\s/i',

        //将sqlinfoA转换成sql去除非法的sql
        'sqlinfoA_sql'  =>  '/(?:select|delete|insert|update|drop|create|alter|rename|truncate|explain|optimize|show|analyze|desc|describe|grant|call|replace|set|change|execute|declare|revoke)[\s\S]*?(?:;#)/i',

        //判断是否为DML操作
        'dml_sql'   =>  '/^(?:delete|insert|update){1}?\s/i',

        //判断是否为DDL操作
        'ddl_sql'   =>  '/^(drop|create|alter|rename|truncate|optimize|analyze){1}?\s/i',

        //判断是否为DQL操作
        'dql_sql'   =>  '/^(select|show|desc|describe|explain){1}?\s/i',

        //判断是否为DML和DDL的集合
        'dml_ddl_sql'   =>  '/^(?:delete|insert|update|drop|create|alter){1}?\s/i',

        //判断是否为指定sql语句
        'sql_operation'   =>  [
            'show'  =>  '/^show\s+/i',
            'desc'  =>  '/^desc\s+/i',
            'describe'  =>  '/^describe\s+/i',
            'explain'  =>  '/^explain\s+/i',
            'insert'  =>  '/^insert\s+/i',
            'delete'  =>  '/^delete\s+/i',
            'create'  =>  '/^create\s+/i',
            'drop'  =>  '/^drop\s+/i',
            'alter'  =>  '/^alter\s+/i',
            'update'  =>  '/^update\s+/i',
        ],

        //检索出select语句的table
        'get_select_table'  =>  '/^select\s+((?!where).|\n)*\s+from\s+([a-zA-A0-9_.`]+)\s*/',

        //权限操作模板匹配
        'privilege_grant' =>    '/grant\s(.*)\son\s(.*)\sto\s/i',
    ]
];
?>