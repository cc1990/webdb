<?php

use yii\helpers\Html;
use yii\bootstrap\Modal;
//use kartik\grid\GridView;
use yii\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;

/* @var $this yii\web\View */
/* @var $searchModel johnitvn\rbacplus\models\AuthItemSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('rbac', 'Roles Manager');
$this->params['breadcrumbs'][] = $this->title;

//CrudAsset::register($this);
?>
<div class="auth-item-index div-index">
    <div id="ajaxCrudDatatable">
    <p>
        <?= Html::a('Create Role', ['create'], ['class' => 'sui-btn btn-xlarge btn-success']) ?>
    </p>
<?=
GridView::widget([
    'id' => 'crud-datatable',
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    //'columns' => require(__DIR__ . '/_columns.php'),
    'columns' => [
        [
            'class' => 'yii\grid\SerialColumn',
        ],
        [
            'attribute' => 'name',
            'label' => $searchModel->attributeLabels()['name'],
        ],
        [
            'attribute' => 'description',
            'label' => $searchModel->attributeLabels()['description'],
        ],
        [
            'label' => $searchModel->attributeLabels()['ruleName'],
            'value' => function($model) {
                return $model->ruleName == null ? Yii::t('rbac', '(not use)') : $model->ruleName;
            }
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'header' => '操作',
            'template' => '{update} {delete}',
            'buttons' => [
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
                            'data-request-method' => 'get',
                            'title' => '删除',
                            'data' => ['confirm' => '你确定要删除吗？',]
                        ]
                    );
                }
            ]
        ]
    ],
])
?>
    </div>
</div>
