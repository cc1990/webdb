<?php
use yii\helpers\Url;
use yii\helpers\Html;
/* @var $this yii\web\View */

$this->title = 'sql操作平台';
?>
<div class="site-index">
    <div class="jumbotron">
        <h2 align="left">请审核内容</h2>
        <div style="width:100%; margin:0px auto; text-align:left">
            <table class="sui-table table-bordered table-zebra">
                <thead>
                <tr><th width="50px">id</th><th>注释</th><th>sql</th><th>执行人</th><th>执行时间</th><!--<th>操作</th>--></tr>
                </thead>
                <tbody>
                    <?php foreach ($log_list as $v):?>
                        <tr log_id="<?=$v['log_id']?>">
                            <td ><?=$v['log_id']?></td>
                            <td ><?=$v['notes']?></td>
                            <td style="max-width:250px;word-wrap:break-word;overflow:hidden;"><textarea class="script"><?=$v['script']?>;</textarea></td>
                            <td><?=$v['username']?></td>
                            <td><?=$v['created_date']?></td>
                            <!--<td>
                                <a title="运行" class="audit_run">
                                    <span class="glyphicon glyphicon-play"></span>
                                </a>
                                <a title="删除"  class="audit_del">
                                    <span class="glyphicon glyphicon-remove"></span>
                                </a>
                                <a title="撤销删除"  class="audit_revoke">
                                    <span class="glyphicon glyphicon-repeat"></span>
                                </a>
                            </td>-->
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        </div>
        <p class="lead"></p>

        <p>
            <?if($is_check == 1):?>
                <a class="sui-btn btn-xlarge btn-info" id="review">全部重审</a>
            <?else :?>
                <a class="sui-btn btn-xlarge btn-success" id="adopt">全部通过</a>
            <?endif;?>
            <?if($is_lock == 1):?>
                <a class="sui-btn btn-xlarge btn-danger" id="unlock">全部解锁</a>
            <?elseif($is_lock == 0) :?>
                <a class="sui-btn btn-xlarge btn-warning" id="lock">全部锁定</a>
            <?endif;?>
        </p>
    </div>
    <div class="body-content">

    </div>
</div>
<?=Html::jsFile('@web/static/plug/My97DatePicker/WdatePicker.js')?>
<script type="text/javascript">

    //全部重审
    $("#review").on("click", function () {
        do_audit_list(0);
    });

    //全部通过
    $("#adopt").on("click", function () {
        do_audit_list(1);
    });

    //全部解锁
    $("#unlock").on("click", function () {
        do_audit_list(0);
    });

    //全部锁定
    $("#lock").on("click", function () {
        do_audit_list(3);
    });

    //审核方法复用
    function do_audit_list(action){
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "<?=Url::to(['/audits/default/auditlist']);?>",
            data: {
                server_id:"<?=$server_id?>",
                database:"<?=$database?>",
                pro_id:"<?=$pro_id?>",
                action:action,
            },
            success: function ($data) {
                if($data.code == 0){
                    alert($data.msg);
                }else{
                    alert($data.msg);
                    location.reload();
                }
            }
        });
    }

    //运行sql
    $(".audit_run").on("click", function () {
        arrange_audit($(this),1);
    });

    //删除
    $(".audit_del").on("click", function () {
        arrange_audit($(this),2);
    });

    //撤销删除
    $(".audit_revoke").on("click", function () {
        arrange_audit($(this),3);
    });

    //sql整理方法复用
    function arrange_audit(self_node,action){
        //alert(self_node.parent().parent().attr('log_id'));
        log_id = self_node.parent().parent().attr('log_id');
        script = self_node.parent().parent().find('textarea.script').html();
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "<?=Url::to(['/audits/default/arrange-audit']);?>",
            data: {
                log_id:log_id,
                action:action,
                script:script,
            },
            success: function ($data) {
                if($data.code == 0){
                    alert($data.msg);
                }else{
                    alert($data.msg);
                    //location.reload();
                }
            }
        });
    }

</script>
