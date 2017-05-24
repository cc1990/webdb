<?php
use yii\helpers\Url;
use yii\helpers\Html;
/* @var $this yii\web\View */

$this->params['breadcrumbs'] = '';
?>
<div class="site-index div-index">
    <div class="jumbotron">
        <h3 align="left">请选择查询内容</h3>
        <form class="sui-form sui-row-fluid form-horizontal searchForm" id="searchForm">
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">项目名称：</label>
                    <div class="controls">
                        <select id="Project" name="Project">
                            <option value='0' >---全部项目---</option>
                            <?php foreach ($project_list as $v):?>
                                <option value='<?=$v['pro_id']?>' ><?=$v['name']?></option>
                            <?php endforeach;?>
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
                    <label class="control-label" for="input001">开始时间：</label>
                    <div class="controls">
                        <input class="Wdate" type="text" onClick="WdatePicker()" style="height:22px;width:178px;line-height: 22px" name='start_time' id="startTime">
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">数据库名：</label>
                    <div class="controls">
                        <select  name="database" id="database">
                            <?php foreach ($user_dbs as $v):?>
                            <option value='<?=$v?>' >数据库<?=$v?></option>
                            <?php endforeach;?>
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
        </form>
        

        <p>
            <a class="sui-btn btn-xlarge" id="select">查询脚本</a>
            <!-- <input type="submit" name="submit" value="导出脚本" class="sui-btn btn-xlarge btn-info"> -->
            <a class="sui-btn btn-xlarge btn-info" id="export" target="_blank">导出脚本</a>
        </p>
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
    });

    $("#select").on("click", function () {
        var url = "<?=Url::to(['get-script-list'])?>";
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
                        tpl += "</tr>";
                        i++;
                    });
                    
                    $("#sqllist").html(tpl);
                }
            },
            errot: function(e){
                layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            }
        });
    });


    $("#server_id").on("change", function () {
        change_query_number( $(this) );
    });

    var db_name_array = '<?php echo $db_name_array; ?>';
    var db_name_array_eval = eval('('+db_name_array+')');

    function change_query_number( o ){
        var server_id = o.children('option:selected').attr("server_id");
        var tpl = "<option value='0'>---全部数据库---</option>";
        
        $.each(db_name_array_eval, function( id, item ) {
            if( server_id == id ){
                for (var i = 0; i < item.length; i++) {
                    tpl += "<option value='"+item[i]+"' >"+item[i]+"</option>";
                }
            }
        });
        $("#database").html(tpl);
    }

    $("#export").on("click", function () {
        var url = "<?=Url::to(['export'])?>";
        $("#searchForm").attr("action", url);
        $("#searchForm").attr("target", "_blank");
        $("#searchForm").attr("method", 'post');
        $("#searchForm").submit();
    });
</script>
