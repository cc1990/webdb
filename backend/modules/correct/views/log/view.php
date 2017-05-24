<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Url;

/** Get all roles */
$authManager = Yii::$app->authManager;


$this->params['breadcrumbs'][] = "";
?>

<link rel="stylesheet" type="text/css" href="http://g.alicdn.com/sj/dpl/1.5.1/css/sui.min.css">
<style type="text/css">
    b{font-weight: 800;
    text-align: left;
    font-size: 11pt;}
</style>
<div class="user-assignment-form" id="htmlpage" >
<?php $form = ActiveForm::begin(['id' => 'infoform', 'options' => ['class' => 'sui-form', ]]); ?>
    <table class="sui-table table-bordered table-zebra">
        <thead>
            <tr>
                <th style="width:30px"></th>
                <th>服务器IP</th>
                <th>数据库名</th>
                <th>脚本数量</th>
                <th>影响行数</th>
            </tr>
        <tbody id="assignment">            
            <?php foreach ($data as $key => $value) { ?>
            <tr>
                <td><?=$key+1;?></td>
                <td><?= $value['server_ip']; ?></td>
                <td><?= $value['db_name']; ?></td>
                <td><?= $value['scripts_number'] ?></td>
                <td><?= $value['influences_number'] ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
<?php ActiveForm::end(); ?>
</div>