<?php 

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '批量执行SQL';
$this->params['breadcrumbs'] = '';
?>
<style type="text/css">
    .c_left{
        text-align: left;
    }
    .sql_content, .database_name{
        display: none;
    }
    .glyphicon-ok{
        color: #73ef73;
    }

</style>
<div class="site-index div-index">
    <h2 align="left">数据迁移</h2>
    <form id="formExecuteSql" name="formExecuteSql" enctype="multipart/form-data" class="sui-form sui-row-fluid form-horizontal">
    <div class="jumbotron">
        <div class="searchForm">
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">来源服务器：</label>
                    <div class="controls">
                        <select id="FromDBHost" name="FromDBHost" >
                            <?php foreach ($server_list as $v):?>
                                <?php if (in_array($v['server_id'],$server_ids)): ?>
                                    <?php $is_have = 1; ?>
                                    <option server_id= "<?=$v['server_id']?>"  value="<?=$v['ip']?>" data-environment="<?=$v['environment'] ?>"><?=$v['name']?>- <?=$v['ip']?></option>';
                                <?php endif; ?>
                            <?php endforeach;?>
                            <?php if (empty($is_have)): ?>
                                <option value='0' >未指定</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">目标服务器：</label>
                    <div class="controls">
                        <select id="DBHost" name="DBHost" >
                            <?php foreach ($server_list as $v):?>
                                <?php if (in_array($v['server_id'],$server_ids)): ?>
                                    <?php $is_have = 1; ?>
                                    <option server_id= "<?=$v['server_id']?>"  value="<?=$v['ip']?>" ><?=$v['name']?>- <?=$v['ip']?></option>';
                                <?php endif; ?>
                            <?php endforeach;?>
                            <?php if (empty($is_have)): ?>
                                <option value='0' >未指定</option>
                            <?php endif; ?>
                        </select>   
                    </div>
                </div>
            </div>
            <div class="span12" style="min-width: 1100px;margin-bottom: 20px; float: left;">
                <?php if (!empty($project_list)): ?>
                    <?php foreach ($project_list as $v):?>
                        <label style="width: 200px;display: inline-block;" class="projectname" data-environment="<?=$v['status']?>"><input type="checkbox" name="Project[]" value="<?=$v['pro_id']?>"><?=$v['name']?></label>
                    <?php endforeach;?>
                <?php endif; ?>
            </div>
        </div>
        <div >
            <table class="sui-table table-bordered table-zebra">
                <thead>
                    <tr>
                        <th width="30"><input class="check_all" type="checkbox" name="" checked ></th>
                        <th width="30">#</th>
                        <th width="150">数据库</th>
                        <th>SQL语句</th>
                        <th width="150">项目名称</th>
                        <th width="100">执行时间</th>
                        <th width="70">执行人</th>
                        <th width="70">操作</th>
                    </tr>
                </thead>
                <tbody id="sqllist">
                    
                </tbody>
            </table> 
        </div>

        <p><a class="sui-btn btn-xlarge btn-success" id="execute_sql">执行sql</a></p>
    </div>
    </form>
    <div id="easyui-layout" style="text-align: left"></div>
</div>


<script type="text/javascript">
    $(document).ready(function(){
        dbNameHtml();
        getSqlList();
        getProject();
    });

    $(".check_all").bind("click", function () {
        $("[name = 'log_id[]']:checkbox").prop("checked", this.checked);
    });

    $("#FromDBHost").on('change', function(){
        getSqlList();
        getProject();
    });

    $("#DBName").on('change', function(){
        getSqlList();
    });

    $("input[name='Project[]']").on('change', function(){
        getSqlList();
    });

    /*
    选择当前用户所授权的数据库名
     */
    function dbNameHtml(){
        var db_name_array = '<?php echo $db_name_array; ?>';
        var db_name_array_eval = eval('('+db_name_array+')');

        $.each(db_name_array_eval, function( id, item ) {
                var tpl = "";
                for (var i = 0; i < item.length; i++) {
                    tpl += "<option value='"+item[i]+"' >"+item[i]+"</option>";
                }
                $("#DBName").html(tpl);
        });
    }

    /*
    根据来源服务器、项目名 获取SQL日志
     */
    function getSqlList(){
        var server_id = $("#FromDBHost option:selected").attr("server_id");
/*        var dbname = $("#DBName option:selected").val();*/
        var project_id = '';
        $("input[name='Project[]']:checked").each(function(){
            project_id += $(this).val()+",";
        });
        project_id = project_id.substring(0, project_id.length-1);
        var url = "<?= Url::to(['get-sql-list']) ?>?server_id="+server_id+"&project_id="+project_id;
        var tpl = "";
        $.getJSON( url, function( data ){
            if (data != '') {
                for (var i = 0; i < data.length; i++) {
                    tpl += "<tr data-key="+data[i].log_id+" class='sql_"+data[i].log_id+"'>";
                    tpl += "<td><input type='checkbox' name='log_id[]' value='"+data[i].log_id+"' checked ></td>";
                    tpl += "<td class='num_"+data[i].log_id+"'>"+(i+1)+"</td>";
                    tpl += "<td class='database_"+data[i].log_id+"'><div class='database c_left'>"+data[i].database+"</div><div class='database_name c_left'><input type='text' name='database[]' value='"+data[i].database+"' ></div></td>";
                    tpl += "<td class='sqlname_"+data[i].log_id+"'><div class='sqlname c_left'>";
                    tpl += data[i].notes+'</br>'+data[i].script+';';
                    tpl += "</div><div class='sql_content c_left'><textarea cols='100' name='sql_content[]'>"+data[i].notes+'\r\n'+data[i].script+";</textarea></div></td>";
                    tpl += "<td>"+data[i].project_name+"</td>";
                    tpl += "<td>"+data[i].created_date+"</td>";
                    tpl += "<td>"+data[i].username+"</td>";
                    /*tpl += "<td><a href='#' class='update_sql' title='修改' ><span class='glyphicon glyphicon-pencil'></span></a>&nbsp;<a href='javascript:;' class='delete_sql' title='删除' ><span class='glyphicon glyphicon-trash'></span></a>&nbsp;<a href='javascript:;' class='execute_sql' title='执行' ><span class='glyphicon glyphicon-repeat'></span></a></td>";*/
                    tpl += "<td class='action'><a href='javascript:;' class='update_sql' title='修改' ><i class='iconfont'>&#xe629;</i></a>&nbsp;<a href='javascript:;' class='delete_sql' title='删除' ><i class='iconfont'>&#xe61a;</i></a></td>";
                    tpl += "</tr>";
                }
                $("#sqllist").html(tpl);
                $("table").delegate("a[class='update_sql']", 'click', function(){
                    var key = $(this).parent().parent().attr('data-key');
                    $(".database_"+key).children(".database").hide();
                    $(".sqlname_"+key).children(".sqlname").hide();
                    $(".database_"+key).children(".database_name").show();
                    $(".sqlname_"+key).children(".sql_content").show();
                    $(".database_"+key).children(".database_name").children().focus();

                    $(".database_"+key).children(".database_name").children().blur(function(){
                        var database_name = $(this).val();
                        $(".database_"+key).children(".database_name").hide();
                        $(".database_"+key).children(".database").html(database_name);
                        $(".database_"+key).children(".database").show();
                    });

                    $(".sqlname_"+key).children(".sql_content").children().blur(function(){
                        var sql_content = $(this).val().replace("\r\n", "</br>");
                        sql_content = sql_content.replace("\n", "</br>");
                        
                        $(".sqlname_"+key).children(".sql_content").hide();
                        $(".sqlname_"+key).children(".sqlname").html(sql_content);
                        $(".sqlname_"+key).children(".sqlname").show();
                    });
                });

                $("table").delegate("a[class='delete_sql']", 'click', function(){
                    $(this).parent().parent().remove();
                });

                $("table").delegate("a[class='execute_sql']", 'click', function(){
                    execute(this);
                });
            } else {
                $("#sqllist").html("");
            }
        } );

        $("#easyui-layout").html("");
    }

    function getProject()
    {
        var environment = $("#FromDBHost option:selected").attr("data-environment");
        $("input[name='Project[]']").attr('checked', false);
        $("#sqllist").html("");

        if ( environment != '' ) {
            $(".projectname").each(function(){
                var pro_en = $(this).attr('data-environment');
                if( environment == pro_en ){
                    $(this).show();
                }else{
                    $(this).hide();
                }
            });
        }else{
            $(".projectname").show();
        }
    }

    $("#execute_sql").on('click', function(){
        $(this).html('执行中');
        $(this).addClass('disabled');
        $("#easyui-layout").html("");
        var length = $("#sqllist tr").length, i=0;
        var host = $("#DBHost option:selected").val();
        var project_id = $("#Project option:selected").val();

        $("#sqllist tr").each(function(){
            if ( $(this).children().children("input[type='checkbox']").is(":checked") && !$(this).children().children("input[type='checkbox']").is(":disabled") ) { //只执行勾选的SQL

                i++;
                var key = $(this).attr("data-key");
                execute(key, host, project_id);
            }
            
        });

        setTimeout( 'show()', i*150);
    });


    function show(){
        $("#execute_sql").removeClass('disabled');
        $("#execute_sql").html('执行sql');
    }

    function execute(key, host, project_id){
        var execute_url= "<?=Url::to(['/index/batch/execute']);?>";
        var database = $(".database_"+key).children(".database_name").children().val();
        var sqlinfo = $(".sqlname_"+key).children(".sql_content").children().val().replace('\n', '\r\n');
        var num = $(".num_"+key).html();

        $.ajax({
            url: execute_url,
            type: "POST",
            dataType: "json",
            //async: false,//同步请求，true 异步请求
            data: { DBHost: host, DBName: database, Project: project_id, sqlinfo: sqlinfo },
            success: function( result ){
                if(result.code == 0){
                    //console.log(result.msg);
                    html = "<div style=\"margin:20px 0;\"></div><p>第<font color='red'>"+num+"</font>条执行失败，执行结果------------</br>"+ result.msg + "</p>";
                    $('#easyui-layout').append(html);
                    return false;
                }else{
                    $("input[value="+key+"]").attr('disabled', true);
                    $(".sql_"+key).children(".action").html("<span class='glyphicon glyphicon-ok'></span>");
                    return true;
                }
            },

            error: function(data){
                //console.log(data.responseText);
                html = "<div style=\"margin:20px 0;\"></div><p>第<font color='red'>"+num+"</font>条执行失败，执行结果------------</br>" + data.responseText + "</p>";
                $('#easyui-layout').append(html);
                return false;
            }
        });

    }


</script>