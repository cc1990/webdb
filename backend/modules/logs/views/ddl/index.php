<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'DDL记录';
$page = isset($search['page']) ? $search['page'] : 1;
$pageSize = isset($search['pageSize']) ? $search['pageSize'] : 1;
$offset = ($page-1) * $pageSize;
?>
<div class="div-index">
    <div class="servers-index div-index">
        <?php $this->params['breadcrumbs'][] = $this->title; ?>
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="blog-title content">
        <div class="content-right">
            <a href="/logs/rule/index" class="sui-btn btn-large  btn-success" id="rule"><i class="sui-icon icon-tb-settings"></i>数据规则配置</a>
        </div>
    </div>


    <table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
        <thead>
        <tr>
            <th width="5%">序号</th>
            <th width="6%">主机</th>
            <th width="7%">数据库</th>
            <th width="6%">执行人员</th>
            <th>脚本</th>
            <th width="7%">项目名称</th>
            <th width="7%">创建时间</th>
        </tr>
        <tr>
            <td></td>
            <td><input class="form-control" name="DdlSearch[host]" value="<?=isset($search["host"]) ? $search["host"] : ''?>" type="text"></td>
            <td><input class="form-control" name="DdlSearch[database]" value="<?=isset($search["database"]) ? $search["database"] : ''?>" type="text"></td>
            <td><input class="form-control" name="DdlSearch[username]" value="<?=isset($search["username"]) ? $search["username"] : ''?>" type="text"></td>
            <td><input class="form-control" name="DdlSearch[table]" value="<?=isset($search["table"]) ? $search["table"] : ''?>" type="text"></td>
            <td><input class="form-control" name="DdlSearch[project_name]" value="<?=isset($search["project_name"]) ? $search["project_name"] : ''?>" type="text"></td>
            <td></td>
        </tr>
        <?php foreach($ddl_list as $key=>$val) : ?>
            <tr>
                <td><?=$offset + $key + 1?></td>
                <td><?=$val['host']?></td>
                <td><?=$val['database']?></td>
                <td><?=$val['username']?></td>
                <td><?=$val['script']?></td>
                <td><?=$val['project_name']?></td>
                <td><?=$val['created_date']?></td>
            </tr>
        <?php endforeach; ?>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<?php $this->registerJsFile("/public/js/layout.js"); ?>
<?php $this->registerJsFile("/public/js/local/function.js"); ?>
<script>
    $(document).ready(function(){
        //配置用户规则
        $('#rule').on('click',function(){
            location.href = "/logs/rule/index";
            return false;
        });
    });
    //数据检索
    $("input[class='form-control']").keydown(function(event){
        if(event.keyCode == 13){
            location.href = '/logs/ddl/index?host=' +
                $("input[name='DdlSearch[host]']").val() +
                '&database=' + $("input[name='DdlSearch[database]']").val() +
                '&username=' + $("input[name='DdlSearch[username]']").val() +
                '&table=' + $("input[name='DdlSearch[table]']").val() +
                '&project_name=' + $("input[name='DdlSearch[project_name]']").val();
        }
    });
</script>
