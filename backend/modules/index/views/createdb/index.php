<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = '建库流程';
$this->params['breadcrumbs'][] = $this->title;
echo Html::jsFile('@web/public/plug/echarts/echarts.min.js');
?>
<?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js') ?>
<style type="text/css">
    .sql{margin: 0 auto; }
    .lf{float: left; margin: 20px; }
}
</style>
<div class="div-index">
    
    <p><span class="page-title"><?= Html::encode($this->title) ?></span><?=Html::a('新增库', ['create'], ['class' => 'sui-btn btn-xlarge btn-success']) ?></p>
    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'headerOptions' => ['width' => '30']
            ],
            'db_name',
            'server_ip',
            [
                //'label' => "当前步骤",
                'attribute' => 'status',
                'value' => function($searchModel){
                    if( $searchModel['status'] == 1 ){
                        return "已建库";
                    }else if( $searchModel['status'] == 2 ){
                        return "已授权";
                    }else if( $searchModel['status'] == 3 ){
                        return "已创建WEBDB角色";
                    }else if( $searchModel['status'] == 4 ){
                        return "已做域名解析";
                    }else if( $searchModel['status'] == 5 ){
                        return "已配置iptables";
                    }else if( $searchModel['status'] == 6 ){
                        return "已配独立DB环境";
                    }else{
                        return '已完成';
                    }
                },
                'options' => ['style' => 'width: 220px;']
            ],
            [
                'label' => "下一步骤",
                'attribute' => 'next_status',
                'value' => function($searchModel){
                    if( $searchModel['status'] == 1 ){
                        return "等待授权";
                    }else if( $searchModel['status'] == 2 ){
                        return "等待创建WEBDB角色";
                    }else if( $searchModel['status'] == 3 ){
                        return "等待域名解析";
                    }else if( $searchModel['status'] == 4 ){
                        return "等待配置iptables";
                    }else if( $searchModel['status'] == 5 && $searchModel['is_independent_db'] == 1 ){
                        return "等待配置独立DB环境";
                    }else{
                        return '';
                    }
                },
                'options' => ['style' => 'width: 220px;'], 
                'format' => 'raw'
            ],
            [
                'label' => "操作",
                'value' => function($searchModel, $key){
                    if( $searchModel['status'] != 0 ){
                        return Html::a('下一步',
                            'javascript:;',
                            ['class' => 'sui-btn btn-bordered btn-small btn-info next', 'data-id' => $key]
                        );
                    }else{
                        return '';
                    }
                },
                'options' => ['style' => 'width: 220px;'], 
                'format' => 'raw'
            ],
            
            /*[
                'class' => 'yii\grid\ActionColumn',
                'header' => "操作",
                'template' => "{delete} {view}",
                'buttons' => [
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
                    },
                    'view' => function( $url, $searchModel, $key ){
                        return Html::a('<i class="iconfont">&#xe66c;</i>',
                            'javascript:;',
                            ['class' => 'view', 'data-id' => $key]
                        );
                    }
                ],
                'headerOptions' => ['width' => '70']
            ]*/
        ],
    ]);
    ?>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $("input[name='LogSearch[workorder_time]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm:ss",isShowClear:true,readOnly:true,isShowWeek:true})');
        $("input[name='LogSearch[workorder_end_time]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm:ss",isShowClear:true,readOnly:true,isShowWeek:true})');
    });

    $(".next").on("click", function(){
        var id = $(this).attr('data-id');
        var url = "<?=Url::to(['update?id='])?>"+id;
        if( confirm("确定下一步么？") ){
            $.getJSON( url, function(data){
                if( data.code == 0 ){
                    layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                }else{
                    window.location.reload();
                }
            } );
        }else{
            return false;
        }
        
    });


    $(".view").on("click", function(){
        var log_id = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        layer.open({
            type: 2,
            title: '工单流水号： '+name,
            shadeClose: true,
            shade: 0.8,
            area: ['800px', '400px'],
            content: "<?=Url::to(['view?log_id=']) ?>"+log_id, //iframe的url
        });
    });
</script>