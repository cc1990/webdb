<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$rules = Yii::$app->authManager->getRules();
$rulesNames = array_keys($rules);
$rulesDatas = array_merge([''=>Yii::t('rbac','(not use)')],array_combine($rulesNames,$rulesNames));        
         
?>

<div class="auth-item-form">

    <?php $form = ActiveForm::begin(['options'=> ['class' => 'sui-form']]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 1, 'class' => 'input-xxlarge input-xfat']) ?>

    <?= $form->field($model, 'ruleName')->dropDownList($rulesDatas, ['class' => 'select-xfat']) ?>

    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('rbac', 'Save'), ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
