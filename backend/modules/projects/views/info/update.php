<?php 
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = "编辑项目信息";

$this->title = 'Update ProjectsInfo: ';
$this->params['breadcrumbs'][] = ['label' => 'ProjectsInfo', 'url' => ['index']];
//$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->pro_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="common-div">
    <?= $this->render('_form', [
                'model' => $model,
                'data' => $data
            ]); ?>
</div>