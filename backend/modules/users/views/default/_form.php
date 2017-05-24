<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\modules\users\models\Users */
/* @var $form yii\widgets\ActiveForm */
//var_dump($this->params);exit;
?>

<div class="users-form">

    <?php $form = ActiveForm::begin(['options' => ['class' => 'sui-form']]); ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>

    <?php if($this->params['type'] == 'create'){ ?>
    <?= $form->field($model, 'password',['inputOptions'=>['value'=>'','class'=>'form-control']])->passwordInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>
    <?php } ?>
    <?= $form->field($model, 'chinesename')->textInput(['class' => 'input-xxlarge input-xfat']) ?>

    <?= $form->field($model, 'authority')->textInput(['class' => 'input-xxlarge input-xfat']) ?>



    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
