<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\users\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-index div-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'username',
            'chinesename',
            'departments',
//            'password',
//            'role_id',
//            'auth_db',
            // 'insert_date',
            // 'is_change_passwd',
            //'authority',
            // 'status',
            // 'created_at',
//             'updated_at',

            [
                //'attribute' => 'created_at',
                'label'=>'更新时间',
                'value'=>function($model){
                    if(!empty($model->updated_at))
                        return  date('Y-m-d H:i:s',$model->updated_at);
                    else
                        return '';
                },
                'headerOptions' => ['width' => '170'],
            ],

            /*['class' => 'yii\grid\ActionColumn',
                'header'=>'操作',
                'template' => '{view} {update} {delete} {reset}',
                'buttons' => [
                    'reset' => function($url, $model, $key){
                        return Html::a('<span class="glyphicon glyphicon-repeat"></span>',
                            ['reset', 'id' => $key],
                            [
                                'data-pjax' => 0,
                                'data-toggle'=>'tooltip',
                                'data-request-method' => 'post',
                                'title' => '密码重置',
                                'data' => ['confirm' => '你确定要重置密码吗？',]
                            ]
                        );
                    },
                ],
            ],*/
        ],
    ]); ?>

</div>
