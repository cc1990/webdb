<?php 
use yii\helpers\Html;

echo Html::jsFile('@web/public/plug/echarts/echarts.min.js');

$this->params['breadcrumbs'][] = ['label' => '订正记录', 'url' => ['index']];
$this->params['breadcrumbs'][] = '订正统计';
?>
<style type="text/css">
    .sql{margin: 0 auto; }
    .lf{float: left; margin: 20px; }
	div.divview{
		height:100%;
		overflow-y:scroll;
	}
	table.tableview{
		border-collapse: collapse;
		width:100%;
		text-align:center; 
		border:1px solid black;
		overflow:scroll;
	}
	thead.theadview{
		background-color:#009ACD;
		font-size:18px;
	}
	tbody th.thview {
		background-color:#8DEEEE;
	}
	th{
		padding:5px;
		border:1px solid black;
	}
	td{
		border:1px solid black;
	}
</style>
<div>
    <div class="sql">
        <div id="database" class="lf" style="width: 98%;height:400px;"></div>
        <div id="workline" class="lf" style="width: 45%;height:350px;"></div>
        <div id="scripts" class="lf" style="width: 50%;height:350px;"></div>
        <div id="influences" class="lf" style="width: 98%;height:350px;"></div>
    </div>
    <script type="text/javascript">
        // 基于准备好的dom，初始化echarts实例
        var database = echarts.init(document.getElementById('database'));

        var database_xis = database_xis1 = database_xis2 = database_xis3 = "["; var database_value = "{[";
        
        <?php $database_xis = $database_xis1 = $database_xis2 = $database_xis3 = $database_value = ''; foreach ($database as $key => $value) {
            $database_xis .= "'".$value['db_name']."',";
            $database_xis1 .= "'".$value['sumary1']."',";
            $database_xis2 .= "'".$value['sumary2']."',";
            $database_xis3 .= "'".$value['sumary3']."',";
        } ?>

        database_xis += "<?=$database_xis?>]";
        database_xis1 += "<?=$database_xis1?>]";
        database_xis2 += "<?=$database_xis2?>]";
        database_xis3 += "<?=$database_xis3?>]";

        var now_month = '<?=date("Y年m月")?>';
        var last_month = '<?=date("Y年m月", strtotime("-1 month"))?>';
        var last2_month = '<?=date("Y年m月", strtotime("-2 month"))?>';;

        var colors = ['#5793f3', '#d14a61', '#675bba'];
        database_option = {
            color: colors,
            title : {
                text: '生产数据订正细分'
            },
            tooltip : {
                trigger: 'axis'
            },
            legend: {
                data:[now_month,last_month,last2_month]
            },
            toolbox: {
                show : true,
                feature : {
                    mark : {show: true},
                    magicType : {show: true, type: ['line', 'bar']},
                    restore : {show: true},
                    saveAsImage : {show: true},
					dataView : {
						readOnly:false,
						title : '数据视图',
						lang: ['数据视图', '关闭', '刷新'],
						optionToContent:function(opt) {
							var return_data = initDataView(opt, 'DB','#database');
							return return_data;
						}
					}
                }
            },
            grid: {
                //show: true,
                left: 'left',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            calculable : true,
            xAxis : [
                {
                    type : 'category',
                    data : eval(database_xis)
                }
            ],
            yAxis : [
                {
                    type : 'value'
                }
            ],
            series : [
                {
                    name:now_month,
                    type:'bar',
                    data:eval(database_xis1),
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
                },
                {
                    name:last_month,
                    type:'bar',
                    data:eval(database_xis2),
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
                },
                {
                    name:last2_month,
                    type:'bar',
                    data:eval(database_xis3),
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
        database.setOption(database_option);


        var workline = echarts.init(document.getElementById('workline'));
        var workline_xis = workline_xis1 = workline_xis2 = workline_xis3 = "["; var workline_value = "{[";
        
        <?php $workline_xis = $workline_xis1 = $workline_xis2 = $workline_xis3 = $workline_count = ''; foreach ($workline as $key => $value) {
            $workline_xis .= "'".$value['work_line']."',";
            $workline_xis1 .= "'".$value['sumary1']."',";
            $workline_xis2 .= "'".$value['sumary2']."',";
            $workline_xis3 .= "'".$value['sumary3']."',";
        } ?>

        workline_xis += "<?=$workline_xis?>]";
        workline_xis1 += "<?=$workline_xis1?>]";
        workline_xis2 += "<?=$workline_xis2?>]";
        workline_xis3 += "<?=$workline_xis3?>]";

        var colors = ['#5793f3', '#d14a61', '#675bba'];
        workline_option = {
            color: colors,
            title : {
                text: '业务线生产订正情况'
            },
            tooltip : {
                trigger: 'axis'
            },
            legend: {
                data:[now_month,last_month,last2_month]
            },
            toolbox: {
                show : true,
                feature : {
                    mark : {show: true},
                    magicType : {show: true, type: ['line', 'bar']},
                    restore : {show: true},
                    saveAsImage : {show: true},
					dataView : {
						show: true, 
						readOnly: false,
						title : '数据视图',
						lang: ['数据视图', '关闭', '刷新'],
						optionToContent:function(opt) {
							var return_data = initDataView(opt, '业务线', '#workline');
							return return_data;
						}
					}
                }
            },
            grid: {
                //show: true,
                left: 'left',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            calculable : true,
            xAxis : [
                {
                    type : 'category',
                    data : eval(workline_xis)
                }
            ],
            yAxis : [
                {
                    type : 'value'
                }
            ],
            series : [
                {
                    name:now_month,
                    type:'bar',
                    data:eval(workline_xis1),
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
                },
                {
                    name:last_month,
                    type:'bar',
                    data:eval(workline_xis2),
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
                },
                {
                    name:last2_month,
                    type:'bar',
                    data:eval(workline_xis3),
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
        workline.setOption(workline_option);


        var scripts = echarts.init(document.getElementById('scripts'));
        var scripts_xis = scripts_value = "[";
        
        <?php $scripts_xis = $scripts_value = ''; foreach ($scripts as $key => $value) {
            $scripts_xis .= "'".$value['monthday']."',";
            $scripts_value .= "{name:'".$value['monthday']."',type:'bar',data:[".$value['gt10'].",".$value['gt100'].",".$value['gt1000'].",".$value['gt3000'].",".$value['gt5000'].",".$value['gt10000']."],itemStyle:{normal:{label:{show:true,position:'top',textStyle:{color:'red'}}}}},";
            
        } ?>

        scripts_xis += "<?=$scripts_xis?>]";
        scripts_value += "<?=$scripts_value?>]";
        console.log(scripts_value);

        var colors = ['#5793f3', '#d14a61', '#675bba'];
        scripts_option = {
            color: colors,
            title : {
                text: '单笔生产数据订正SQL条数'
            },
            tooltip : {
                trigger: 'axis'
            },
            legend: {
                data:eval(scripts_xis)
            },
            toolbox: {
                show : true,
                feature : {
                    mark : {show: true},
                    magicType : {show: true, type: ['line', 'bar']},
                    restore : {show: true},
                    saveAsImage : {show: true},
					dataView : {
						show: true, 
						readOnly: false,
						title : '数据视图',
						lang: ['数据视图', '关闭', '刷新'],
						optionToContent:function(opt) {
							var return_data = initDataView(opt, 'sql条数', '#scripts');
							return return_data;
						}
					}
                }
            },
            grid: {
                //show: true,
                left: 'left',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            calculable : true,
            xAxis : [
                {
                    type : 'category',
                    data : ['大于10条','大于100条','大于1000条','大于3000条','大于5000条','大于10000条']
                }
            ],
            yAxis : [
                {
                    type : 'value'
                }
            ],
            series : eval(scripts_value)
        };
        scripts.setOption(scripts_option);


        var influences = echarts.init(document.getElementById('influences'));
        var influences_xis = influences_value = "[";
        
        <?php $influences_xis = $influences_value = ''; foreach ($influences as $key => $value) {
            $influences_xis .= "'".$value['monthday']."',";
            $influences_value .= "{name:'".$value['monthday']."',type:'bar',data:[".$value['gt5k'].",".$value['gt10k'].",".$value['gt50k'].",".$value['gt100k'].",".$value['gt500k'].",".$value['gt1000k'].",".$value['gt5000k']."],itemStyle:{normal:{label:{show:true,position:'top',textStyle:{color:'red'}}}}},";
            
        } ?>

        influences_xis += "<?=$influences_xis?>]";
        influences_value += "<?=$influences_value?>]";
        console.log(influences_value);

        var colors = ['#5793f3', '#d14a61', '#675bba'];
        influences_option = {
            color: colors,
            title : {
                text: '单笔生产数据订正数量情况'
            },
            tooltip : {
                trigger: 'axis'
            },
            legend: {
                data:eval(influences_xis)
            },
            toolbox: {
                show : true,
                feature : {
                    mark : {show: true},
                    magicType : {show: true, type: ['line', 'bar']},
                    restore : {show: true},
                    saveAsImage : {show: true},
					dataView : {
						show: true, 
						readOnly: false,
						title : '数据视图',
						lang: ['数据视图', '关闭', '刷新'],
						optionToContent:function(opt) {
							var return_data = initDataView(opt, '订正数据量', '#influences');
							return return_data;
						}
					}
                }
            },
            grid: {
                //show: true,
                left: 'left',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            calculable : true,
            xAxis : [
                {
                    type : 'category',
                    data : ['大于5k','大于1W','大于5W','大于10W','大于50W','大于100W','大于500W']
                }
            ],
            yAxis : [
                {
                    type : 'value'
                }
            ],
            series : eval(influences_value)
        };
        influences.setOption(influences_option);

		/**
		 * 设置视图展开时的样子
		 */
		function initDataView(opt, name, container) 
		{
			if($(container).length > 0) {
				$(container).css({'user-select':'text'});
			}

			//定义全部要渲染的数据
			var table_item = {};

			//定义title信息
			var title = opt.title[0].text;

			//定义头信息
			var head_name_item = [name];
			var series = opt.series;
			for(var length=0; length < series.length; length++) {
				head_name_item.push(series[length].name);
			}

			//定义每一行的数据
			var row_list = [];
			var xaxis_item = opt.xAxis[0]['data'];
			for(var key in xaxis_item) {
				var item = [];
				item.push(xaxis_item[key]);
				for(var start=0; start<series.length; start++) {
					item.push(series[start].data[key])
				}
				row_list.push(item);
			}

			table_item = {	'title':title,
							'thead':head_name_item,
							'tbody':row_list	};
			var table = "<div class='divview'><table cellspacing='0' cellpadding='0' class='tableview'>\
							<thead class='theadview'>\
								<th class='thview' colspan="+table_item.thead.length+">"+table_item.title+"</th>\
							</thead>\
							<tbody>\
							<tr>";
			for(key in table_item.thead) {
				table +="<th class='thview'>"+table_item.thead[key]+"</th>";
			}
			table += "</tr>";
			for(key in table_item.tbody) {
				table += "<tr>";
					for(innerkey in table_item.tbody[key]) {
						table += "<td>"+table_item.tbody[key][innerkey]+"</td>";
					}
				table += "</tr>";
			}
			table += "</tbody></table></div>";
			return table;
		}        
    </script>
</div>
