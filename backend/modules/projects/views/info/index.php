<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = 'ProjectsInfo';
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
    <h1><?= Html::encode($this->title) ?></h1>
    <p><?=Html::a('Create ProjectsInfo', ['create'], ['class' => 'sui-btn btn-xlarge btn-success']) ?></p>
    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'headerOptions' => ['width' => '30']
            ],
            [
                'label' => "项目名称",
                'attribute' => 'project_name',
                'value' => function($searchModel){
                    if( $searchModel['pro_id'] == 0 ){
                        return "";
                    }else{
                        return $searchModel['name'];
                    }
                },
                'options' => ['style' => 'width: 220px;']
            ],
            [
                'label' => '项目描述',
                'attribute' => 'project_title',
                'value' => function($searchModel){
                    if( $searchModel['pro_id'] == 0 ){
                        return $searchModel['pro_name'];
                    }else{
                        return $searchModel['title'];
                    }
                },
                'options' => ['style' => 'width: 220px;']
            ],
            [
                'label' => '开发/测试 库',
                'attribute' => 'server_ip',
                'value' => function($searchModel){
                    if( empty($searchModel['server_ip']) ){
                        return '';
                    }else{
                        return $searchModel['server_ip'];
                    }
                },
                'options' => ['style' => 'width: 220px;']
            ],
            [
                'label' => '测试主干环境',
                'attribute' => 'test_trunck_date',
                'value' => function($searchModel){
                    if( empty($searchModel['test_trunck_date']) ){
                        return '';
                    }else{
                        return substr($searchModel['test_trunck_date'], 0, 16);
                    }
                },
                'options' => ['style' => 'width: 200px;']
            ],
            [
                'label' => '预发环境',
                'attribute' => 'pre_date',
                'value' => function($searchModel){
                    if( empty($searchModel['pre_date']) ){
                        return '';
                    }else{
                        return substr($searchModel['pre_date'], 0, 16);
                    }
                },
                'options' => ['style' => 'width: 200px;']
            ],
            [
                'label' => '线上环境',
                'attribute' => 'pro_date',
                'value' => function($searchModel){
                    if( empty($searchModel['pro_date']) ){
                        return '';
                    }else{
                        return substr($searchModel['pro_date'], 0, 16);
                    }
                },
                'options' => ['style' => 'width: 200px;']
            ],
            'remark',
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => "操作",
                'template' => "{update} {delete} {view}",
                'buttons' => [
                    'update' => function( $url, $searchModel, $key ){
                        return Html::a('<i class="iconfont">&#xe629;</i>',
                            ['update', 'id' => $searchModel['id']]
                        );
                    },
                    'delete' => function( $url, $searchModel, $key ){
                        return Html::a('<i class="iconfont">&#xe61a;</i>',
                            ['delete', 'id' => $searchModel['id']], 
                            [
                                'data-pjax' => 0,
                                'data-toggle'=>'tooltip',
                                'data-request-method' => 'post',
                                'title' => '删除',
                                'data' => ['confirm' => '你确定要删除吗？',]
                            ]
                        );
                    },
                    'view' => function($url, $searchModel, $key){
                        return Html::a('<i class="iconfont">&#xe66c;</i>', null, ['data-id' => $searchModel['id'], 'class' => 'view off disable info'.$searchModel['id']]);
                    }
                ],
                'headerOptions' => ['width' => '70']
            ]
        ],
    ]);
    ?>
    <div class="chartbox">
        <div id="thisweek" class="lf" style="width: 30%;height: 300px;"></div>
        <div id="lastweek" class="lf" style="width: 30%;height: 300px;"></div>
        <div id="lastmonth" class="lf" style="width: 30%;height: 300px;"></div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $("input[name='ProjectsInfoSearch[test_trunck_date]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm",isShowClear:true,readOnly:true,isShowWeek:true})');
        $("input[name='ProjectsInfoSearch[pre_date]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm",isShowClear:true,readOnly:true,isShowWeek:true})');
        $("input[name='ProjectsInfoSearch[pro_date]']").attr('onFocus', 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm",isShowClear:true,readOnly:true,isShowWeek:true})');
    });
    $(".view").on('click', function(){
        var id = $(this).attr('data-id');
        var url = "<?=Url::to(['info/get-project-list?id='])?>"+id;
        if( $(this).is(".off") ){
            if( $(".list"+id).html() != undefined ){
                $(".list"+id).show();
            }else{
                $.getJSON(url, function( data ){
                    if( data != '' ){
                        var tpl = "";
                        for( var i=0; i<data.length; i++ ){
                            tpl += "<tr class='list"+id+"'><td>&nbsp;&nbsp;"+i+"）</td><td></td><td></td>"
                                + "<td>"+data[i].server_ip+"</td>"
                                + "<td>"+data[i].test_trunck_date+"</td>"
                                + "<td>"+data[i].pre_date+"</td>"
                                + "<td>"+data[i].pro_date+"</td>"
                                + "<td>"+data[i].remark+"</td>"
                                + "<td></td></tr>";
                        }
                        tpl += "";
                        $(".info"+id).parent().parent().after(tpl);
                    }else{
                        layer.msg("暂无历史信息", {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                    }
                });
            }
            $(this).removeClass('off');
        }else{
            $(this).addClass('off');
            $(".list"+id).remove();
        }
        
    });

    var thisweek = echarts.init(document.getElementById('thisweek'));
    var value = "[{value:<?=$data['thisweek']['pres']?>, name: '预发环境', environment: 'pre'},{value:<?=$data['thisweek']['pros']?>, name: '线上环境', environment: 'pro'}]";
    thisweek_option = {
        title : {
            text: '本周项目发布数  总量：'+"<?= $data['thisweek']['pres']+$data['thisweek']['pros'] ?>",
            x:'center'
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient: 'vertical',
            left: 'left',
            data: ['预发环境', '线上环境'],
            formatter: function(name){
                var oa = thisweek_option.series[0].data;
                for(var i = 0; i < thisweek_option.series[0].data.length; i++){
                    if(name==oa[i].name){
                        return name + ' ' + oa[i].value;
                    }
                }
            }
        },
        series : [
            {
                name: '发布项目：',
                type: 'pie',
                radius : '55%',
                center: ['50%', '60%'],
                data:eval(value),
                label: {
                },
                itemStyle: {
                    emphasis: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }
        ]
    };
    thisweek.setOption(thisweek_option);
    /*thisweek.on('click', function (params) {
        var environment = params.data.environment;
        getInfo( environment, 'thisweek', '昨日项目上线量' );
        console.log(params);
    });*/

    var lastweek = echarts.init(document.getElementById('lastweek'));
    var key = "";
    var value = "[{value:<?=$data['lastweek']['pres']?>, name: '预发环境', environment: 'pre'},{value:<?=$data['lastweek']['pros']?>, name: '线上环境', environment: 'pro'}]";
    lastweek_option = {
        title : {
            text: '两周内项目发布数  总量：'+"<?= $data['lastweek']['pres']+$data['lastweek']['pros'] ?>",
            x:'center'
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient: 'vertical',
            left: 'left',
            data: ['预发环境', '线上环境'],
            formatter: function(name){
                var oa = lastweek_option.series[0].data;
                for(var i = 0; i < lastweek_option.series[0].data.length; i++){
                    if(name==oa[i].name){
                        return name + ' ' + oa[i].value;
                    }
                }
            }
        },
        series : [
            {
                name: '发布项目：',
                type: 'pie',
                radius : '55%',
                center: ['50%', '60%'],
                data:eval(value),
                label: {
                },
                itemStyle: {
                    emphasis: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }
        ]
    };
    lastweek.setOption(lastweek_option);
    /*lastweek.on('click', function (params) {
        var environment = params.data.environment;
        getInfo( environment, 'yesterday', '上周项目上线量' );
        console.log(params);
    });*/

    var lastmonth = echarts.init(document.getElementById('lastmonth'));
    var key = "";
    var value = "[{value:<?=$data['lastmonth']['pres']?>, name: '预发环境', environment: 'pre'},{value:<?=$data['lastmonth']['pros']?>, name: '线上环境', environment: 'pro'}]";
    lastmonth_option = {
        title : {
            text: '上个月项目发布数  总量：'+"<?= $data['lastmonth']['pres']+$data['lastmonth']['pros'] ?>",
            x:'center'
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient: 'vertical',
            left: 'left',
            data: ['预发环境', '线上环境'],
            formatter: function(name){
                var oa = lastmonth_option.series[0].data;
                for(var i = 0; i < lastmonth_option.series[0].data.length; i++){
                    if(name==oa[i].name){
                        return name + ' ' + oa[i].value;
                    }
                }
            }
        },
        series : [
            {
                name: '发布项目：',
                type: 'pie',
                radius : '55%',
                center: ['50%', '60%'],
                data:eval(value),
                label: {
                },
                itemStyle: {
                    emphasis: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }
        ]
    };
    lastmonth.setOption(lastmonth_option);
    /*lastmonth.on('click', function (params) {
        var environment = params.data.environment;
        getInfo( environment, 'yesterday', '上个月项目上线量' );
        console.log(params);
    });*/

    function getInfo( environment, date, title ){
        var url = "/projects/info/show?environment="+environment+"&date="+date;
        layer.open({
            title: title,
            type: 2,
            area: ['1200px', '530px'],
            fixed: false, //不固定
            maxmin: true,
            content: url
        });
    }
</script>