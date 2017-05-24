<?php 
use yii\helper\Html;

$this->params['breadcrumbs'][] = ['label' => 'VersionLog', 'url' => ['index']];
$this->params['type'] = $this->params['breadcrumbs'][] = "Create";
?>
<div class="white-update common-div">
    <?= 
    $this->render('_form', [
        'model' => $model
        ]);
    ?>
</div>
