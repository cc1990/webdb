<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = '自助记录';
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
    
    <p><span class="page-title"><?= Html::encode($this->title) ?></span></p>
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
            'environment',
            'server_ip',
            'db_name',
            'tb_name',
            'sql',
            [
                'attribute' => 'backup_note',
                'value' => function($searchModel){
                    if( empty($searchModel['backup_note']) ){
                        return "未备份";
                    }else{
                        return $searchModel['backup_note'];
                    }
                }
            ],
            [
                'attribute' => 'execute_note',
                'value' => function($searchModel){
                    if( empty($searchModel['execute_note']) ){
                        return "未执行";
                    }else{
                        return $searchModel['execute_note'];
                    }
                }
            ],
            [
                'label' => "工单类型",
                'attribute' => 'workorder_type',
                'value' => function($searchModel){
                    if( $searchModel['workorder_type'] == 'correct' ){
                        return "自助订正";
                    }elseif( $searchModel['workorder_type'] == 'release' ){
                        return "自助发布";
                    }else{
                        return "";
                    }
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => "操作",
                'template' => "{view} {download}",
                'buttons' => [
                    'view' => function( $url, $searchModel, $key ){
                        return Html::a('<i class="iconfont">&#xe66c;</i>',
                            "javascript:;",
                            ['class' => 'view', 'data-id' => $key, 'data-name' => $searchModel->workorder_no, 'title' => '查看更多脚本']
                        );
                    },
                    'download' => function( $url, $searchModel, $key ){
                        if( $searchModel->backup_status == '3' ){
                            return Html::a('<i class="iconfont">&#xe610;</i>',
                                ['download', 'id' => $key],
                                ['class' => 'download', 'data-id' => $key, 'data-name' => $searchModel->workorder_no, 'title' => '下载备份文件', "target" => '_blank']
                            );
                        }else{
                            return "";
                        }
                    }
                ],
                'headerOptions' => ['width' => '70px']
            ]
        ],
    ]);
    ?>
</div>

<?php $this->registerJsFile("/public/js/common.function.js"); ?>
<script type="text/javascript">
    $(document).ready(function(){
        $("input[name='SelfHelpSearch[workorder_time]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm:ss",isShowClear:true,readOnly:true,isShowWeek:true})');
        $("input[name='SelfHelpSearch[workorder_end_time]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd",isShowClear:true,readOnly:true,isShowWeek:true})');
        $("input[name='SelfHelpSearch[workorder_user]']").css("width", "60px");
        $("input[name='SelfHelpSearch[environment]']").css("width", "50px");
        $("input[name='SelfHelpSearch[server_ip]']").css("width", "140px");
        $("input[name='SelfHelpSearch[db_name]']").css("width", "140px");
        $("input[name='SelfHelpSearch[tb_name]']").css("width", "100px");
        $("input[name='SelfHelpSearch[workorder_type]']").css("width", "140px");
    });

    $(".view").on("click", function(){
        var id = $(this).attr('data-id');
        var title = $(this).attr('data-name');
        var url = "<?=Url::to(['view?id='])?>"+id;
        var index = layer.open({
            title: "工单流水号为"+title+"的工单中的脚本",
            type: 2,
            area: ['1200px', '530px'],
            maxmin: true,
            content: url
        });
        layer.full(index);  
    });


</script>