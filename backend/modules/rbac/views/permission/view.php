<?php 
use yii\helpers\Html;

$this->title = 'View Permisstions';
$this->params['breadcrumbs'][] = ['label' => 'Permisstions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->name;


Html::cssFile('http://g.alicdn.com/sj/dpl/1.5.1/css/sui.min.css');
Html::cssFile('@web/public/css/custom.css');
?>
<link rel="stylesheet" type="text/css" href="http://g.alicdn.com/sj/dpl/1.5.1/css/sui.min.css">
<div class="permistion-item-view common-div">
    <h1></h1>
    <table class="sui-table table-bordered table-sideheader">
        <tbody>
            <tr><th><?= $model->attributeLabels()['name'] ?></th><td><?= $model->name ?></td></tr>
            <tr><th><?= $model->attributeLabels()['description'] ?></th><td><?= $model->description ?></td></tr>
            <tr><th><?= $model->attributeLabels()['ruleName'] ?></th><td><?= $model->ruleName==null?'<span class="text-danger">'.Yii::t('rbac','(not use)').'</span>':$model->ruleName?></td></tr>
            <?php
            /*
            <tr><th><?= $model->attributeLabels()['data'] ?></th><td><?= $model->data ?></td></tr>                      
             */
            
            ?>
        </tbody>
    </table>
</div>
