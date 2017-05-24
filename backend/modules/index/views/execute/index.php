<?php
use yii\helpers\Url;
use yii\helpers\Html;
/* @var $this yii\web\View */

$this->params['breadcrumbs'] = '';

?>

<?=Html::cssFile("@web/public/plug/select2/css/select2.css")?>
<?=Html::jsFile("@web/public/plug/select2/js/select2.js")?>
<style type="text/css">
    .select2-container--default .select2-selection--multiple{height: 32px; overflow: hidden;}
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: rgba(255,255,255,0.15);
    border: 0px solid #aaa;
    cursor: default;
    float: left;
    margin-right: 0px;
    margin-top: 5px;
    padding: 0 2px;}
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
        display: none; 
    }
    .search-button{width: 350px; height: 42px;}
    .to_form{width: 720px; display: none;}
    .lf{float: left;}
    .li select{width: 188px;}
    .ml{margin-left: 20px;}
    label{margin-right: 20px;}
</style>


<div class="site-index div-index">
    <div class="jumbotron">
        <h3 align="left">请选择查询内容</h3>
        <form class="sui-form sui-row-fluid form-horizontal searchForm" id="searchForm">
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">项目环境：</label>
                    <div class="controls">
                        <select  name="server_id" id="server_id" >
                            <?php foreach ($server_list as $v):?>
                                <?php if (in_array($v['server_id'],$server_ids)): ?>
                                    <?php $is_have = 1; ?>
                                    <option server_id= "<?=$v['server_id']?>"  value="<?=$v['server_id']?>" ><?=$v['ip']?> - <?=$v['name']?></option>';
                                <?php endif; ?>
                            <?php endforeach;?>
                            <?php if (empty($is_have)): ?>
                                <option value='0' >未指定</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="input001">查询类型：</label>
                    <div class="controls">
                        <select  name="action" id="action">
                            <option value='1'>DML和DDL脚本</option>
                            <option value='2' >DML脚本</option>
                            <option value='3' >DDL脚本</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">项目名称：</label>
                    <div class="controls">
                        <select id="Project" name="Project">
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="input001">开始时间：</label>
                    <div class="controls">
                        <input class="Wdate" type="text" onClick="WdatePicker()" style="height:22px;width:178px;line-height: 22px" name='start_time' id="startTime">
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">数据库名：</label>
                    <div class="controls" >
                        <select id="database" name="database[]" style="width: 300px;" multiple="multiple" class="selectpicker"  data-live-search="true" data-live-search-placeholder="Search" data-actions-box="true">
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="input001">结束时间：</label>
                    <div class="controls">
                        <input class="Wdate" type="text" onClick="WdatePicker()" style="height:22px;width:178px;line-height: 22px" name='end_time' id="endTime">
                    </div>
                </div>
            </div>
            <input type="hidden" name="type" value="1">
        </form>
        

        <div class="search-button lf">
            <a class="sui-btn btn-xlarge" id="select">正式脚本</a>
            <a class="sui-btn btn-xlarge" id="history">历史脚本</a>
            <?php if ( !empty( $project_list ) ): ?>
            <a class="sui-btn btn-xlarge" id="allscript">执行脚本</a>
            <?php endif ?>
        </div>
        <div class="to_form lf">
        <?php if ( !empty( $project_list ) ): ?>
            <form class="sui-form sui-row-fluid form-horizontal" id="to_form" >
                <input type="hidden" name="from_server_id" value="">
                <input type="hidden" name="from_project" value="">
                <input type="hidden" name="from_database" value="">
                <div class="li lf">
                    <div class="control-group">
                        <label class="control-label" for="input001">目标环境：</label>
                        <div class="controls">
                            <select  name="to_server_id"  >
                                <?php foreach ($to_server_list as $v):?>
                                <option server_id= "<?=$v['server_id']?>"  value="<?=$v['server_id']?>" ><?=$v['ip']?> - <?=$v['name']?></option>';
                                <?php endforeach;?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="li lf">
                    <div class="control-group">
                        <label class="control-label" for="input001">目标项目：</label>
                        <div class="controls">
                            <select name="to_project">
                            <?php foreach ($project_list as $key => $value): ?>
                                <option value="<?=$value['pro_id']?>"><?=$value['name']?></option>
                            <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="lf ml"><a class="sui-btn btn-xlarge btn-danger" id="execute">一键执行</a></div>
            </form>
        <?php endif ?>
        </div>
    </div>
    <div class="body-content">
        <div >
            <table class="sui-table table-bordered table-zebra">
                <thead>
                    <tr>
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
    </div>
</div>
<?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js')?>
<script type="text/javascript">
    
    $(document).ready(function() {
        change_query_number( $("#server_id") );

        $(".searchForm select").on("change", function(){
            $(".sui-btn").removeClass('btn-success');
            $("#sqllist").html("");
        });
        $(".searchForm input[type='checkbox']").on("click", function(){
            $(".sui-btn").removeClass('btn-success');
            $("#sqllist").html("");
        });
    });

    $("#select").on("click", function () {
        $(".sui-btn").removeClass('btn-success');
        $(this).addClass('btn-success');
        var url = "<?=Url::to(['get-script-list'])?>";
        $("input[name='type']").val(1);
        getScripts( url, 1 );
        $(".to_form").hide();
    });

    $("#history").on("click", function () {
        $(".sui-btn").removeClass('btn-success');
        $(this).addClass('btn-success');
        var url = "<?=Url::to(['get-script-list'])?>";
        $("input[name='type']").val(2);
        getScripts( url, 2 );
        $(".to_form").hide();
    });

    $("#allscript").on("click", function () {
        $(".sui-btn").removeClass('btn-success');
        $(this).addClass('btn-success');
        var url = "<?=Url::to(['get-script-list'])?>";
        $("input[name='type']").val(3);
        getScripts( url, 3 );
        $(".to_form").show();
    });

    $("#execute").on('click', function(){
        if ( $("#sqllist").html().length > 0 ) {
            var url = "<?=Url::to(['execute'])?>"
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                data: $("#to_form").serialize(),
                success: function(data){
                    if( data.code == 0 ){
                        layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                    }else if( data.code == 1 ){
                        alert("一键执行成功");
                    }else if( data.code == 2 ){
                        var log_id = data.log_id;
                        $(".sql_"+log_id +" td:last").html("<span style='color:red;'>错误</span>");
                        layer.msg(data.msg, {time: 5000, icon:5, shade: 0.6,shadeClose: true});
                    }
                },
                errot: function(e){
                    layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                }
            });
        } else {
            layer.msg("无正式脚本可执行", {time: 3000, icon:5, shade: 0.6,shadeClose: true});
        }
    });


    $("#server_id").on("change", function () {
        change_query_number( $(this) );
    });

    var db_name_array = '<?php echo $db_name_array; ?>';
    var db_name_array_eval = eval('('+db_name_array+')');

    function change_query_number( o ){
        var server_id = o.children('option:selected').attr("server_id");

        getProjects( server_id );
        
        var tpl = "";
        
        $.each(db_name_array_eval, function( id, item ) {
            if( server_id == id ){
                for (var i = 0; i < item.length; i++) {
                    //tpl += "<label><input type='checkbox' name='database[]' value='"+item[i]+"'>"+item[i]+"</label>";
                    tpl += "<option value='"+item[i]+"' >"+item[i]+"</option>";
                }
            }
        });
        $("#database").html(tpl);
        $('#database').select2({
            minimumResultsForSearch: Infinity
        });

        $(".searchForm input[type='checkbox']").off("click");
        $(".searchForm input[type='checkbox']").on("click", function(){
            $(".sui-btn").removeClass('btn-success');
            $("#sqllist").html("");
            $(".to_form").hide();
        });

    }

    function getProjects( server_id )
    {
        var url = "<?=Url::to(['get-projects?server_id='])?>"+server_id;
        $.getJSON( url, function(data){
            if( data.code == 0 ){
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            }else{
                var tpl = "";
                $.each( data.content, function( i, item ){
                    tpl += "<option value="+item.pro_id+">"+item.name+"</option>";
                } );
                $("#Project").html(tpl);
            }
        } );
    }

    function getScripts( url, type ){
        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: $("#searchForm").serialize(),
            success: function(data){
                if( data.code == 0 ){
                    layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                }else{
                    var tpl = "";var i = 0;

                    $.each(data.content, function(k, item){
                        tpl += "<tr data-key="+item.log_id+" class='sql_"+item.log_id+"'>";
                        tpl += "<td class='num_"+item.log_id+"'>"+(i+1)+"</td>";
                        tpl += "<td class='database_"+item.log_id+"'>"+item.database+"</td>";
                        tpl += "<td class='sqlname_"+item.log_id+"'>";
                        tpl += item.notes+'</br>'+item.script+';';
                        tpl += "</td>";
                        tpl += "<td>"+item.project_name+"</td>";
                        tpl += "<td>"+item.created_date+"</td>";
                        tpl += "<td>"+item.username+"</td>";
                        if( type == 3 ){
                            tpl += "<td></td>";
                        }else{
                            tpl += "<td><a href='javascript:;' onclick='change("+item.log_id+")' title='变更脚本状态'><i class='iconfont'>&#xe61a;</i></a></td>";
                        }
                        tpl += "</tr>";
                        i++;
                    });
                    
                    $("#sqllist").html(tpl);

                    if( type == 3 ){
                        $("input[name='from_server_id']").val($("#server_id option:selected").val());
                        $("input[name='from_project']").val($("#Project option:selected").val());
                        $("input[name='from_database']").val(data.database);
                    }
                }
            },
            errot: function(e){
                layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            }
        });
    }

    function change( log_id ){
        var url = "<?=Url::to(['change-script-status'])?>?log_id="+log_id;
        if( log_id == '' ){
            layer.msg("日志ID不能为空", {time: 3000, icon:5, shade: 0.6,shadeClose: true});
        }
        $.getJSON( url, function(data){
            if( data.code == 1 ){
                $(".sql_"+log_id).remove();
            }else{
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            }
        } );
    }
</script>
