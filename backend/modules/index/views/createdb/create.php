<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Url;

$this->params['breadcrumbs'][] = ['label' => '建库流程', 'url' => ['index']];
$this->params['type'] = $this->params['breadcrumbs'][] = "添加库";
?>
<?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js') ?>
<div class="common-div">
    <?php $form = ActiveForm::begin(['options' => ['class' => 'sui-form sui-validate']]);?>
    <?= $form->field( $model, 'db_name')->textInput(['class' => 'input-xxlarge input-xfat', 'data-rules' => 'required']) ?>
    <div class="form-group field-log-db">
        <label class="control-label" for="log-workorder_sql_checker">服务器</label>
        <div>
            <table>
                <!-- <thead>
                    <tr>
                        <th></th>
                        <th>服务器IP</th>
                    </tr>
                </thead> -->
                <tbody>
                <?php foreach ($server_list as $key => $value): ?>
                    <tr>
                        <td><input type="checkbox" name="server_id[]" value="<?=$value['server_id'] ?>"></td>
                        <td><?=$value['ip'] . "（" . $value['name'] . "）" ?></td>
                    </tr>
                <?php endforeach ?>
                    
                </tbody>
            </table>
        </div>

        <div class="help-block"></div>
    </div>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>