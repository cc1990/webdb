<?php 
use yii\helpers\Html;
use yii\helpers\Url;

use yii\grid\GridView;

$this->title = '授权白名单';
$this->params['breadcrumbs'][] = $this->title;
$environment_array = array(
    'dev' => '开发',
    'test' => '测试',
    'test_trunk' => '测试主干',
    'pre' => '预发布',
    'pro' => '线上',
    'dev_trunk' => '研发主干',
);

?>
<?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js') ?>
<div class="white-index div-index">
    <p><?=Html::a('Create Authorize', ['create'], ['class' => 'sui-btn btn-xlarge btn-success'])?></p>
    <?=GridView::widget([
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
                [
                    'attribute' => 'stop_time',
                    'value' => function($searchModel){
                        if ( $searchModel->stop_time < date("Y-m-d H") ) {
                            return Html::tag('span', substr($searchModel->stop_time, 0, 13), ['style' => ['color' => 'red']]);
                        } else {
                            return substr($searchModel->stop_time, 0, 13);
                        }
                        
                    },
                    'format' => 'raw'
                ],
                [
                    'attribute' => 'type',
                    'value' => function($searchModel){
                        if ( $searchModel->type == 'common' ) {
                            return '通用库';
                        } else {
                            return '分库分表';
                        }
                        
                    }
                ],
                'server_ip',
                [
                    'attribute' => 'environment',
                    'value' => function($searchModel){
                        $environment = $searchModel->environment;
                        switch ( $environment ) {
                            case 'dev':
                                return '开发';
                                break;
                            case 'dev_trunk':
                                return '开发主干';
                                break;
                            case 'test':
                                return '测试';
                                break;
                            case 'test_trunk':
                                return '测试主干';
                                break;
                            case 'pre':
                                return '预发';
                                break;
                            case 'pro':
                                return '线上';
                                break;
                            
                            default:
                                return '';
                                break;
                        }                        
                    }
                ],
                'db_name',
                'sqloperation',
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => '操作',
                    'template' => '{update} {delete}',
                    'buttons' => [
                        'update' => function( $url, $searchModel, $key ){
                            return Html::a('<i class="iconfont">&#xe629;</i>',
                                ['update', 'id' => $key]
                            );
                        },
                        'delete' => function( $url, $searchModel, $key ){
                            return Html::a('<i class="iconfont">&#xe61a;</i>',
                                ['delete', 'id' => $key], 
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
    ])?>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $("input[name='AuthorizeSearch[stop_time]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd HH",isShowClear:true,readOnly:true,isShowWeek:true})');
    });
</script>