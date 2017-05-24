<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'DDL操作规则管理';
$title2 = "<a href='/logs/ddl/index' event-bind='redirect'>DDL操作查看</a>-DDL操作规则管理";
$page = Yii::$app->request->getQueryParam("page",1);
$pageSize = Yii::$app->request->getQueryParam("per-page",20);
?>
<div class="div-index">
<div class="servers-index div-index">
    <?php $this->params['breadcrumbs'][] = $this->title; ?>
    <h1><?=$title2?></h1>
</div>
<div class="blog-title content">
    <div class="content-right sui-layout select-menu">
        <a href="javascript:void(0);" class="sui-btn btn-large btn-primary" id="add_rule"><i class="sui-icon icon-plus-sign"></i>新增规则</a>
    </div>
</div>


<table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
    <thead>
    <tr>
        <th>序号</th>
        <th>数据库名称</th>
        <th>表格名称</th>
        <th>状态</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td></td>
            <td><input class="form-control" name="RuleSearch[database]" type="text" value="<?=isset($search['database']) ? $search['database'] : ''?>"></td>
            <td><input class="form-control" name="RuleSearch[table]" type="text" value="<?=isset($search['table']) ? $search['table'] : ''?>"></td>
            <td><select name="RuleSearch[status]">
                    <option value="">请选择状态</option>
                    <option value="1" <?php if(isset($search['status']) && $search['status'] == 1) echo 'selected="selected"'; ?>>开启</option>
                    <option value="0" <?php if(isset($search['status']) && $search['status'] == 0) echo 'selected="selected"'; ?>>关闭</option>
                </select>
            </td>
            <td></td>
        </tr>
        <?php foreach($rule_list as $key=>$value) : ?>
            <tr><td><?=($page-1)*$pageSize + ($key+1)?></td>
                <td><?=$value['database']?></td>
                <td><?=$value['table']?></td>
                <td><img src="/public/images/<?=$value['status']?"on":"off"?>.png" event-bind="status_change" data-status="<?=$value['status']?0:1?>" data-id="<?=$value['id']?>"></td>
                <td>
                    <a href="#" event-bind="update" data-database="<?=$value['database']?>" data-table="<?=$value['table']?>" data-id="<?=$value['id']?>">编辑</a>
                    <a href="/logs/rule/delete?id=<?=$value['id']?>" event-bind="delete">删除</a>
                </td></tr>
        <?php endforeach; ?>
                <td colspan="5"><?=$pageHtml?></td>
    </tbody>
</table>
</div>
<?php $this->registerJsFile("/public/js/local/function.js"); ?>
<script>
    $(document).ready(function(){
        //添加事件
        $('#add_rule').on('click',function(){
            var body = '';
            body += '<div class="sui-form projects-form">';
            body += '<div class="form-group field-projects-name"><label class="control-label">数据库名称:</label><input type="text" class="input-info input-xlarge" id="database" value=""></div>';
            body += '<div class="form-group field-projects-name"><label class="control-label">表格名称:</label><input type="text" class="input-info input-xlarge" id="table" value=""></div>';
            body += '<input type="hidden" id="server_id" value="'+$('#DBHost option:selected').attr('server_id')+'">';
            body += '</div>';
            successTip(body, DdlruleAdd);
        });

        //编辑事件
        $($("a[event-bind='update']")).on('click',function(){
            var body = '';
            body += '<div class="sui-form projects-form">';
            body += '<div class="form-group field-projects-name"><label class="control-label">数据库名称:</label><input type="text" class="input-info input-xlarge" id="database" value="' + $(this).attr("data-database") + '"></div>';
            body += '<div class="form-group field-projects-name"><label class="control-label">表格名称:</label><input type="text" class="input-info input-xlarge" id="table" value="' + $(this).attr("data-table") + '"></div>';
            body += '<input type="hidden" id="id" value="' + $(this).attr('data-id') + '">';
            body += '</div>';
            successTip(body, DdlRuleSave);
            return false;
        });

        //删除事件
        $("a[event-bind='delete']").on('click',function(){
            if(confirm("确认删除?")){
                $.get($(this).attr('href'),
                    {},
                    function(data){
                        if(data.status == 1){
                            successTip(data.msg,function(){location.href = '/logs/rule/index'});
                        }else{
                            errorTip(data.msg);
                        }
                    },
                    'json'
                )
            }
            return false;
        });

        //开启关闭禁用状态
        $("img[event-bind='status_change']").on('click',function(){
            DdlStatusChange($(this).attr('data-id'),$(this).attr('data-status'),$(this))
        });

        //页面跳转
        $("a[event-bind='redirect']").on('click',function(){
            location.href = $(this).attr('href');
            return false;
        });

        //数据检索
        $("input[class='form-control']").keydown(function(event){
            if(event.keyCode == 13){
                location.href = '/logs/rule/index?database=' + $("input[name='RuleSearch[database]']").val() + '&table=' + $("input[name='RuleSearch[table]']").val() + '&status=' + $("select[name='RuleSearch[status]']").val();
            }
        });

        //状态变更
        $("select[name='RuleSearch[status]']").change(function(){
            location.href = '/logs/rule/index?database=' + $("input[name='RuleSearch[database]']").val() + '&table=' + $("input[name='RuleSearch[table]']").val() + '&status=' + $("select[name='RuleSearch[status]']").val();
        })
    });
</script>
