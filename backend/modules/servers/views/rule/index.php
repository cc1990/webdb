<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '用户规则管理';
$title2 = "<a href='/servers/privilege/index' event-bind='redirect'>权限管理</a>-用户规则管理";
?>
<div class="div-index">
<div class="servers-index div-index">
    <?php $this->params['breadcrumbs'][] = $this->title; ?>
    <h1><?=$title2?></h1>
</div>
<div class="blog-title content">
    <div class="select-menu sui-layout content-left">
        请选择服务器： <select name="server_host" id="DBHost">
            <?php foreach($server_list as $key=>$val) { ?>
            <option server_id="<?=$val['server_id']?>" value="<?=$val['ip']?>" data-environment="<?=$val['environment']?>" data-name="<?=$val['name']?>" <?php if($val['server_id'] == $server_id) echo 'selected="selected"';?>><?=$val['ip']?> - <?=$val['name']?></option>';
            <?php } ?>
        </select>
    </div>
    <div class="content-right">
        <a href="javascript:void(0);" class="sui-btn btn-large btn-primary" id="add_rule"><i class="sui-icon icon-plus-sign"></i>新增规则</a>
    </div>
</div>


<table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
    <thead>
    <tr>
        <th>服务器</th>
        <th>禁止编辑用户</th>
        <th>禁止编辑主机</th>
        <th>状态</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
        <?php foreach($rule_list as $key=>$value) : ?>
            <tr><td><?=$server_list[$value['server_id']]['ip'].'-'.$server_list[$value['server_id']]['name']?></td>
                <td><?=$value['user']?></td><td><?=$value['host']?></td>
                <td><img src="/public/images/<?=$value['status']?"on":"off"?>.png" event-bind="status_change" data-status="<?=$value['status']?0:1?>" data-id="<?=$value['id']?>"></td>
                <td><a href="/servers/rule/delete?id=<?=$value['id']?>" event-bind="delete">删除</a></td></tr>
        <?php endforeach; ?>
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
            body += '<div class="form-group field-projects-name"><label class="control-label">禁用用户:</label><input type="text" class="input-info input-xlarge" id="user" value=""></div>';
            body += '<div class="form-group field-projects-name"><label class="control-label">禁用主机:</label><input type="text" class="input-info input-xlarge" id="host" value=""></div>';
            body += '<input type="hidden" id="server_id" value="'+$('#DBHost option:selected').attr('server_id')+'">';
            body += '</div>';
            successTip(body, ruleAdd);
        });

        //删除事件
        $("a[event-bind='delete']").on('click',function(){
            if(confirm("确认删除?")){
                $.get($(this).attr('href'),
                    {},
                    function(data){
                        if(data.status == 1){
                            successTip(data.msg,function(){location.href = '/servers/rule/index?server_id=' + $('#DBHost option:selected').attr('server_id')})
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
            statusChange($(this).attr('data-id'),$(this).attr('data-status'),$(this))
        });

        //页面跳转
        $("a[event-bind='redirect']").on('click',function(){
            location.href = $(this).attr('href') + "?ip=" + $('#DBHost').val();
            return false;
        });

        //选择服务器事件
        $('#DBHost').on('change',function(){
            location.href = '/servers/rule/index?server_id=' + $('#DBHost option:selected').attr('server_id');
        });
    });
</script>
