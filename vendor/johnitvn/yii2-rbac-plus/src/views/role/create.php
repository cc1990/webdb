<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model johnitvn\rbacplus\models\AuthItem */

$this->title = 'Create Role';
$this->params['breadcrumbs'][] = ['label' => 'Role', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Create Role';
?>
<div class="auth-item-create common-div">
    <h1>Create Role</h1>
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
