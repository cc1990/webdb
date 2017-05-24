<?php 
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$environment_list = array(
    'dev' => '开发',
    'dev_trunk' => '开发主干',
    'test' => '测试',
    'test_trunk' => '测试主干',
    'pre' => '预发',
    'pro' => '线上',
);
$sql_operations = array('DML','DDL','DQL');
 ?>
 <?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js') ?>
 
 <div>

    <?php $form = ActiveForm::begin(['method' => 'post', 'options' => ['class' => 'sui-form']]); ?>
    <?= $form->field( $model, 'username' )->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <div class="form-group field-authorize-type">
        <label class="control-label" for="authorize-type">执行环境</label>
        <div id="authorize-type"><label><input type="radio" name="Authorize[type]" value="common" <?php if( $model->type != 'sharding' ){ echo 'checked'; }?> > 通用库</label>
        <label><input type="radio" name="Authorize[type]" value="sharding" <?php if( $model->type == 'sharding' ){ echo 'checked'; }?> > 分库分表</label></div>

        <div class="help-block"></div>
    </div>

    
    <div class="form-group field-authorizesearch-server_id" id="server" <?php if ( $model->type == 'sharding' ){ echo "style='display:none;'"; } ?> >
        <label class="control-label" for="authorizesearch-server_id">服务器选择</label>
        <select id="authorizesearch-server_id" class="form-control" name="Authorize[server_id]" style="width:500px">
        <?php foreach ($server_list as $key => $value) { ?>
            <option value="<?=$value['server_id']?>" <?php if( $model->server_id == $value['server_id'] ){echo 'selected';} ?> ><?=$value['ip']?> - <?=$value['name']?></option>
         <?php } ?>
        </select>

        <div class="help-block"></div>
    </div>
    <div class="form-group field-authorizesearch-environment" id='environment' <?php if ( $model->type != 'sharding' ){ echo "style='display:none;'"; } ?>>
        <label class="control-label" for="authorizesearch-environment">分库分表环境</label>
        <select id="authorizesearch-environment" class="form-control" name="Authorize[environment]" style="width:500px">
            <?php foreach ($environment_list as $k => $val) { ?>
            <option value="<?=$k?>" <?php if( $model->environment == $k ){echo 'selected';} ?> ><?=$val ?></option>
            <?php } ?>
        </select>

        <div class="help-block"></div>
    </div>

    <?= $form->field( $model, 'stop_time' )->textInput(['onFocus' => 'WdatePicker({dateFmt:"yyyy-MM-dd HH",isShowClear:true,readOnly:true,isShowWeek:true})', 'class' => 'input-xxlarge input-xfat Wdate uneditable-input']) ?>

    <?= $form->field( $model, 'db_name' )->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <div class="form-group field-authorize-sqloperation required">
        <label class="control-label" for="authorize-sqloperation">SQL操作</label>
        <div id="authorize-sqloperation">
        <?php $sqloperation=$model->sqloperation; ?>
        <?php foreach ($sql_operations as $key => $value) { ?>
            <label><input type="checkbox" name="Authorize[sqloperation][]" <?= (@strpos("$sqloperation",$value) !== false) ? "checked":"" ?> value="<?=$value?>"> <?=$value?></label>
        <?php } ?>
        </div>

        <div class="help-block"></div>
    </div>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
<script type="text/javascript">
    $("input[type='radio']").on("change", function(){
        if( $(this).val() == 'sharding' ){
            $("#server").hide();
            $("#environment").show();
        }else{
            $("#server").show();
            $("#environment").hide();
        }
    });
</script>