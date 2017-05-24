<?php

use yii\helpers\Url;
use yii\helpers\Html;
use common\models\User;
$columns = [
    [
        'class' => 'yii\grid\SerialColumn',
    ],
    [
        //'class' => '\kartik\grid\DataColumn',
        'attribute' => Yii::$app->getModule('rbac')->userModelIdField,
        //'width' => '50px',
    ],
    [
        //'class' => '\kartik\grid\DataColumn',
        'attribute' => Yii::$app->getModule('rbac')->userModelLoginField,
        //'width' => '200px',
    ],
    [
        //'class' => '\kartik\grid\DataColumn',
        'attribute' => Yii::$app->getModule('rbac')->userModelChinesenameField,
        //'width' => '200px',
    ],
    [
        //'class' => '\kartik\grid\DataColumn',
        'attribute' => Yii::$app->getModule('rbac')->userModelDepartmentsField,
        //'width' => '200px',
    ],
    [
        'attribute' => Yii::$app->getModule('rbac')->userModelRolesField,
        'label' => 'Roles',
        'content' => function($model) {
            $authManager = Yii::$app->authManager;
            $idField = Yii::$app->getModule('rbac')->userModelIdField;
            $roles = [];
            foreach ($authManager->getRolesByUser($model->{$idField}) as $role) {
               $roles[] = $role->name;
            }
            if(count($roles)==0){
                return Yii::t("yii","(未设置)");
            }else{
                return implode(",", $roles);
            }
        },
        'width' => '500px',
    ],
    [
        'class' => 'yii\grid\ActionColumn',
        'header' => '操作',
        'template' => '{update}',
        'buttons' => [
            'update' => function( $url, $searchModel, $key ){
                return Html::a('<i class="iconfont">&#xe629;</i>',
                    ['update', 'id' => $key]
                );
            }
        ]
    ],
];

        return $columns;


        