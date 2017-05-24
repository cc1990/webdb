<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\modules\projects\models\Projects */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="projects-form">

    <?php $form = ActiveForm::begin(['options' => ['class' => 'sui-form']]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true,'disabled' => 'disabled', 'class' => 'input-xxlarge input-xfat uneditable-input']) ?>

    <?= $form->field($model, 'updated_date')->textInput(['disabled' => 'disabled', 'class' => 'input-xxlarge input-xfat uneditable-input']) ?>

    <?= $form->field($model, 'status')->textInput(['disabled' => 'disabled', 'class' => 'input-xxlarge input-xfat uneditable-input']) ?>

    <?= $form->field($model, 'remarks')->textInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
