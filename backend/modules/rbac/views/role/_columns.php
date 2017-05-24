<?php

use yii\helpers\Url;

return [   
    [
        'class' => 'yii\grid\SerialColumn',
    ],
    [
        'attribute' => 'name',
        'label' => $searchModel->attributeLabels()['name'],
        'width' => '140px',
    ],
    [
        'attribute' => 'description',
        'label' => $searchModel->attributeLabels()['description'],
    ],
    [
        'label' => $searchModel->attributeLabels()['ruleName'],
        'width' => '140px',
        'value' => function($model) {
            return $model->ruleName == null ? Yii::t('rbac', '(not use)') : $model->ruleName;
        }
    ],
    [
        'class' => 'yii\grid\ActionColumn',
    ],
];
        