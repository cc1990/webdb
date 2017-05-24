<?php 
use yii\helpers\Html;

echo Html::jsFile('@web/public/plug/echarts/echarts.min.js');

$this->params['breadcrumbs'] = '';
?>
<style type="text/css">
    .sql{margin: 0 auto; }
    .lf{float: left; margin: 20px; }
}
</style>
<div>
    <div class="sql">
        <div id="daybox" class="lf" style="width: 98%;height:350px;"></div>
        <div class="databasebox">
            <div id="db_yesterday" class="lf" style="width: 30%;height: 300px;"></div>
            <div id="db_lastweek" class="lf" style="width: 30%;height: 300px;"></div>
            <div id="db_lastmonth" class="lf" style="width: 30%;height: 300px;"></div>
        </div>

        <div class="usersbox">
            <div id="users_yesterday" class="lf" style="width: 30%;height: 300px;"></div>
            <div id="users_lastweek" class="lf" style="width: 30%;height: 300px;"></div>
            <div id="users_lastmonth" class="lf" style="width: 30%;height: 300px;"></div>
        </div>
    </div>
    <script type="text/javascript">
        // 基于准备好的dom，初始化echarts实例
        var daybox = echarts.init(document.getElementById('daybox'));
        var day_key = "["; var day_value = "{[";
        
        <?php $day_key = $day_count = ''; foreach ($count['thirty_day'] as $key => $value) {
            $day_key .= "'".$value['day']."',";
            $day_count .= "'".$value['count']."',";
        } ?>
        day_key += "<?=$day_key?>]";day_value += "<?=$day_count?>]}";
        day_option = {
            title: [{
                text: '线上查询最近30天每日的统计数量'
            }],
            color: ['#3398DB'],
            tooltip : {
                trigger: 'axis',
                axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                    type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                }
            },
            toolbox: {
                show: true,
                feature: {
                    mark : {show: true},
                    dataView : {show: true, readOnly: false},
                    magicType: {show: true, type: ['line', 'bar']},
                    restore : {show: true},
                    saveAsImage : {show: true}
                }
            },
            grid: {
                //show: true,
                left: 'left',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis : [
                {
                    type : 'category',
                    data : eval(day_key),
                    axisTick: {
                        alignWithLabel: true
                    }
                }
            ],
            yAxis : [
                {
                    type : 'value'
                }
            ],
            series : [
                {
                    name:'执行条数：',
                    type:'bar',
                    barWidth: '60%',
                    data:eval(day_value),
                    itemStyle: {
                        normal: {
                            label: {
                                show: true,
                                position: 'top',
                                textStyle: {
                                    color: 'red'
                                }
                            }
                        }
                    }
                }
            ]
        };
        // 使用刚指定的配置项和数据显示图表。
        daybox.setOption(day_option);

        var db_yesterday = echarts.init(document.getElementById('db_yesterday'));
        var db_key = db_value = "[";
        
        <?php $db_key = $db_value = ''; foreach ($database['yesterday'] as $key => $value) {
            $db_key .= "'".$value['database']."',";
            $db_value .= "{value:".$value['count'].", name:'".$value['database']."'},";
            //$db_count .= "'".$value['count']."',";
        } ?>
        db_key += "<?=$db_key?>]";db_value += "<?=$db_value?>]";
        db_yesterday_option = {
            title : {
                text: '昨日数据库查询量前10  总量：'+<?=$database['yesterday_count']['count']?>,
                x:'center'
            },
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                data: eval(db_key),
                formatter: function(name){
                    var oa = db_yesterday_option.series[0].data;
                    for(var i = 0; i < db_yesterday_option.series[0].data.length; i++){
                        if(name==oa[i].name){
                            return name + ' ' + oa[i].value;
                        }
                    }
                }
            },
            series : [
                {
                    name: '查询数据库：',
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:eval(db_value),
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
        db_yesterday.setOption(db_yesterday_option);
        db_yesterday.on('click', function (params) {
            var db_name = params.data.name;
            getInfo( "database", db_name, 'yesterday', '昨日数据库访问量前10' );
            console.log(params);
        });

        var db_lastweek = echarts.init(document.getElementById('db_lastweek'));
        var db_key = db_value = "[";
        
        <?php $db_key = $db_value = ''; foreach ($database['lastweek'] as $key => $value) {
            $db_key .= "'".$value['database']."',";
            $db_value .= "{value:".$value['count'].", name:'".$value['database']."'},";
            //$db_count .= "'".$value['count']."',";
        } ?>
        db_key += "<?=$db_key?>]";db_value += "<?=$db_value?>]";
        db_lastweek_option = {
            title : {
                text: '上周数据库查询量前10  总量：'+<?=$database['lastweek_count']['count']?>,
                x:'center'
            },
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                data: eval(db_key),
                formatter: function(name){
                    var oa = db_lastweek_option.series[0].data;
                    for(var i = 0; i < db_lastweek_option.series[0].data.length; i++){
                        if(name==oa[i].name){
                            return name + ' ' + oa[i].value;
                        }
                    }
                }
            },
            series : [
                {
                    name: '查询数据库：',
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:eval(db_value),
                    itemStyle: {
                        normal: {
                            show: true,
                            formatter: '{b} : {c} ({d}%)' 
                        }
                    }
                }
            ]
        };
        db_lastweek.setOption(db_lastweek_option);
        db_lastweek.on('click', function (params) {
            var db_name = params.data.name;
            getInfo( "database", db_name, 'lastweek', '上周数据库访问量前10' );
            console.log(params);
        });

        var db_lastmonth = echarts.init(document.getElementById('db_lastmonth'));
        var db_key = db_value = "[";
        
        <?php $db_key = $db_value = ''; foreach ($database['lastmonth'] as $key => $value) {
            $db_key .= "'".$value['database']."',";
            $db_value .= "{value:".$value['count'].", name:'".$value['database']."'},";
            //$db_count .= "'".$value['count']."',";
        } ?>
        db_key += "<?=$db_key?>]";db_value += "<?=$db_value?>]";
        db_lastmonth_option = {
            title : {
                text: '上个月数据库查询量前10  总量：'+<?=$database['lastmonth_count']['count']?>,
                x:'center'
            },
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                data: eval(db_key),
                formatter: function(name){
                    var oa = db_lastmonth_option.series[0].data;
                    for(var i = 0; i < db_lastmonth_option.series[0].data.length; i++){
                        if(name==oa[i].name){
                            return name + ' ' + oa[i].value;
                        }
                    }
                }
            },
            series : [
                {
                    name: '查询数据库：',
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:eval(db_value),
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
        db_lastmonth.setOption(db_lastmonth_option);
        db_lastmonth.on('click', function (params) {
            var db_name = params.data.name;
            getInfo( "database", db_name, 'lastmonth', '上个月数据库访问量前10' );
            console.log(params);
        });

        var users_yesterday = echarts.init(document.getElementById('users_yesterday'));
        var users_key = users_value = "[";
        
        <?php $users_key = $users_value = ''; foreach ($users['yesterday'] as $key => $value) {
            $chinesename = $value['chinesename'] ? $value['chinesename'] : $value['username'];
            $users_key .= "'".$chinesename."',";
            $users_value .= "{value:".$value['count'].", name:'".$chinesename."', user:".$value['user_id']."},";
            //$users_count .= "'".$value['count']."',";
        } ?>
        users_key += "<?=$users_key?>]";users_value += "<?=$users_value?>]";
        users_yesterday_option = {
            title : {
                text: '昨日用户查询次数前10  总量：'+<?=$users['yesterday_count']['count']?>,
                x:'center'
            },
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                data: eval(users_key),
                formatter: function(name){
                    var oa = users_yesterday_option.series[0].data;
                    for(var i = 0; i < users_yesterday_option.series[0].data.length; i++){
                        if(name==oa[i].name){
                            return name + ' ' + oa[i].value;
                        }
                    }
                }
            },
            series : [
                {
                    name: '用户：',
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:eval(users_value),
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
        users_yesterday.setOption(users_yesterday_option);
        users_yesterday.on('click', function (params) {
            var user_id = params.data.user;
            getInfo( "user", user_id, 'yesterday', '昨日用户查询次数前10' );
            console.log(params);
        });

        var users_lastweek = echarts.init(document.getElementById('users_lastweek'));
        var users_key = users_value = "[";
        
        <?php $users_key = $users_value = ''; foreach ($users['lastweek'] as $key => $value) {
            $chinesename = $value['chinesename'] ? $value['chinesename'] : $value['username'];
            $users_key .= "'".$chinesename."',";
            $users_value .= "{value:".$value['count'].", name:'".$chinesename."', user:".$value['user_id']."},";
            //$users_count .= "'".$value['count']."',";
        } ?>
        users_key += "<?=$users_key?>]";users_value += "<?=$users_value?>]";
        users_lastweek_option = {
            title : {
                text: '上周用户查询次数前10  总量：'+<?=$users['lastweek_count']['count']?>,
                x:'center'
            },
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                data: eval(users_key),
                formatter: function(name){
                    var oa = users_lastweek_option.series[0].data;
                    for(var i = 0; i < users_lastweek_option.series[0].data.length; i++){
                        if(name==oa[i].name){
                            return name + ' ' + oa[i].value;
                        }
                    }
                }
            },
            series : [
                {
                    name: '用户：',
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:eval(users_value),
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
        users_lastweek.setOption(users_lastweek_option);
        users_lastweek.on('click', function (params) {
            var user_id = params.data.user;
            getInfo( "user", user_id, 'lastweek', '上周用户查询次数前10' );
            console.log(params);
        });

        var users_lastmonth = echarts.init(document.getElementById('users_lastmonth'));
        var users_key = users_value = "[";
        
        <?php $users_key = $users_value = ''; foreach ($users['lastmonth'] as $key => $value) {
            $chinesename = $value['chinesename'] ? $value['chinesename'] : $value['username'];
            $users_key .= "'".$chinesename."',";
            $users_value .= "{value:".$value['count'].", name:'".$chinesename."', user:".$value['user_id']."},";
            //$users_count .= "'".$value['count']."',";
        } ?>
        users_key += "<?=$users_key?>]";users_value += "<?=$users_value?>]";
        users_lastmonth_option = {
            title : {
                text: '上个月用户查询次数前10  总量：'+<?=$users['lastmonth_count']['count']?>,
                x:'center'
            },
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                data: eval(users_key),
                formatter: function(name){
                    var oa = users_lastmonth_option.series[0].data;
                    for(var i = 0; i < users_lastmonth_option.series[0].data.length; i++){
                        if(name==oa[i].name){
                            return name + ' ' + oa[i].value;
                        }
                    }
                }
            },
            series : [
                {
                    name: '用户：',
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:eval(users_value),
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
        users_lastmonth.setOption(users_lastmonth_option);
        users_lastmonth.on('click', function (params) {
            var user_id = params.data.user;
            getInfo( "user", user_id, 'lastmonth', '上个月用户查询次数前10' );
            console.log(params);
        });

        function getInfo( key, value, type, title ){
            var url = "/logs/scripts/show?key="+key+"&value="+value+"&type="+type;
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
</div>