<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


?>
<div class="Version-index div-index">

    <?php $this->params['breadcrumbs'][] = "Versiong Logs"; ?>
    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create VersionLog', ['create'], ['class' => 'sui-btn btn-xlarge btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'version_title',
            'version_number',
            'author',
            [
                'attribute' => 'create_time',
                'headerOptions' => ['width' => '200px'],
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'contentOptions' => ['style' => 'width:70px;']
            ],
        ],
    ]); ?>

</div>
