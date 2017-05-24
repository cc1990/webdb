<?php
return [
    'adminEmail' => 'admin@example.com',
    /*操作第三方数据库账户配置开始*/
    'MARKET_USER' => 'php',
    'MARKET_PASSWD' => 'phpmysqldb2016',
    'MARKET_CHARSET' => 'utf8',
    'ADMIN_USER' => 'php_priv',
    'ADMIN_PASSWD' => 'p2p#340C6',
    'ADMIN_CHARSET' => 'utf8',
    'BACK_USER' => 'php_priv',
    'BACK_PASSWD' => 'p2p#340C6',
    'BACK_DIR' => 'd:/',
    'BACK_CHARSET' => 'utf8',
//    'MARKET_USER' => 'developer',
//    'MARKET_PASSWD' => 'developer',
    /*操作第三方数据库账户配置结束*/
    //
    'MD5_KEY' => 'E75D7E01A60B06480043BFF55CF2DA93',

    //运维平台项目状态和环境对照表
    'cryw_project_status' =>[
        '0' => 'new', //新建
        '10' => 'dev', //开发阶段
        '15' => 'dev_trunk', //自测阶段
        '25' => 'test', //分支测试
        '30' => 'test_trunk', //测试主干
        '35' => 'pre', //灰度
        '99' => 'pro' //线上
    ],

    //分库分表环境的库
    'sharding_database' => [
        'membercenter',
        'ordercenter'
    ],

    //公共操作权限
    'public_permission' => [
        'index_default_index', //通用库操作首页
        'index_default_execute', //通用库SQL执行
        'index_default_export', //数据导出
        'index_default_home', //入口选择
        'index_default_get-table-info', //获取表信息
        'index_default_get-table-list', //获取表
        'index_default_ping', //检查服务器是否连通状态
        'index_default_sql-verify', //检测SQL的合法性和受影响范围

        'index_sharding_index', //分库分表操作
        'index_sharding_get-project-list', //获取分库分表项目列表
        'index_sharding_sql-verify', //检测SQL的合法性和受影响范围
        //'index_sharding_test', //获取分库分表项目列表
        //'index_migrate_test', //获取分库分表项目列表
        //'site/test', //获取分库分表项目列表
        'index_sharding_execute', //执行分库分表的SQL语句
        'audits_default_audit',  //提交审核
        'audits_default_arrange-audit', //执行审核SQL
        'users_default_updatepassword', //修改密码

        'logs_version_index', //版本日志

        'index_sharding_get-table-info',    //
        'index_sharding_get-config',

        'rbac_role_upgrade',    //权限数据库升级

        'rbac_role_get-dbs',    //权限获取数据库详情
    ],
    'privilege_list'    =>  [   //权限列表
        'SELECT'    =>  '查询',
        'INSERT'    =>  '插入',
        'UPDATE'    =>  '更新',
        'DELETE'    =>  '删除数据',
        'CREATE'    =>  '创建数据库、表或索引',
        'DROP'      =>  '删除数据库或表',
//        'RELOAD'    =>  '执行相关命令',
//        'SHUTDOWN'    =>  '关闭数据库',
//        'PROCESS'    =>  '查看进程',
//        'FILE'    =>  '文件访问',
//        'REFERENCES'    =>  '',
        'INDEX'    =>  '索引',
        'ALTER'    =>  '更改表',
//        'SHOW DATABASES'    =>  '现实数据库',
//        'SUPER'    =>  '执行kill线程',
//        'CREATE TEMPORARY TABLES'    =>  '创建临时表',
//        'LOCK TABLES'    =>  '锁表',
        'EXECUTE'    =>  '执行存储过程',
//        'REPLICATION SLAVE'    =>  '从复制',
//        'REPLICATION CLIENT'    =>  '客户端复制',
        'CREATE VIEW'    =>  '创建视图',
        'SHOW VIEW'    =>  '查看视图',
        'CREATE ROUTINE'    =>  '创建存储过程',
        'ALTER ROUTINE'    =>  '更改存储过程',
        'CREATE USER'    =>  '创建用户',
//        'EVENT'    =>  '事件',
//        'TRIGGER'    =>  '触发器',
    ],
    //数据库权限列表
    'database_privilege_list'   =>  [
        'SELECT'    =>  '查询',
        'INSERT'    =>  '插入',
        'UPDATE'    =>  '更新',
        'DELETE'    =>  '删除数据',
        'CREATE'    =>  '创建数据库、表或索引',
        'DROP'      =>  '删除数据库或表',
//        'REFERENCES'    =>  '',
        'INDEX'    =>  '索引',
        'ALTER'    =>  '更改表',
//        'CREATE TEMPORARY TABLES'    =>  '创建临时表',
//        'LOCK TABLES'    =>  '锁表',
        'EXECUTE'    =>  '执行存储过程',
        'CREATE VIEW'    =>  '创建视图',
        'SHOW VIEW'    =>  '查看视图',
        'CREATE ROUTINE'    =>  '创建存储过程',
        'ALTER ROUTINE'    =>  '更改存储过程',
//        'EVENT'    =>  '事件',
//        'TRIGGER'    =>  '触发器',
    ],
    'table_privilege_list'  =>  [
        'SELECT'    =>  '查询',
        'INSERT'    =>  '插入',
        'UPDATE'    =>  '更新',
        'DELETE'    =>  '删除数据',
        'CREATE'    =>  '创建数据库、表或索引',
        'DROP'      =>  '删除数据库或表',
//        'REFERENCES'    =>  '',
        'INDEX'    =>  '索引',
        'ALTER'    =>  '更改表',
        'CREATE VIEW'    =>  '创建视图',
        'SHOW VIEW'    =>  '查看视图',
//        'TRIGGER'    =>  '触发器',
    ],
    //过滤相关数据库
    'filter_databases'  =>  ['mysql','information_schema','performance_schema'],
    //创建用户关键字屏蔽
    'create_user_filter_keyword'    =>  [
        'database','drop','alter','exit','explain','column','like','grant','create',
        'rename','insert','delete','revoke','kill','index','schema','lock','procedure',
        'select','null','sqlwarning','update','primary','trigger','root','admin'
    ],
    //脚本备份相关参数
    'backup'    =>  [
        'host'  =>  '192.168.5.144',
        'username'  =>  'webdb',
        'password'  =>  'webdb@123',
        'log_path'  =>  '/var/log/db_backup/',
        'type'  =>  [
            'full'  =>  '物理全量备份',
            'incr'  =>  '物理增量备份',
            'dump'  =>  '逻辑备份',
            'binlog'    =>  'binlog备份'
        ],
        'keyword'   =>  [
            'full'  =>  [
                'start' =>  'Backup start, please wait some minutes !',
                'size'  =>  'Before compress the backup set size',
                'archive_server'    =>  'Send file',
                'success'   =>  'Backup completed, congratulations !',
                'failed'    =>  'Backup faild, stop script and exit !'
            ],
            'incr'  =>  [
                'start' =>  'Backup start, please wait some minutes !',
                'size'  =>  'Before compress the backup set size',
                'archive_server'    =>  'Send file',
                'success'   =>  'Backup completed, congratulations !',
                'failed'    =>  'Backup faild, stop script and exit !'
            ],
            'dump'  =>  [
                'start' =>  'Backup start, please wait some minutes !',
                'size'  =>  'Before compress the backup set size',
                'archive_server'    =>  'Send file',
                'success'   =>  'Backup completed, congratulations !',
                'failed'    =>  'Backup faild, stop script and exit !'
            ],
            'binlog'  =>  [
                'start' =>  'Backup start, please wait some minutes !',
                'size'  =>  'Before compress the backup set size',
                'archive_server'    =>  'Send file',
                'success'   =>  'Backup completed, congratulations !',
                'failed'    =>  'Backup faild, stop script and exit !'
            ],
        ],
        'getSpaceInterface' =>  [
            'url'   =>  'http://192.168.5.70:9966/graph/last',
            'params'    =>  [
                'endpoint'  =>  '',
                'counter.free'   =>  'df.bytes.free/fstype=ext4,mount=/',
                'counter.total'   =>  'df.bytes.total/fstype=ext4,mount=/'
            ],
            'disk'  =>  [
                '192.168.69.144'    =>  ['data2','data2'],
                '192.168.70.19'    =>  ['data','data2'],
                '192.168.70.100'    =>  ['data','data2'],
                '192.168.70.8'    =>  ['data','data'],
                '192.168.69.152'    =>  ['data','data'],
                '192.168.5.123'    =>  ['data','data'],
                '192.168.3.236'    =>  ['data','data'],
                '192.168.5.122'    =>  ['data','data'],
                '192.168.5.139'    =>  ['data','data'],
                '192.168.5.70'    =>  ['data','data'],
            ]
        ]
    ]
];
