<?php

use johnitvn\rbacplus\models\Role;
//服务器模型
use common\models\Servers;

$permissions = Role::getPermistions($model->name);
$first = '';
$rows = [];
foreach ($permissions as $permission) {
    if (empty($first)) {
        $first = $permission->name;
    } else {
        $rows[] = '<tr><td>' . $permission->name . '</td></tr>';
    }
}
$first_server_name = '';
$rows_servers = [];
$Server = new Servers();
foreach ($model->servers as $server_id) {
    $server = $Server->getServer($server_id);
    if (empty($first_server_name)) {
        $first_server_name = $server['name'];
    } else {
        $rows_servers[] = '<tr><td>' . $server['name'] . '</td></tr>';
    }
}

$environment_array = array(
            'dev' => '开发',
            'test' => '测试',
            'test_trunk' => '测试主干',
            'pre' => '预发布',
            'pro' => '线上',
            'dev_trunk' => '研发主干',
        );

$this->title = 'View Role';
$this->params['breadcrumbs'][] = ['label' => 'Role', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->name;
?>
<style type="text/css">
    td{word-break: break-all; word-wrap:break-word;}
</style>
<div class="permistion-item-view common-div">
    <table class="sui-table table-bordered table-zebra">
        <tbody>
            <tr><th><?= $model->attributeLabels()['name'] ?></th><td><?= $model->name ?></td></tr>
            <tr><th><?= $model->attributeLabels()['description'] ?></th><td><?= $model->description ?></td></tr>
            <tr><th><?= $model->attributeLabels()['ruleName'] ?></th><td><?= $model->ruleName == null ? '<span class="text-danger">' . Yii::t('rbac', '(not use)') . '</span>' : $model->ruleName ?></td></tr>
            <tr><th><?= $model->attributeLabels()['dbs'] ?></th><td><?= $model->dbs ?></td></tr>
            <tr><th><?= $model->attributeLabels()['environment'] ?></th><td>
            <?php $environment = $model->environment; $env = "";
                if( !empty($environment) ){
                    $environment = explode(',', $environment);
                    foreach ($environment as $key => $value) {
                        $env .= $environment_array[$value] . ',';
                    }
                }else{
                    $env = "";
                }
                echo $env;
            ?>
            </td></tr>
            <tr><th><?= $model->attributeLabels()['sqloperations'] ?></th><td><?= $model->sqloperations ?></td></tr>
            <tr><th><?= $model->attributeLabels()['sharding_operations'] ?></th><td><?= $model->sharding_operations ?></td></tr>
            <tr><th rowspan="<?= count($model->servers)==0 ? 1: count($model->servers)?>" ><?= $model->attributeLabels()['servers'] ?></th><td><?= $first_server_name ?></td></tr>
            <?= implode("", $rows_servers) ?>
            <tr><th rowspan="<?= count($permissions)==0 ? 1: count($permissions) ?>" ><?= $model->attributeLabels()['permissions'] ?></th><td><?= $first ?></td></tr>
            <?= implode("", $rows) ?>
    </table>
</div>
