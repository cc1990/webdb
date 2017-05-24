<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Url;

/** Get all roles */
$authManager = Yii::$app->authManager;

$work_line = array("车主技术线", "商户技术线", "支撑技术线", "基础设施线");
$this->params['breadcrumbs'][] = "";
?>

<link rel="stylesheet" type="text/css" href="http://g.alicdn.com/sj/dpl/1.5.1/css/sui.min.css">
<style type="text/css">
    b{font-weight: 800;
    text-align: left;
    font-size: 11pt;}
</style>
<div class="user-assignment-form" id="htmlpage" >
<?php $form = ActiveForm::begin(['id' => 'infoform', 'options' => ['class' => 'sui-form', ]]); ?>
    <input type="hidden" name="log_id" value="<?=$log_id?>">
    <table class="sui-table table-bordered table-zebra">
        <thead>
            <tr>
                <th>服务器IP</th>
                <th>数据库名</th>
                <th>脚本数量</th>
                <th>影响行数</th>
                <th width="100px"><button type="button" class='add'>+</button></th>
            </tr>
        <tbody id="assignment" class="list">            
            <?php foreach ($data as $key => $value) { ?>
            <tr>
                <td><input type="text" class="input" name="server_ip[]" value="<?= $value['server_ip'] ?>" style="height: 25px;"></td>
                <td><input type="text" class="input" name="db_name[]" value="<?= $value['db_name'] ?>" style="height: 25px;"></td>
                <td><input type="text" class="input" name="scripts_number[]" value="<?= $value['scripts_number'] ?>" style="height: 25px;"></td>
                <td><input type="text" class="input" name="influences_number[]" value="<?= $value['influences_number'] ?>" style="height: 25px;"></td>
                <td><button type="button" class='del'> - </button></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="form-group field-module_name">
        <b>模块名称：</b>
        <input type="text" id="projectsinfo-work_line" class="input-xxlarge input-xfat" name="module_name" value="<?= $log_data['module_name'] ?>" style="height: 28px;">

        <div class="help-block"></div>
    </div>
    <div class="form-group field-work_line">
        <b>业务线名：</b>
        <select name='work_line' style="width: 480px;">
            <?php foreach ($work_line as $key => $value) { ?>
                <option value="<?=$value?>" <?php if ( $value == $log_data['work_line'] ) { echo 'selected'; } ?>><?=$value?></option>
            <?php } ?>
        </select>
        

        <div class="help-block"></div>
    </div>
    <div class="form-group field-work_line input-prepend input-append">
        <b>工单耗时：</b>
        <input type="text" id="log-use_time" class="input-xlarge input-fat" name="use_time" data-rules="digits" value="<?= $log_data['use_time'] ?>" style="height: 28px;">
        <span class="add-on">分</span>
        <div class="help-block"></div>
    </div>
    <div class="form-group field-work_line">
        <textarea cols="128" rows="5" name='remark'><?= $log_data['remark'] ?></textarea>
        <div class="help-block"></div>
    </div>
    <?php if (!Yii::$app->request->isAjax) { ?>
    <div class="form-group">
    <?= Html::Button(Yii::t('rbac', 'Update'), ['class' => 'sui-btn btn-xlarge btn-info', 'id' => 'submit']) ?>
    </div>
    <?php } ?>
<?php ActiveForm::end(); ?>

    <table style="display: none;">
        <tbody class="tpl">
            <tr>
                <td><input type="text" class="input" name="server_ip[]" value="" style="height: 25px;"></td>
                <td><input type="text" class="input" name="db_name[]" value="" style="height: 25px;"></td>
                <td><input type="text" class="input" name="scripts_number[]" value="" style="height: 25px;"></td>
                <td><input type="text" class="input" name="influences_number[]" value="" style="height: 25px;"></td>
                <td><button type="button" class='del'> - </button></td>
            </tr>
            
        </tbody>
    </table>
<?=Html::jsFile('@web/public/js/jquery.min.js') ?>
<?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
<script type="text/javascript">
    $("#submit").on('click', function(){
        var index = parent.layer.getFrameIndex(window.name); 
        $.ajax({
            url: $("#infoform").attr("action"),
            type: 'post',
            dataType: 'json',
            data: $("#infoform").serialize(),
            success: function(data){
                if( data.code == 0 ){
                    layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                }else{
                    parent.location.reload(); 
                    parent.layer.msg('保存成功', {time: 2000, icon:6});
                    parent.layer.close(index);
                }
            },
            error: function(e){
                parent.layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                parent.layer.close(index);
            }
        })
    });

    $(".add").on("click", function(){
        var tpl = $(".tpl").html();
        $(".list").append(tpl);
        $(".list .del").off('click');
        $(".list .del").on("click", function(){
            $(this).parent().parent().remove();
        });
    });
    $(".del").on("click", function(){
        $(this).parent().parent().remove();
    });
</script>
</div>