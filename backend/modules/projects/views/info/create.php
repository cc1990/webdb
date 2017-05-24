<?php 
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = "添加项目信息";

$this->params['breadcrumbs'][] = ['label' => 'ProjectsInfo', 'url' => ['index']];
$this->params['type'] = $this->params['breadcrumbs'][] = "Create";
?>
<div class="common-div">
    <?= $this->render('_form', [
                'model' => $model,
                'data' => $data
            ]); ?>
</div>