<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '权限管理';
?>
<div class="div-index">
    <div class="servers-index div-index">
        <?php $this->params['breadcrumbs'][] = $this->title; ?>
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="blog-title content">
        <div class="select-menu sui-layout content-left">
            请选择服务器： <select name="server_host" id="DBHost">
                <?php foreach($server_list as $key=>$val) { ?>
                <option server_id="<?=$val['server_id']?>" value="<?=$val['ip']?>" data-environment="<?=$val['environment']?>" data-name="<?=$val['name']?>" <?php if($val['ip'] == $ip) echo 'selected="selected"';?>><?=$val['ip']?> - <?=$val['name']?></option>';
                <?php } ?>
            </select>
            <span class="status" title="服务器通讯失败，请检查服务器是否正常运行！"><i class="iconfont" style="color:#e8351f"></i></span>
        </div>
        <div class="content-right">
            <a href="/servers/rule/index" class="sui-btn btn-large  btn-success" id="rule"><i class="sui-icon icon-tb-settings"></i>用户规则配置</a>
            <a href="javascript:void(0);" class="sui-btn btn-large btn-primary" id="add_user"><i class="sui-icon icon-plus-sign"></i>新增用户</a>
        </div>
    <!--    <div class="sui-msg msg-large msg-tips">-->
    <!--        <div class="msg-con">您正在编辑用户php的数据库操作权限!!!</div>-->
    <!--        <s class="msg-icon"></s>-->
    <!--    </div>-->
    <!--    <div class="content-right warning"></div>-->
    </div>


    <table class="sui-table table-bordered table-zebra table-content-center" id="dbList">
        <thead>
        <tr>
            <th>用户名</th>
            <th>主机</th>
            <th>授权操作</th>
            <th>用户操作</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<?php $this->registerJsFile("/public/js/layout.js"); ?>
<?php $this->registerJsFile("/public/js/local/function.js"); ?>
<script>
    $(document).ready(function(){
        //初始化
        var status;
        status = ping($('#DBHost').val());
        if(status.status != 0){
            errorTip("服务器:"+$('#DBHost').val()+" 连接异常，请联系管理员或重新选择服务器!");
        }else{
            getUserList($('#DBHost').val());
        }

        //选择服务器触发
        $('#DBHost').on('change',function(){
            status = ping($(this).val());
            $('#dbList tbody').html('');
            if(status.status != 0){
                errorTip("服务器连接异常，请联系管理员或重新选择服务器!");
            }else{
                getUserList($('#DBHost').val());
            }
        });

        //点击编辑操作，操作数据库
//        $("a[event-bind='edit']").on('click',function(){
//            alert('123');
//            getPrivilege($(this).attr('data-serverId'),$(this).attr('data-db_name'),$(this).attr('data-ip'),1);
//        });

        //新增用户
        $('#add_user').on('click',function(){
            userEdit($('#DBHost').val(),'','','add');
        });

        //配置用户规则
        $('#rule').on('click',function(){
            var server_id = $('#DBHost option:selected').attr('server_id')
            location.href = "/servers/rule/index?server_id=" + server_id;
            return false;
        });
    });
</script>
