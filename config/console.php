<?php
Yii::setAlias('@tests', dirname(__DIR__) . '\tests\odeception');

$params = [];
//$params = array_merge(
//    require(__DIR__ . '/../../common/config/params.php'),
//    require(__DIR__ . '/../../common/config/params-local.php'),
//    require(__DIR__ . '/params.php'),
//    require(__DIR__ . '/params-local.php'),
//    require(__DIR__. '/regexp.php')
//);

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname=db_tools',
            'username' => 'php',
            'password' => 'phpmysqldb2016',
            'charset' => 'utf8',
        ],
        'redis'=> [
            'class' => 'yii\redis\Connection',
            'hostname' => '192.168.3.212',
            'port' => 6379,
            'database' => 2,
        ]
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
