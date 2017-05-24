<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Url;

$this->params['breadcrumbs'][] = ['label' => '订正工单日志', 'url' => ['index']];
$this->params['type'] = $this->params['breadcrumbs'][] = "添加";
?>
<?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js') ?>
<div class="common-div">
    <?php $form = ActiveForm::begin(['options' => ['class' => 'sui-form sui-validate']]);?>
    <?= $form->field( $model, 'workorder_no')->textInput(['class' => 'input-xxlarge input-xfat', 'data-rules' => 'required']) ?>
    <?= $form->field( $model, 'workorder_time')->textInput(['onFocus' => 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm",isShowClear:false,readOnly:true,isShowWeek:true})', 'class' => 'input-xxlarge input-xfat']) ?>
    <?= $form->field( $model, 'workorder_user')->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <?= $form->field( $model, 'workorder_title')->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <?= $form->field( $model, 'workorder_reason')->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <?= $form->field( $model, 'workorder_sql_checker')->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <div class="form-group field-log-db">
        <label class="control-label" for="log-workorder_sql_checker">数据库</label>
        <div>
            <table>
                <thead>
                    <tr>
                        <th>服务器IP</th>
                        <th>数据库名</th>
                        <th>脚本数量</th>
                        <th>影响行数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" class="input-medium input-xfat" name="server_ip[]"></td>
                        <td><input type="text" class="input-medium input-xfat" name="db_name[]"></td>
                        <td><input type="text" class="input-medium input-xfat" name="scripts_number[]"></td>
                        <td><input type="text" class="input-medium input-xfat" name="influences_number[]"></td>
                        <td><button type="button" class='add'>+</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="help-block"></div>
    </div>
    <?= $form->field( $model, 'module_name')->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <?= $form->field( $model, 'work_line')->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <?= $form->field( $model, 'workorder_type')->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <?= $form->field( $model, 'workorder_end_time')->textInput(['onFocus' => 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm",isShowClear:false,readOnly:true,isShowWeek:true})', 'class' => 'input-xxlarge input-xfat']) ?>
    <div class="form-group field-log-use_time input-prepend input-append">
        <label class="control-label" for="log-use_time">工单耗时</label>
        <input type="text" id="log-use_time" class="input-xlarge input-fat" name="Log[use_time]" data-rules="digits" value='0'>
        <span class="add-on">分</span>
        <div class="help-block"></div>
    </div>
    <?= $form->field( $model, 'source')->hiddenInput(['value' => 'create']) ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
<script type="text/javascript">
    $(".add").on("click", function(){
        var tpl = "<tr>"+$("tbody tr:eq(0)").html()+"</tr>";
        tpl_ = tpl.replace("add", "del").replace("+", "-");
        $("tbody").append(tpl_);
        $("tbody .del").off('click');
        $("tbody .del").on("click", function(){
            $(this).parent().parent().remove();
        });
    });
</script>