<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$title2 = "权限管理-<a href='/servers/privilege/index?ip={$info['ip']}'>{$info['ip']}</a>-{$info['user']}@{$info['host']}";
$this->title = "权限管理-{$info['ip']}-{$info['user']}@{$info['host']}";
?>
<div class="div-index">
<div class="servers-index div-index">
    <?php $this->params['breadcrumbs'][] = $this->title; ?>
    <h1><?=$title2 ?></h1>
</div>

<input type="hidden" id="DBHost" value="<?=$info['ip']?>">
<table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
    <thead>
    <tr>
        <th>数据库名称</th>
        <th>包含的表数量</th>
        <th>编辑</th>
    </tr>
    </thead>
    <tbody>
        <?php foreach($database_list as $value) : ?>
            <tr>
                <td><?=$value['db_name']?></td>
                <td><?=$value['table_num']?></td>
                <td><a href="javascript:void(0)" onclick="getPrivilege('<?=$info['host']?>','<?=$info['user']?>','<?=$info['ip']?>','<?=$value['db_name']?>',1,'<?php echo "{$info['ip']}-{$value['db_name']}"; ?>','ck','*')">查看</a>
                <?php if(@$rule_list[$info['user']."@".$info['host']] != true) { ?>
                    <a href="javascript:void(0)" onclick="getPrivilege('<?=$info['host']?>','<?=$info['user']?>','<?=$info['ip']?>','<?=$value['db_name']?>',1,'<?php echo "{$info['ip']}-{$value['db_name']}"; ?>','edit','*')">授权</a>
                <?php } ?>
                <a href="/servers/table/index?host=<?=$info['host']?>&user=<?=$info['user']?>&ip=<?=$info['ip']?>&database=<?=$value['db_name']?>&server_id=<?=$info['server_id']?>">表授权</a>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr><td colspan="3" class="content-right"><?=$pageHtml?></td></tr>
    </tbody>
</table>
</div>
<?php $this->registerJsFile("/public/js/local/function.js"); ?>
<script>
    $(document).ready(function(){

    });
</script>
