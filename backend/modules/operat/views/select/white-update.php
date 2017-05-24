<?php 
use yii\helpers\Html;

$this->title="修改白名单";
$this->params['breadcrumbs'][] = ['label' => 'White', 'url' => ['white']];
$this->params['type'] = $this->params['breadcrumbs'][] = $model->username;
?>
<div class="white-update common-div">
    <?= $this->render('_form', ['model' => $model]); ?>
</div>