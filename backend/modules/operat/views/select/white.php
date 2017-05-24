<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

$this->title="查询白名单设置";
?>

<div class="white-index div-index">
    <p><?=Html::a('create', ['white-add'], ['class' => 'sui-btn btn-xlarge btn-success']) ?></p>
    <div class="white-list">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'username',
                [
                    'label' => '姓名',
                    'attribute' => 'chinesename',
                    'value' => 'users.chinesename'
                ],
                'number',
                [
                    'attribute' => 'stop_date',
                    'value' => function($searchModel){
                        if ( $searchModel->stop_date < date("Y-m-d") ) {
                            return Html::tag('span', $searchModel->stop_date, ['style' => ['color' => 'red']]);
                            //return "<span color='red'>".$searchModel->stop_date."</span>";
                        } else {
                            return $searchModel->stop_date;
                        }
                        
                    },
                    'format' => 'raw'
                ],
                'db_name',
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => '操作',
                    'template' => '{update} {delete}',
                    'buttons' => [
                        'update' => function( $url, $searchModel, $key ){
                            return Html::a('<i class="iconfont">&#xe629;</i>',
                                ['white-update', 'id' => $key]
                            );
                        },
                        'delete' => function( $url, $searchModel, $key ){
                            return Html::a('<i class="iconfont">&#xe61a;</i>',
                                ['white-del', 'id' => $key], 
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
            ],
        ]);?>
    </div>

</div>