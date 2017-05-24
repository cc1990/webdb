<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model johnitvn\rbacplus\models\AuthItem */

$this->title = 'Update Role';
$this->params['breadcrumbs'][] = ['label' => 'Role', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="auth-item-update common-div">
    <h1>Update Role：<?=$model->name ?></h1>
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
