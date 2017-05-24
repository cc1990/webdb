<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model johnitvn\rbacplus\models\AuthItem */
$this->params['breadcrumbs'][] = ['label' => 'Permisstions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'name' => $model->name]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="auth-item-update common-div">
    <h1>Update Role：<?=$model->name ?></h1>
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
