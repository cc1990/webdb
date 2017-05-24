<?php
use yii\helpers\Url;
use yii\helpers\Html;
/* @var $this yii\web\View */

$this->title = 'sql操作平台';
$this->params['breadcrumbs'] = '';
?>
<div class="site-index div-index">
    <div class="jumbotron">
        <h3 align="left">请选择导出内容</h3>
        <form class="sui-form sui-row-fluid form-horizontal searchForm">
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">项目环境：</label>
                    <div class="controls">
                        <select  name="server_id" id="server_id" >
                            <?php foreach ($server_list as $v):?>
                                <?php if (in_array($v['server_id'],$server_ids)): ?>
                                    <?php $is_have = 1; ?>
                                    <option server_id= "<?=$v['server_id']?>"  value="<?=$v['server_id']?>" ><?=$v['name']?>- <?=$v['ip']?></option>';
                                <?php endif; ?>
                            <?php endforeach;?>
                            <?php if (empty($is_have)): ?>
                                <option value='0' >未指定</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="input001">导出类型：</label>
                    <div class="controls">
                        <select  name="action" id="action">
                            <option value='1' >查询脚本</option>
                            <option value='2' >DML脚本</option>
                            <option value='3' >DDL脚本</option>
                        </select>
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
                    <label class="control-label" for="input001">开始时间：</label>
                    <div class="controls">
                        <input class="Wdate" type="text" onClick="WdatePicker()" style="height:22px;width:178px;line-height: 22px" id="startTime">
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="input001">项目名称：</label>
                    <div class="controls">
                        <select id="Project" name="Project">
                            <?php if (empty($project_list)): ?>
                                <option value='0' >未指定</option>
                            <?php else: ?>
                                <?php foreach ($project_list as $v):?>
                                    <option value='<?=$v['pro_id']?>' ><?=$v['name']?></option>
                                <?php endforeach;?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="input001">结束时间：</label>
                    <div class="controls">
                        <input class="Wdate" type="text" onClick="WdatePicker()" style="height:22px;width:178px;line-height: 22px" id="endTime">
                    </div>
                </div>
            </div>
        </form>
        

        <p>
            <a class="sui-btn btn-xlarge" id="export">导出</a>
            <a class="sui-btn btn-xlarge btn-info" id="audit">提交审核</a>
            <a class="sui-btn btn-xlarge btn-primary" id="auditlist">审核</a>
        </p>
    </div>
    <div class="body-content">

    </div>
</div>
<?=Html::jsFile('@web/static/plug/My97DatePicker/WdatePicker.js')?>
<script type="text/javascript">

    $(document).ready(function() {
        change_query_number( $("#server_id") );
    });

    $("#export").on("click", function () {
        var do_export= "<?=Url::to(['/index/default/export']);?>";
        var server_id = $("#server_id option:selected").val();
        var database = $("#database option:selected").val();
        var action = $("#action option:selected").val();
        var pro_id = $("#Project option:selected").val();
        var startTime = $("#startTime").val();
        var endTime = $("#endTime").val();
        window.open(do_export+'?action='+action+'&server_id='+server_id+'&database='+database+'&startTime='+startTime+'&endTime='+endTime+'&pro_id='+pro_id);
    });

    $("#audit").on("click", function () {
        var audit_url= "<?=Url::to(['/audits/default/audit']);?>";
        var server_id = $("#server_id option:selected").val();
        var database = $("#database option:selected").val();
        var pro_id = $("#Project option:selected").val();
        var startTime = $("#startTime").val();
        var endTime = $("#endTime").val();
        window.open(audit_url+'?server_id='+server_id+'&database='+database+'&startTime='+startTime+'&endTime='+endTime+'&pro_id='+pro_id);
    });

    $("#auditlist").on("click", function () {
        var audit_url= "<?=Url::to(['/audits/default/auditlist']);?>";
        var server_id = $("#server_id option:selected").val();
        var database = $("#database option:selected").val();
        var pro_id = $("#Project option:selected").val();
        window.open(audit_url+'?server_id='+server_id+'&database='+database+'&pro_id='+pro_id);
    });

    $("#server_id").on("change", function () {
        change_query_number( $(this) );
    });

    var db_name_array = '<?php echo $db_name_array; ?>';
    var db_name_array_eval = eval('('+db_name_array+')');

    function change_query_number( o ){
        var server_id = o.children('option:selected').attr("server_id");
        
        $.each(db_name_array_eval, function( id, item ) {
            if( server_id == id ){
                var tpl = "";
                for (var i = 0; i < item.length; i++) {
                    tpl += "<option value='"+item[i]+"' >"+item[i]+"</option>";
                }
                $("#database").html(tpl);
            }
        });
    }
</script>
