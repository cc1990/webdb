<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = "权限管理-{$info['ip']}-{$info['user']}@{$info['host']}-{$info['database']}";
$title2 = "权限管理-<a href='/servers/privilege/index?ip={$info['ip']}'>{$info['ip']}</a>-<a href='/servers/database/index?ip={$info['ip']}&user={$info['user']}&host={$info['host']}&server_id={$info['server_id']}'>{$info['user']}@{$info['host']}</a>-{$info['database']}";
?>
<div class="div-index">
<div class="servers-index div-index">
    <?php $this->params['breadcrumbs'][] = $this->title; ?>
    <h1><?= $title2 ?></h1>
</div>

<input type="hidden" id="DBHost" value="<?=$info['ip']?>">
<table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
    <thead>
    <tr>
        <th>数据库名称</th>
        <th>表名称</th>
        <th>存储引擎</th>
        <th>起始递增数</th>
        <th>数据量</th>
        <th>创建时间</th>
        <th>编辑</th>
    </tr>
    </thead>
    <tbody>
        <?php foreach($table_list as $value) : ?>
            <tr>
                <td><?=$value['db_name']?></td>
                <td><?=$value['tb_name']?></td>
                <td><?=$value['engine']?></td>
                <td><?=$value['auto_increment']?></td>
                <td><?=$value['num']?></td>
                <td><?=$value['create_time']?></td>
                <td>
                <a href="javascript:void(0)" onclick="getPrivilege('<?=$info['host']?>','<?=$info['user']?>','<?=$info['ip']?>','<?=$value['db_name']?>',2,'<?php echo "{$info['ip']}-{$value['db_name']}-{$value['tb_name']}"; ?>','ck','<?=$value['tb_name']?>')">查看</a>
                <?php if(@$rule_list[$info['user']."@".$info['host']] != true) { ?>
                    <a href="javascript:void(0)" onclick="getPrivilege('<?=$info['host']?>','<?=$info['user']?>','<?=$info['ip']?>','<?=$value['db_name']?>',2,'<?php echo "{$info['ip']}-{$value['db_name']}-{$value['tb_name']}"; ?>','edit','<?=$value['tb_name']?>')">授权</a>
                <?php } ?>
                </td>
            </tr>
        <?php endforeach; ?>
		<tr><td colspan=7><?=$pageHtml?></td></tr>
    </tbody>
</table>
</div>
<?php $this->registerJsFile("/public/js/local/function.js"); ?>
<script>
    $(document).ready(function(){

    });
</script>
