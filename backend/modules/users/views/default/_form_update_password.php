<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\modules\users\models\Users */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="users-form">

    <?php $form = ActiveForm::begin(['options' => ['class' => 'sui-form']]); ?>


    <?= $form->field($model, 'old_password')->passwordInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>

    <?= $form->field($model, 'password',['inputOptions'=>['value'=>'','class'=>'form-control']])->passwordInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>

    <?= $form->field($model, 'new_password')->passwordInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>


    <div class="form-group">
        <?= Html::submitButton('保存', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
