<?php 
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = "数据迁移";
$this->params['breadcrumbs'] = '';
?>
<style type="text/css">
    h4{height: 30px;}
    .plus_icon{
        padding: 1px 5px;
        border: 1px solid #8a8484;
    }
    .minus_icon{
        padding: 1px 7px;
        border: 1px solid #8a8484;
    }
    .btn-success{margin-left: 100px;}
    .c_left{float: left;}
    .rule_left{width: 48%;float: left;}
    .rule_list{width: 100%; }
    .log_list{width: 100%; }
    .rule_info{width: 50%;float: right; display: none;}
    .log_info{width: 50%;float: right; display: none;}
    .title_name{text-align: left;}
    .sharding_from{width: 30%;float: left;}
    .sharding_to{width: 30%;float: left;}
    .sharding{ width: 100%; overflow: auto; display: none; }
    .tb_over{background-color: #b5b0b0;}
    .red{color: red;}
    pre{text-align: left;}
</style>
<div class="site-index div-index">
    <form id="formExecuteSql" name="formExecuteSql" enctype="multipart/form-data">
    <div class="jumbotron">
        <label></label>
        <h4 align="left">
            选择方式：
            <select name="type" id="type">
                <option value='common'>通用环境导入分库分表</option>
                <option value='sharding'>分库分表环境导入分库分表</option>
            </select>
        </h4>
        <div class="common">
            <h4 align="left">
                源IP地址：
                <input type="text" name="from_ip">
            </h4>
            <h4 align="left">
                源数据库：
                <input type="text" name="db_name"><span style="color: red">*如果填写了源库名, 则以源库名为主，否则取目标库名</span>
            </h4>
            <h4 align="left">
                目标项目：
                <select id="Project" name="project">
                </select>
            </h4>
            <h4 align="left">
                目标环境：
                <select id="environment" name="environment">
                </select>
            </h4>
            <h4 align="left">
                目标逻辑库：
                <select id="DBName" name="DBName">
                </select>
            </h4>
            <h4 align="left" style="display: none">
                是否创建表：
                <input type='checkbox' name='create_tb' value = 'y'>
            </h4>
        </div>
        <div class="sharding">
            <div class="sharding_from">
                <h4 align="left">
                    来源项目：
                    <select id="from_project" name="from_project">
                    </select>
                </h4>
                <h4 align="left">
                    来源环境：
                    <select id="from_environment" name="from_environment">
                    </select>
                </h4>
                <h4 align="left">
                    来源库名：
                    <select id="from_DBName" name="from_DBName">
                    </select>
                </h4>
            </div>
            <div class="sharding_to">
                <h4 align="left">
                    目标项目：
                    <select id="to_project" name="to_project">
                    </select>
                </h4>
                <h4 align="left">
                    目标环境：
                    <select id="to_environment" name="to_environment">
                    </select>
                </h4>
                <h4 align="left">
                    目标库名：
                    <select id="to_DBName" name="to_DBName">
                    </select>
                </h4>
            </div>
        </div>
        <div>
            <h4 class="title_name">需要迁移的表</h4>
            <div class='rule_left'>
                <div class="rule_list">
                    <table class="sui-table table-bordered table-zebra">
                        <thead>
                            <tr>
                                <th width="30"><input class="check_all" type="checkbox" name="" ></th>
                                <th>表名</th>
                                <th>分库规则</th>
                                <th>分表规则</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="tblist">
                            
                        </tbody>
                    </table>
                    <p><a class="sui-btn btn-xlarge btn-success" id="create">建分片表</a>&nbsp;&nbsp;<a class="sui-btn btn-xlarge btn-info" id="execute">数据分片</a></p>
                    
                </div>
                <div class="log_list">
                    
                </div>
            </div>
            <div class="rule_info">
                <table class="sui-table table-bordered table-zebra">
                    <thead>
                        <tr>
                            <th>表下标</th>
                            <th class="title_ip">目标IP</th>
                            <th>库下标</th>
                        </tr>
                    </thead>
                    <tbody id="tbinfolist">
                        
                    </tbody>
                </table>
            </div>
            <div class="log_info">

            </div>
        </div>
    </div>
    
    </form>
</div>
<?=Html::jsFile('@web/public/js/jquery.min.js') ?>
<?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
<script type="text/javascript">
    var project_list = <?= $project_list ?>;
    var project_data = <?= $project_data ?>;
    var dblist = "";
    var tbAmountPerDB = 0;
    var environment_array = new Array();
    environment_array['dev'] = '开发';
    environment_array['test'] = '测试';
    environment_array['test_trunk'] = '测试主干';
    environment_array['pre'] = '预发布';
    environment_array['pro'] = '线上';
    environment_array['dev_trunk'] = '研发主干';

    $(document).ready(function(){
        getProject();
    });

    $("#type").on("change", function(){
        var type = $(this).val();
        if ( type == "common" ) {
            $(".sharding").hide();
            $(".common").show();
            $(".title_ip").html("目标IP");
        } else if( type == "sharding" ){
            $(".common").hide();
            $(".sharding").show();
            $(".title_ip").html("来源IP");
        }
        getProject();
        $("#tbinfolist").html("");
        $(".rule_info").hide();
    });


    $("#add_table").on('click', function(){
        var tpl = "<h4 align='left'>输入表名：<input type='text' name='table[]' >   <a href='javascript:;' class='minus'><span class='minus_icon'>-</span></a></h4>";
        $(".jumbotron").append(tpl);
        $(".jumbotron").undelegate(".minus", "click").delegate(".minus", 'click', function(){
            $(this).parent().remove();
        });
    });

    $("#execute_sql").on('click', function(){
        var url = "<?=Url::to(['migrate/execute'])?>";
    });

    //下拉选择项目
    $("#Project").on("change", function(){
        var project = $(this).val();
        var dbname_tpl = '';
        var x = 0;
        $("#environment").html(''); $("#DBName").html('');
        $.each(project_list, function(i, item){
            if( project == i ){
                $.each( item, function(j, vo){
                    if( j == 'dev' ){
                        //将开发Dev环境排在第一位，防止误操作
                        $("#environment").prepend("<option value='"+j+"' selected>"+environment_array[j]+"</option>");
                        
                        $("#DBName").html('');
                        $.each(vo, function(z, v){
                            $("#DBName").append("<option value='"+v+"'>"+v+"</option>");
                        });
                    }else{
                        $("#environment").append("<option value='"+j+"'>"+environment_array[j]+"</option>");
                    }

                    if( x == 0 && j != 'dev' ){
                        if( vo != '' ){
                            $.each(vo, function(z, v){
                                dbname_tpl = "<option value='"+v+"'>"+v+"</option>";
                                $("#DBName").append(dbname_tpl);
                            });
                        }
                    }
                    x++;
                });
            }
        });
        getRule();
        clear_list();
    });

    $("#from_project").on("change", function(){
        var project = $(this).val();
        var dbname_tpl = '';
        var x = 0;
        $("#from_environment").html(''); $("#from_DBName").html('');
        $.each(project_list, function(i, item){
            if( project == i ){
                $.each( item, function(j, vo){
                    if( j == 'dev' ){
                        //将开发Dev环境排在第一位，防止误操作
                        $("#from_environment").prepend("<option value='"+j+"' selected>"+environment_array[j]+"</option>");

                        $("#from_DBName").html('');
                        $.each(vo, function(z, v){ 
                            $("#from_DBName").append("<option value='"+v+"'>"+v+"</option>");
                        });
                    }else{
                        $("#from_environment").append("<option value='"+j+"'>"+environment_array[j]+"</option>");
                    }

                    if( x == 0 && j != 'dev' ){
                        if( vo != '' ){
                            $.each(vo, function(z, v){
                                dbname_tpl = "<option value='"+v+"'>"+v+"</option>";
                                $("#from_DBName").append(dbname_tpl);
                            });
                        }
                    }
                    x++;
                });
            }
        });
        getSardingRule();
        clear_list();
    });

    $("#to_project").on("change", function(){
        var project = $(this).val();
        var dbname_tpl = '';
        var x = 0;
        $("#to_environment").html(''); $("#to_DBName").html('');
        $.each(project_list, function(i, item){
            if( project == i ){
                $.each( item, function(j, vo){
                    if( j == 'dev' ){
                        //将开发Dev环境排在第一位，防止误操作
                        $("#to_environment").prepend("<option value='"+j+"' selected>"+environment_array[j]+"</option>");

                        $("#to_DBName").html('');
                        $.each(vo, function(z, v){
                            $("#to_DBName").append("<option value='"+v+"'>"+v+"</option>");
                        });
                    }else{
                        $("#to_environment").append("<option value='"+j+"'>"+environment_array[j]+"</option>");
                    }
                    if( x == 0 && j != 'dev' ){
                        if( vo != '' ){
                            $.each(vo, function(z, v){
                                dbname_tpl = "<option value='"+v+"'>"+v+"</option>";
                                $("#to_DBName").append(dbname_tpl);
                            });
                        }
                    }
                    x++;
                });
            }
        });
    });

    //选择环境
    $("#environment").on("change", function(){
        var project = $("#Project option:selected").val();
        var environment = $(this).val();
        var environment_tpl = '', environment_tpl = '', dbname_tpl = '';
        var x = 0;
        $("#DBName").html('');
        $.each(project_list, function(i, item){
            if( project == i ){
                $.each( item, function(j, vo){
                    if( environment == j ){
                        $.each(vo, function(z, v){
                            dbname_tpl = "<option value='"+v+"'>"+v+"</option>";
                            $("#DBName").append(dbname_tpl);
                        });
                    }
                });
            }
        });
        getRule();
        clear_list();
    });

    $("#from_environment").on("change", function(){
        var project = $("#from_project option:selected").val();
        var environment = $(this).val();
        var environment_tpl = '', environment_tpl = '', dbname_tpl = '';
        var x = 0;
        $("#from_DBName").html('');
        $.each(project_list, function(i, item){
            if( project == i ){
                $.each( item, function(j, vo){
                    if( environment == j ){
                        $.each(vo, function(z, v){
                            dbname_tpl = "<option value='"+v+"'>"+v+"</option>";
                            $("#from_DBName").append(dbname_tpl);
                        });
                    }
                });
            }
        });
        getSardingRule();
        clear_list();
    });

    $("#to_environment").on("change", function(){
        var project = $("#to_project option:selected").val();
        var environment = $(this).val();
        var environment_tpl = '', environment_tpl = '', dbname_tpl = '';
        var x = 0;
        $("#to_DBName").html('');
        $.each(project_list, function(i, item){
            if( project == i ){
                $.each( item, function(j, vo){
                    if( environment == j ){
                        $.each(vo, function(z, v){
                            dbname_tpl = "<option value='"+v+"'>"+v+"</option>";
                            $("#to_DBName").append(dbname_tpl);
                        });
                    }
                });
            }
        });
    });

    $("#DBName").on("change", function(){
        getRule();
    });
    $("#from_DBName").on("change", function(){
        getSardingRule();
    });

    /**
     * 获取项目列表的信息
     * @param  {[type]} o [description]
     * @return {[type]}   [description]
     */
    function getProject(){
        var url = "<?= Url::to(['sharding/get-project-list']); ?>";
        var pro_tpl = '', environment_tpl = '', dbname_tpl = '';
        $("#Project").html(''); $("#environment").html(''); $("#DBName").html('');
        $("#from_project").html(''); $("#from_environment").html(''); $("#from_DBName").html('');
        $("#to_project").html(''); $("#to_environment").html(''); $("#to_DBName").html('');
        
        var result = project_data;
        if( result != '' ){
            for (var i = 0; i < result.length; i++) {
                pro_tpl = "<option value='"+result[i].name+"'>"+result[i].name+"</option>";
                $("#Project").append(pro_tpl);
                $("#from_project").append(pro_tpl);
                $("#to_project").append(pro_tpl);
                //项目列表
            }

            //默认第一个项目下对应的环境列表
            for (var j = 0; j < result[0].environment.length; j++) {
                environment_tpl = "<option value='"+result[0].environment[j].name+"'>"+result[0].environment[j].title+"</option>";
                $("#environment").append(environment_tpl);
                $("#from_environment").append(environment_tpl);
                $("#to_environment").append(environment_tpl);
            }

            //默认显示第一个项目下第一个环境下对应的数据库列表
            for (var z = 0; z < result[0].environment[0].database.length; z++) {
                dbname_tpl = "<option value='"+result[0].environment[0].database[z]+"'>"+result[0].environment[0].database[z]+"</option>";
                $("#DBName").append(dbname_tpl);
                $("#from_DBName").append(dbname_tpl);
                $("#to_DBName").append(dbname_tpl);
            }
            getRule();
        }else{
            alert("暂无项目");
        }
            
    }

    /*
    获取分库分表规则
     */
    function getRule(){
        var url = "<?=Url::to(['migrate/get-rule-list']) ?>";
        var project = $("#Project option:selected").val();
        var environment = $("#environment option:selected").val();
        var dbname = $("#DBName option:selected").val();

        $.ajax({
            type: 'post',
            url: url,
            data: {project: project, environment: environment, dbname: dbname},
            dataType: "json",
            async:false,
            success: function( result ){
                if( result.error ){
                    alert( result.error );
                }else{
                    var tblist = result.data.tblist;
                    dblist = result.data.dblist;
                    var tpl = "";
                    $.each( tblist, function(i, item){
                        tpl += "<tr class='tbinfo'>"
                            + "<td><input type='checkbox' name='tbname[]' value='"+item.tbName+"'></td>"
                            + "<td><div class='c_left tbname'>"+item.tbName+"</div></td>"
                            + "<td><div class='c_left'>"+item.dbIndex+"</div></td>"
                            + "<td><div class='c_left'>"+(item.dbIndex * item.tbAmountPerDB)+"</div></td>"
                            + "<td><span class='info'>分表信息</span>&nbsp;|&nbsp;<span class='log'>数据分片日志</span>&nbsp;|&nbsp;<span class='createlog'>建分片表日志</span></td>"
                            + "</tr>";
                    } );
                    $("#tblist").undelegate(".info", "click").delegate(".info", "click", function(){
                        $(".info").removeClass("tb_over");
                        $(".log").removeClass("tb_over");
                        $(".createlog").removeClass("tb_over");
                        $(this).addClass("tb_over");
                        var tbname_ = $(this).parent().parent().find(".tbname").text();
                        
                        var tpl_ = "";
                        $.each( tblist, function( x, v ){
                            if ( v.tbName == tbname_ ) {
                                tbAmountPerDB = v.tbAmountPerDB;
                            }
                        });
                        var z = 0;var x = 0;
                        $.each( dblist, function(j, vo){
                            var i = vo.dbBeginIndex;
                            var j = vo.dbEndIndex;
                            var ip = vo.masterIP;
                            for (i; i <= j; i++) {
                                for (z; z <= (Number(Number(j)+1)*Number(tbAmountPerDB)-1); z++) {
                                    tpl_ += "<tr>"
                                    + "<td>"+tbname_+"_"+z+"</td>"
                                    + "<td>"+ip+"</td>"
                                    + "<td>"+(parseInt(z/Number(tbAmountPerDB)))+"</td>"
                                    + "</tr>";
                                }
                                z = Number(Number(j)+1)*Number(tbAmountPerDB);
                            }
                        } );
                        $(".log_info").hide();
                        $("#tbinfolist").html(tpl_);
                        $(".rule_info").show();
                    } );
                    
                    $("#tblist").undelegate(".log", "click").delegate( ".log", "click", function(){
                        $(".info").removeClass("tb_over");
                        $(".log").removeClass("tb_over");
                        $(".createlog").removeClass("tb_over");
                        $(this).addClass("tb_over");
                        $(".rule_info").hide();

                        var tbname = $(this).parent().parent().find(".tbname").text();
                        var type = $("#type option:selected").val();
                        var database = $("#DBName option:selected").val();
                        //var url = "<?=URL::to(['migrate/get-execute-log']) ?>?type="+type+"&database="+database+"&tbname="+tbname;
                        //$.getJSON( url, function( result ){
                            layer.open({
                                type: 2,
                                title: '表：'+tbname,
                                shadeClose: true,
                                shade: 0.8,
                                area: ['700px', '400px'],
                                content: "<?=URL::to(['migrate/get-execute-log']) ?>?type="+type+"&database="+database+"&tbname="+tbname
                            });
                        //} );
                    } );
                    
                    $("#tblist").undelegate(".createlog", "click").delegate( ".createlog", "click", function(){
                        $(".info").removeClass("tb_over");
                        $(".log").removeClass("tb_over");
                        $(".createlog").removeClass("tb_over");
                        $(this).addClass("tb_over");
                        $(".rule_info").hide();

                        var tbname = $(this).parent().parent().find(".tbname").text();
                        var database = $("#DBName option:selected").val();
                        //var url = "<?=URL::to(['migrate/get-execute-log']) ?>?type="+type+"&database="+database+"&tbname="+tbname;
                        //$.getJSON( url, function( result ){
                            layer.open({
                                type: 2,
                                title: '表：'+tbname,
                                shadeClose: true,
                                shade: 0.8,
                                area: ['700px', '400px'],
                                content: "<?=URL::to(['migrate/get-create-log']) ?>?database="+database+"&tbname="+tbname
                            });
                        //} );
                    } );

                    $("#tblist").on( "click", "input[name='tbname[]']", function(){
                        if( $(this).is(":checked") ){
                            $(this).parent().parent().find(".tbname").addClass("red");
                        }else{
                            $(this).parent().parent().find(".tbname").removeClass("red");
                        }
                        
                    } );  

                    $("#tblist").html(tpl);
                    $(".check_all").undelegate("", "click").delegate("", 'click', function(){
                        $("[name = 'tbname[]']:checkbox").prop("checked", this.checked);
                        if( $(this).is(":checked") ){
                            $(".tbname").addClass("red");
                        }else{
                            $(".tbname").removeClass("red");
                        }
                    });
                }
            } 
        });
    }

    $(".log").on( "click", function(){
        var tbname = $(this).parent().parent().find(".tbname").text();
        var type = 'common';
        var database = $("#DBName option:selected").val();
        var url = "<?=URL::to(['migrate/get-execute-log']) ?>?type="+type+"&database="+database+"&tbname="+tbname;
        $.getJSON( url, function( result ){
            
            $(".rule_info").hide();
            $(".log_info").html("<pre>"+result+"</pre>");
            $(".log_info").show();
        } );
    } );

    $(".createlog").on( "click", function(){
        var tbname = $(this).parent().parent().find(".tbname").text();
        var database = $("#DBName option:selected").val();
        var url = "<?=URL::to(['migrate/get-create-log']) ?>?database="+database+"&tbname="+tbname;
        $.getJSON( url, function( result ){
            
            $(".rule_info").hide();
            $(".log_info").html("<pre>"+result+"</pre>");
            $(".log_info").show();
        } );
    } );
    /*
    获取分库分表规则
     */
    function getSardingRule(){
        var url = "<?=Url::to(['migrate/get-rule-list']) ?>";
        var project = $("#from_project option:selected").val();
        var environment = $("#from_environment option:selected").val();
        var dbname = $("#from_DBName option:selected").val();

        $.ajax({
            type: 'post',
            url: url,
            data: {project: project, environment: environment, dbname: dbname},
            dataType: "json",
            async:false,
            success: function( result ){
                if( result.error ){
                    alert( result.error );
                }else{
                    var tblist = result.data.tblist;
                    dblist = result.data.dblist;
                    var tpl = "";
                    $.each( tblist, function(i, item){
                        tpl += "<tr class='tbinfo'>"
                            + "<td><input type='checkbox' name='tbname[]' value='"+item.tbName+"'></td>"
                            + "<td><div class='c_left tbname'>"+item.tbName+"</div></td>"
                            + "<td><div class='c_left'>"+item.dbIndex+"</div></td>"
                            + "<td><div class='c_left'>"+(item.dbIndex * item.tbAmountPerDB)+"</div></td>"
                            + "<td><span class='info'>分表信息</span>&nbsp;|&nbsp;<span class='log'>查看日志</span></td>"
                            + "</tr>";
                    } );
                    
                    $("#tblist").html(tpl);
                }
            } 
        });
    }

    $("#execute").on("click", function(){
        if( $("#environment option:selected").val() == 'pro' ){
            if( !confirm("是否确认执行线上环境的操作？") ){
                return false;
            }
        }

        if( !confirm("是否确认开始导入数据？") ){
            return false;
        }

        if( checkForm() == false ){
            return false;
        }
        var url = "<?=Url::to(['migrate/execute']) ?>";
        $(this).html('执行中');
        $(this).addClass('disabled');
        $.ajax({
            url: url,
            data: $('#formExecuteSql').serialize(),
            type: 'post',
            dataType: 'json',
            success: function( result ){
                alert( result.msg );
                console.log(result.msg);
                $("#execute").removeClass('disabled');
                $("#execute").html('数据分片');
            },
            error: function(data){
                $("#execute").removeClass('disabled');
                $("#execute").html('数据分片');
                console.log(data.responseText);
                alert("请求失败！");
            }
        });
    });


    $("#create").on("click", function(){
        if( $("#environment option:selected").val() == 'pro' ){
            if( !confirm("是否确认执行线上环境的操作？") ){
                return false;
            }
        }

        if( !confirm("是否确认开始建分片表？") ){
            return false;
        }

        if( checkForm() == false ){
            return false;
        }
        var url = "<?=Url::to(['migrate/create-table']) ?>";
        $(this).html('执行中');
        $(this).addClass('disabled');
        $.ajax({
            url: url,
            data: $('#formExecuteSql').serialize(),
            type: 'post',
            dataType: 'json',
            success: function( result ){
                alert( result.msg );
                console.log(result.msg);
                $("#create").removeClass('disabled');
                $("#create").html('建分片表');
            },
            error: function(data){
                $("#create").removeClass('disabled');
                $("#create").html('建分片表');
                console.log(data.responseText);
                alert("请求失败！");
            }
        });
    });

    function clear_list(){
        $("#tbinfolist").html("");
        $(".rule_info").hide();
    }

    function checkForm(){
        var type = $("#type option:selected").val();
        var from_ip = $("input[name='from_ip']").val();
        var from_DBName = $("#from_DBName option:selected").val();
        var to_DBName = $("#to_DBName option:selected").val();
        if( type == 'common' ){
            var re =  /^([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/;
            if ( !re.test(from_ip) ) {
                alert("请填写正确的源IP地址");
                return false;
            }
            return true;
        }else if ( type == 'sharding' ) {
            if( (from_DBName != to_DBName) || from_DBName == '' | to_DBName == '' ){
                alert("来源数据库和目标数据库必须相同！");
                return false;
            }
            return true;
        } else {
            alert("请选择执行方式！");
            return false;
        }
    }

</script>