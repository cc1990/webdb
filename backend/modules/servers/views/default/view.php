<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\servers\models\Servers */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Servers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="servers-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->server_id], ['class' => 'sui-btn btn-xlarge btn-info']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->server_id], [
            'class' => 'sui-btn btn-xlarge btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'server_id',
            'ip',
            'mirror_ip',
            'name',
            'environment',
            'updated_date',
        ],
    ]) ?>

</div>
