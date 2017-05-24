<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = '订正记录';
$this->params['breadcrumbs'][] = $this->title;
echo Html::jsFile('@web/public/plug/echarts/echarts.min.js');
?>
<?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js') ?>
<style type="text/css">
    .sql{margin: 0 auto; }
    .lf{float: left; margin: 20px; }
    td{    max-width: 400px; word-wrap: break-word; word-break: break-all;}
}
</style>
<div class="div-index">
    
    <p><span class="page-title"><?= Html::encode($this->title) ?></span><?=Html::a('订正记录', ['create'], ['class' => 'sui-btn btn-xlarge btn-success']) ?>&nbsp;&nbsp;&nbsp;&nbsp;<?=Html::a('订正统计', ['logview'], ['class' => 'sui-btn btn-xlarge btn-info']) ?>&nbsp;&nbsp;&nbsp;&nbsp;是否开启自助功能：<img src="/public/images/<?=$allowSelfhelp == 'on'?"on":"off"?>.png" event-bind="selfhelp_status" data-status="<?= ($allowSelfhelp == 'on')?1:0?>"></p>
    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'headerOptions' => ['width' => '30']
            ],
            'workorder_no',
            'workorder_user',
            'workorder_title',
            'workorder_dba',
            'work_line',
            'workorder_end_time',
            'db_names',
            'use_time',
            'script_number',
            'influences_number',
            [
                'attribute' => 'source',
                'value' => function($searchModel){
                    if ($searchModel['source'] == 'create') {
                        return '手工录入';
                    } elseif ($searchModel['source'] == 'selfhelp') {
                        return '自助订正';
                    } else {
                        return '人工订正';
                    }
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => "操作",
                'template' => "{update} {view}",
                'buttons' => [
                    'update' => function( $url, $searchModel, $key ){
                        return Html::a('<i class="iconfont">&#xe629;</i>',
                            'javascript:;',
                            ['class' => 'update', 'data-id' => $key, 'data-name' => $searchModel->workorder_no]
                        );
                    },
                    /*'delete' => function( $url, $searchModel, $key ){
                        return Html::a('<i class="iconfont">&#xe61a;</i>',
                            ['delete', 'id' => $searchModel['log_id']], 
                            [
                                'data-pjax' => 0,
                                'data-toggle'=>'tooltip',
                                'data-request-method' => 'post',
                                'title' => '删除',
                                'data' => ['confirm' => '你确定要删除吗？',]
                            ]
                        );
                    },*/
                    'view' => function( $url, $searchModel, $key ){
                        return Html::a('<i class="iconfont">&#xe66c;</i>',
                            'javascript:;',
                            ['class' => 'view', 'data-id' => $key, 'data-name' => $searchModel->workorder_no]
                        );
                    }
                ],
                'headerOptions' => ['width' => '70']
            ]
        ],
    ]);
    ?>
</div>

<?php $this->registerJsFile("/public/js/common.function.js"); ?>
<script type="text/javascript">
    $(document).ready(function(){
        $("input[name='LogSearch[workorder_time]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm:ss",isShowClear:true,readOnly:true,isShowWeek:true})');
        $("input[name='LogSearch[workorder_end_time]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd",isShowClear:true,readOnly:true,isShowWeek:true})');
        $("input[name='LogSearch[workorder_user]']").css("width", "100px");
        $("input[name='LogSearch[workorder_dba]']").css("width", "100px");
    });

    $(".update").on("click", function(){
        var log_id = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        layer.open({
            type: 2,
            title: '工单流水号： '+name,
            shadeClose: true,
            shade: 0.8,
            area: ['800px', '500px'],
            content: "<?=Url::to(['update?log_id=']) ?>"+log_id, //iframe的url
        });
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

    //开启关闭禁用状态
    $("img[event-bind='selfhelp_status']").on('click',function(){
        var status = $(this).attr('data-status');
        var url = "<?=Url::to(['allow-selfhelp'])?>?status="+status;
        $.getJSON(url, function(data){
            if(data.code == 1){
                $("img[event-bind='selfhelp_status']").attr("data-status", status == 1 ? 0 : 1);
                var s_status = status == 1 ? 'off' : 'on';
                $("img[event-bind='selfhelp_status']").attr("src","/public/images/" + s_status + ".png");
            }else{
                alert(data.msg);
            }
        })
    });

</script>