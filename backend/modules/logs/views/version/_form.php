<?php 
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<?=Html::jsFile('@web/public/plug/ueditor/ueditor.config.js') ?>
<?=Html::jsFile('@web/public/plug/ueditor/ueditor.all.min.js') ?>
<?=Html::jsFile('@web/public/plug/ueditor/lang/zh-cn/zh-cn.js') ?>
<div>
    <?php $form = ActiveForm::begin(['options' => ['class' => 'sui-form']]); ?>
        <div class="form-group field-selectwhite-username required">
            <label class="control-label" for="selectversion_title">标题</label>
            <input type="text" id="selectversion_title" class="input-xxlarge input-xfat" name="VersionSearch[version_title]" value="<?=$model->version_title;?>">

            <div class="help-block"></div>
        </div>
        <div class="form-group field-selectwhite-username required">
            <label class="control-label" for="selectversion_number">版本号</label>
            <input type="text" id="selectversion_number" class="input-xxlarge input-xfat" name="VersionSearch[version_number]" value="<?=$model->version_number;?>">

            <div class="help-block"></div>
        </div>
        <div class="form-group field-selectwhite-username required">
            <label class="control-label" for="selectversion_log">日志内容</label>
            <script id="VersionSearch[version_log]" type="text/plain" name="VersionSearch[version_log]" style="width:800px;height:300px;"><?=$model->version_log;?></script>
            <div class="help-block"></div>
        </div>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
        </div>

    <?php ActiveForm::end(); ?>
</div>
<script type="text/javascript">
    var ue = UE.getEditor('VersionSearch[version_log]');
</script>