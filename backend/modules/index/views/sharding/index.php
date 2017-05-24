<?php
use yii\helpers\Url;
use yii\helpers\Html;
/* @var $this yii\web\View */

$this->title = '分库分表操作';
?>
<style type="text/css">
    .CodeMirror-sizer{font-size: 12px;}
    .control{margin: 20px 20px;}
    .control select{width: 190px;}
</style>
    <div id="main">
        <div class="tree">
            <div class="check-menu">
                <ul>
                    <li><a href="/index/default">通用库</a></li>
                    <li><a href="javascript:;" class="active">分库分表</a></li>
                </ul>
            </div>
            <div class="control">
                查询方式：
                <label><input type="radio" name="type" value="project" checked>项目</label>&nbsp;&nbsp;&nbsp;
                <label><input type="radio" name="type" value="environment" >环境</label>
            </div>

            <div class="control project">
                选择项目：
                <select id="Project" name="Project">
                </select>
            </div>

            <div class="control environment">
                选择环境：
                <select id="environment" name="environment">
                </select></br>
                <font size="2" color="red">(如果此处选项值为空，请联系管理员授权)</font>
            </div>
            <div class="control loading">
                <div class="sui-loading loading-xxsmall loading-inline loading-dark"><i class="sui-icon icon-pc-loading"></i>数据正在加载中，请稍后......</div>
            </div>
            <div class="tree-menu">
                <ul class="well" id="DBName">
                    
                </ul>
            </div>
            <script type="text/javascript">
                
            </script>
        </div>
        <div class="spread" title="隐藏"><div class="img"></div></div>
        <div class="right" >
            <div class="container">
                <div class="tabbox" id="tabbox">
                    <ul class="sui-nav nav-tabs nav-large">
                        <li class="active"><span class="nav-title">首页&nbsp;&nbsp;&nbsp;</span></li>
                    </ul>
                </div>
                <div id="pagebox">
                    <div class="pagebox-index">
                        <div class="index-box">
                            <div class="left">
                                <div class="action-sm">
                                    <h4>相关说明：</h4>
                                    <ul>
                                        <li>1、查询方式可根据项目或者环境两种方式联动查询：根据项目联动环境、根据环境联动项目； </li>
                                        <li>2、选择项目和环境，列出该项目环境下的数据库；</li>
                                        <li>3、双击数据库名，右侧创建SQL窗口标签页；</li>
                                        <li>4、点击多个标签页可切换查看执行的SQL信息和结果集，标签页上限5个；</li>
                                        <li>5、对于无权限的环境或库表目标，请走工单申请权限或联系DBA沟通；</li>
                                        <li>6、如果项目或环境下拉列表为空，请查看配置中心是否有相应的配置，若配置存在请联系DBA沟通或授权；</li>
                                        <li>7、更改项目或者环境之后，需要重新创建SQL窗口，选择的信息才能生效。（创建方式为鼠标左键双击库名）。</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pagebox-list" style="display: none;">
                        <form class="sui-form">
                            <div class="navbox">
                                <div>
                                    <div style="display: none;">
                                        <input type="text" name="Project">
                                        <input type="text" name="environment">
                                        <input type="text" name="DBName">
                                    </div>
                                    <div class="control-group">
                                        <input type="checkbox" name="batch" value="1">&nbsp;批量注释&nbsp;<input type="text" name="batch_notes" class="input-xlarge"><i class="sui-icon icon-tb-questionfill" title="请在需要相同类型批量操作的时候使用本功能"></i>
                                    </div>
                                    <div style="width">
                                        <div class="control-group">
                                        <a href="javascript:void(0);" class="sui-btn btn-info white" id="sql_verify">SQL检测</a>
                                        <a href="javascript:void(0);" class="sui-btn btn-success white" id="submit" >执行SQL</a>
                                        <?php if ( $is_administrator ) { ?>
                                        <a href="javascript:void(0);" class="sui-btn btn-success white" id="sql_export">查询导出</a>
                                        <?php } ?>
                                        </div>
                                    </div>
                                
                                </div>
                            </div>
                            <div class="sqlbox">
                                <textarea name="sqlinfo" class="sqlinfo" id=""></textarea>
                            </div>
                        </form>
                        <div class="resultnav">
                            <ul>
                                <li class="active"><span class="resultnav-title">消息</span></li>
                            </ul>
                        </div>
                        <div class="resultbox">
                            <div class="result-info-box">
                            </div>
                            
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- 右键菜单栏DOM属性 -->
    <div class="contextMenu" id="dbMenu" style="display: none;">
        <ul>
            <li id="refresh-db"><i class="iconfont">&#xe620;</i>加载表</li>
        </ul>
    </div>
    <div class="contextMenu" id="tbMenu" style="display: none;">
        <ul>
            <li id="open-table"><i class="iconfont">&#xe638;</i>SQL操作</li>
            <li id="select-table" class="nav-border-bottom"><i class="iconfont">&#xe637;</i>打开表</li>
            <li id="info-table"><i class="iconfont">&#xe619;</i>表信息</li>
        </ul>
    </div>
    <!-- 右键菜单栏DOM属性 -->
    <?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
    <?=Html::jsFile('@web/public/js/jquery.contextmenu.r2.js') ?>
    <?=Html::cssFile('@web/public/plug/easyui/easyui.css')?>
    <?=Html::jsFile('@web/public/plug/easyui/jquery.easyui.min.js') ?>
    <?=Html::jsFile('@web/public/js/sharding.js') ?>

    <link rel=stylesheet href="/codemirror/doc/docs.css">
    <link rel="stylesheet" href="/codemirror/lib/codemirror.css" />
    <link rel="stylesheet" href="/codemirror/theme/mysql.css" />
    <script src="/codemirror/lib/codemirror.js"></script>
    <script src="/codemirror/mode/sql/sql.js"></script>
    <link rel="stylesheet" href="/codemirror/addon/hint/show-hint.css" />
    <script src="/codemirror/addon/hint/show-hint.js"></script>
    <script src="/codemirror/addon/hint/sql-hint.js"></script>
    <script src="/codemirror/addon/hint/get-dynamic-keyworks.js"></script>
<script type="text/javascript">
    window.editor = new Array;
    var project_list = "";
    var environ_list = "";
    var environment_array = new Array();
    environment_array['dev'] = '开发';
    environment_array['test'] = '测试';
    environment_array['test_trunk'] = '测试主干';
    environment_array['pre'] = '预发布';
    environment_array['pro'] = '线上';
    environment_array['dev_trunk'] = '研发主干';
    var environment_rule = '<?=$environment_rule ?>';
    var environment_rule_array = eval('('+environment_rule+')');

    $(document).ready(function(){
        getProject( $("#DBHost") );
    });


    $("input[name='is_limit_check']").on( "click", function() {
        if( $(this).is(":checked") ){
            $("#is_limit").val(1);
        }else{
            $("#is_limit").val("");
        }
    } );

    $("#batch").bind("click", function () {
        if(this.checked === true){
            $("#batch_notes_area").show();
        }else{
            $("#batch_notes_area").hide();
        }
    });

    $("input[name='type']").on('change', function(){
        if( $(this).val() == 'project' ){
            window.location.reload();
        }else{
            $(".environment").insertBefore(".project");
            var environment_tpl = ""; var project_tpl = ''; var dbname_tpl = '';var x = 0;var y = 0;
            $.each(environ_list, function(i, item){
                environment_tpl += "<option value="+i+">"+environment_array[i]+"</option>";
                if( x == 0){
                    $.each(item, function(j, vo){
                        project_tpl += "<option value="+j+">"+j+"</option>";
                        if ( y == 0 ) {
                            $.each(vo, function(z, v){
                                dbname_tpl += "<li class='dbinfo' data-dbname='"+v+"'><div class='db-name' ><span class='db-name-title'><i class='iconfont'>&#xe606;</i>"+v+"</span></div></li>";
                                //dbname_tpl += "<option value="+v+">"+v+"</option>";
                            });
                            $("#DBName").html(dbname_tpl);
                            load_function();
                        }
                        y++;
                    });
                    $("#Project").html(project_tpl);
                }
                x++;
            });
            $("#environment").html(environment_tpl);
        }
    });

    //下拉选择项目
    $("#Project").on("change", function(){
        var type = $("input[name='type']:checked").val();
        if( type == 'project' ){
            var project = $(this).val();
            var x = 0;
            $("#environment").html(''); $("#DBName").html('');
            $.each(project_list, function(i, item){
                if( project == i ){ //获取该项目下的信息
                    $.each( item, function(j, vo){
                        $("#environment").prepend("<option value='"+j+"'>"+environment_array[j]+"</option>");
                        if(x == 0){
                            $.each(vo, function(z, v){
                                $("#DBName").append("<li class='dbinfo' data-dbname='"+v+"'><div class='db-name' ><span class='db-name-title'><i class='iconfont'>&#xe606;</i>"+v+"</span></div></li>");
                            });

                            load_function();
                        }
                        
                        x++;
                    });
                }
            });
        }else if( type == 'environment' ){
            var environment = $("#environment option:selected").val();
            var project = $(this).val();
            $("#DBName").html('');
            $.each( environ_list, function(i, item){
                if( environment == i ){
                    $.each( item, function(j, vo){
                        if( project == j ){
                            $.each(vo, function(z, v){
                                $("#DBName").append("<li class='dbinfo' data-dbname='"+v+"'><div class='db-name' ><span class='db-name-title'><i class='iconfont'>&#xe606;</i>"+v+"</span></div></li>");
                            });
                            load_function();
                        }
                    } )
                }
            } )
        }

        change_query_number();
    });

    //选择环境
    $("#environment").on("change", function(){
        var type = $("input[name='type']:checked").val();
        if( type == 'project' ){
            var project = $("#Project option:selected").val();
            var environment = $(this).val();
            var x = 0;
            $("#DBName").html('');
            $.each(project_list, function(i, item){
                if( project == i ){
                    $.each( item, function(j, vo){
                        if( environment == j ){
                            $.each(vo, function(z, v){
                                $("#DBName").append("<li class='dbinfo' data-dbname='"+v+"'><div class='db-name' ><span class='db-name-title'><i class='iconfont'>&#xe606;</i>"+v+"</span></div></li>");
                            });
                            load_function();
                        }
                    });
                }
            });
        }else if( type == 'environment' ){
            var environment = $(this).val();
            var x = 0; var project_tpl = ''; var dbname_tpl = '';
            $("#Project").html(''); $("#DBName").html('');
            $.each(environ_list, function(i, item){
                if( environment == i ){
                    $.each( item, function( j, vo){
                        project_tpl += "<option value="+j+">"+j+"</option>";
                        if ( x == 0 ) {
                            $.each(vo, function(z, v){
                                dbname_tpl += "<li class='dbinfo' data-dbname='"+v+"'><div class='db-name' ><span class='db-name-title'><i class='iconfont'>&#xe606;</i>"+v+"</span></div></li>";
                            });
                            $("#DBName").html(dbname_tpl);
                            load_function();
                        }
                        x++;
                    });
                    $("#Project").html(project_tpl);
                }
            });
        }
        change_query_number();
    });

    /**
     * 获取项目列表的信息
     * @param  {[type]} o [description]
     * @return {[type]}   [description]
     */
    function getProject( o ){
        $("input[name='type']").attr('disabled', true);
        var ip = o.val();
        var url = "<?= Url::to(['sharding/get-project-list']); ?>";
        var pro_tpl = '', environment_tpl = '', dbname_tpl = '';
        $("#Project").html(''); $("#environment").html(''); $("#DBName").html('');
        $.ajax({
            type: 'get',
            url: url,
            data: {},
            dataType: "json",
            //async:false,
            success: function( res ){
                var result = res.data;
                if( result != '' ){
                    for (var i = 0; i < result.length; i++) {
                        pro_tpl = "<option value='"+result[i].name+"'>"+result[i].name+"</option>";
                        $("#Project").append(pro_tpl);
                    }

                    //默认第一个项目下对应的环境列表
                    for (var j = 0; j < result[0].environment.length; j++) {
                        environment_tpl = "<option value='"+result[0].environment[j].name+"'>"+result[0].environment[j].title+"</option>";
                        $("#environment").append(environment_tpl);
                    }
                    $(".loading").hide();
                    $("input[name='type']").attr('disabled', false);

                    //默认显示第一个项目下第一个环境下对应的数据库列表
                    for (var z = 0; z < result[0].environment[0].database.length; z++) {
                        dbname_tpl = "<li class='dbinfo' data-dbname='"+result[0].environment[0].database[z]+"'><div class='db-name' ><span class='db-name-title'><i class='iconfont'>&#xe606;</i>"+result[0].environment[0].database[z]+"</span></div></li>";
                        //dbname_tpl = "<option value='"+result[0].environment[0].database[z]+"'>"+result[0].environment[0].database[z]+"</option>";
                        $("#DBName").append(dbname_tpl);
                        load_function();
                    }
                    project_list = res.project_list;
                    environ_list = res.environ_list;

                    right_menu(); // 右键菜单
                }else{
                    alert("暂无项目");
                }
            }
        });
        sqlBoxKeyDown();

        //change_query_number();
    }

    //服务器是否设置限制执行条数
    function change_query_number(){
        var environment = $("#environment option:selected").val();
        $.each( environment_rule_array, function( i, item){
            if ( environment == i ) {
                $("#nums").val( item );
                $("#nums").attr("readonly","readonly");
            }
        } );
        
    }

    
<?php if ( $is_administrator ) { ?>
    $("#sql_export").on("click", function(){
        var export_url= "<?=Url::to(['/index/sharding/sql-export']);?>";

        if( $(this).css('background-color') == 'rgb(243, 243, 243)' ){
            alert('正在运行，请等待执行结果！');return false;
        }

        $(this).html('数据导出中，请稍后');
        $(this).addClass('disabled');

        var index = $("#tabbox .active").index();
        var index1 = index-1;
        var textarea = $("#pagebox .pagebox-list:eq("+index1+") textarea");
        var value = window.editor[textarea.attr('id')].getValue();
        textarea.val(value);

        $.ajax({
            url: export_url,
            type: "post",
            dataType: "json",
            data: $("#pagebox .pagebox-list:eq("+index1+") form").serialize(),
            success: function( $data ){
                //console.log($data.msg);
                $("#pagebox .pagebox-list:eq("+index1+") form #sql_export").html('数据导出');
                $("#pagebox .pagebox-list:eq("+index1+") form #sql_export").removeClass('disabled');
                if($data.code == 1){
                    alert($data.msg);
                }else{
                    layer.msg($data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                }
            },
            error: function(e){
                $("#pagebox .pagebox-list:eq("+index1+") form #sql_export").html('数据导出');
                $("#pagebox .pagebox-list:eq("+index1+") form #sql_export").removeClass('disabled');
                //console.log(e.responseText);
                layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            }
        });
    });
<?php } ?>

    
</script>