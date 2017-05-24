<?php 
use yii\helpers\Html;

$this->title="修改授权白名单";
$this->params['breadcrumbs'][] = ['label' => 'Authorize', 'url' => ['index']];
$this->params['type'] = $this->params['breadcrumbs'][] = $this->title;
?>
<div class="white-update common-div">
    <?= $this->render('_form', [
        'model' => $model,
        'server_list' => $server_list
    ]) ?>
</div>