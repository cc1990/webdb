<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model johnitvn\rbacplus\models\AuthItem */
$this->title = 'Create Permisstions';
$this->params['breadcrumbs'][] = ['label' => 'Permisstions', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Create Permisstions';
?>
<div class="auth-item-create common-div">
    <h1>Create Permisstions</h1>
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
