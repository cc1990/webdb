<?php

use yii\helpers\Url;
use yii\helpers\Html;

return [
    [
        'class' => 'yii\grid\SerialColumn',
    ],
    [
        'attribute'=>'name',
        'label' => $searchModel->attributeLabels()['name'],
    ],
    [
        'attribute'=>'description',
        'label' => $searchModel->attributeLabels()['description'],
    ],    
    [
        'label' => $searchModel->attributeLabels()['ruleName'],
        'value' => function($model){
            return $model->ruleName==null?Yii::t('rbac','(not use)'):$model->ruleName;
        }
    ],
    [
        'class' => 'yii\grid\ActionColumn',
        'header' => '操作',
        'template' => '{view} {update} {delete}',
        'buttons' => [
            'view' => function( $url, $searchModel, $key ){
                return Html::a('<i class="iconfont">&#xe66c;</i>',
                    '#',
                    ['class' => 'view', 'data-name' => $key]
                );
            },
            'update' => function( $url, $searchModel, $key ){
                return Html::a('<i class="iconfont">&#xe629;</i>',
                    ['update', 'name' => $key]
                );
            },
            'delete' => function( $url, $searchModel, $key ){
                return Html::a('<i class="iconfont">&#xe61a;</i>',
                    ['delete', 'name' => $key], 
                    [
                        'data-pjax' => 0,
                        'data-toggle'=>'tooltip',
                        'data-request-method' => 'post',
                        'title' => '删除',
                        'data' => ['confirm' => '你确定要删除吗？',]
                    ]
                );
            }
        ]
    ],
];
        