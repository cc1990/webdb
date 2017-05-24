<?php 
use yii\helper\Html;

$this->params['breadcrumbs'][] = ['label' => 'VersionLog', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->version_title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="white-update common-div">
    <?= 
    $this->render('_form', [
        'model' => $model
        ]);
    ?>
</div>
