<?php 
use yii\helpers\Html;
use yii\widgets\ActiveForm;
//use dosamigos\datepicker\DatePicker;
//use kartik\date\DatePicker; 
 ?>
 <?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js') ?>
 
 <div>
    <?php $form = ActiveForm::begin(['options' => ['class' => 'sui-form']]); ?>
    <?= $form->field( $model, 'username' )->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    <?= $form->field( $model, 'number' )->textInput(['class' => 'input-xxlarge input-xfat']) ?>

    <?= $form->field( $model, 'stop_date' )->textInput(['onFocus' => 'WdatePicker({dateFmt:"yyyy-MM-dd",isShowClear:false,readOnly:true,isShowWeek:true})', 'class' => 'input-xxlarge input-xfat Wdate uneditable-input']) ?>

    <?= $form->field( $model, 'db_name' )->textInput(['class' => 'input-xxlarge input-xfat']) ?>
    
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>