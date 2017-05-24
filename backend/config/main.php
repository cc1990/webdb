<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    file_exists(__DIR__ . '/../../common/config/params-local.php') ? require(__DIR__ . '/../../common/config/params-local.php') : [],
    require(__DIR__ . '/params.php'),
    file_exists(__DIR__ . '/params-local.php') ? require(__DIR__ . '/params-local.php') : [],
    require(__DIR__. '/regexp.php')
);

return [
    'id' => 'tools_backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'defaultRoute' => 'index',
    'modules' => [
        //默认首页模块
        'index' => [
            'class' => 'backend\modules\index\Module',
        ],
        //审核管理模块
        'audits' => [
            'class' => 'backend\modules\audits\Module',
        ],
        //用户管理
        'users' => [
            'class' => 'backend\modules\users\module',
        ],
        //角色管理
        'roles' => [
            'class' => 'backend\modules\roles\Module',
        ],
        //数据库服务器管理
        'servers' => [
            'class' => 'backend\modules\servers\module',
        ],
        //数据库服务器管理
        'projects' => [
            'class' => 'backend\modules\projects\Module',
        ],
        'operat' => [
            'class' => 'backend\modules\operat\module',
        ],
        'logs' => [
            'class' => 'backend\modules\logs\module',
        ],
        //订正模块
        'correct' => [
            'class' => 'backend\modules\correct\module',
        ],
        //数据备份管理
        'backup' => [
            'class' => 'backend\modules\backup\module',
        ],
        'rbac' =>  [
            'class' => 'johnitvn\rbacplus\Module',
            //'class' => 'backend\modules\rbac\module',
            'userModelClassName'=>'common\models\Users',
            'userModelIdField'=>'id',
            'userModelLoginField'=>'username',
            'userModelLoginFieldLabel'=>null,
            'userModelExtraDataColumls'=>null,
            'beforeCreateController'=>null,
            'beforeAction'=>null
        ],

        'gridview' =>  [
            'class' => '\kartik\grid\Module',
        ],

        'admin' => [
            'class' => 'mdm\admin\Module',
            'layout' => 'left-menu',//yii2-admin的导航菜单
        ]
    ],


    'components' => [
//        'user' => [
//            'identityClass' => 'common\models\User',
//            'enableAutoLogin' => true,
//        ],
        'users' => [
            'identityClass' => 'common\models\Users',
            'enableAutoLogin' => true,
            'class' => 'yii\web\User',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules'=>[
                '<controller:\w+>/<id:\d+>'=>'<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
                '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'request' => [
            'cookieValidationKey' => 'webdb',
        ],
        'redis'=>require ( __DIR__ . '/redis.php'),
    ],
    'params' => $params,
];
