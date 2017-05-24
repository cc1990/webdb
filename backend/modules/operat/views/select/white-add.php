<?php 
use yii\helpers\Html;

$this->title = "白名单添加";

$this->params['breadcrumbs'][] = ['label' => 'White', 'url' => ['white']];
$this->params['type'] = $this->params['breadcrumbs'][] = "Create";
 ?>
 <div class="white-update common-div">
<?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>