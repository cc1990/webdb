<?php
use yii\helpers\Url;
use yii\helpers\Html;
/* @var $this yii\web\View */

$this->title = 'sql操作平台';
?>
<div class="site-index">
    <div class="jumbotron">
        <h2 align="left">请确认审核内容</h2>
        <div style="width:100%; margin:0px auto; text-align:left">
            <table class="sui-table table-bordered table-zebra">
                <thead>
                    <tr>
                        <td width="50px">ID</td>
                        <td>Script</td>
                        <td>注释</td>
                        <td width="200px">执行时间</td>
                        <td width="60px">操作</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($log_list as $v):?>
                        <tr>
                            <td><?=$v['log_id']?></td>
                            <td style="max-width:800px;word-wrap:break-word;overflow:hidden;"><?=$v['script']?>;</td>
                            <td style="max-width:250px;word-wrap:break-word;overflow:hidden;"><?=$v['notes']?></td>
                            <td style="width: 200px;"><?=$v['created_date']?></td>
                            <td><a id = "<?=$v['log_id']?>" class = "del" data-pjax="0"  aria-label="Delete" title="Delete"><i class="iconfont">&#xe61a;</i></a></td>
                        </tr>
                        <?php $ids .= $v['log_id'].',';?>
                    <?php endforeach;?>
                </tbody>
            </table>
        </div>
        <p class="lead"></p>
        <form id="formAudit" name="formExecuteSql" enctype="multipart/form-data">
        <p>
            <input id="ids" name="ids" value="<?=$ids?>" type="hidden">
            <input id="pro_id" name="pro_id" value="<?=$pro_id?>" type="hidden">
            <a class="sui-btn btn-xlarge" id="do_audit">提交审核</a>
            <a class="sui-btn btn-xlarge btn-warning" id="reset">重置</a>
        </p>
        </form>
    </div>
    <div class="body-content">

    </div>
</div>
<?=Html::jsFile('@web/static/plug/My97DatePicker/WdatePicker.js')?>
<script type="text/javascript">
    $(".del").on("click", function () {
        ids = $("#ids").val();
        ids = ids.replace($(this).attr('id'),'');
        $("#ids").val(ids);
        $(this).parent().parent().remove();

    });

    $("#reset").click(function(){
        window.location.reload();
    });

    $("#do_audit").on("click", function () {
        var do_audit_url= "<?=Url::to(['/audits/default/audit']);?>";
        $.ajax({
            type: "POST",
            dataType: "json",
            url: do_audit_url+'?server_id=<?=$server_id?>&database=<?=$database?>',
            data: $('#formAudit').serialize(),
            success: function ($data) {
                if($data.code == 0){
                    alert($data.msg);
                }else{
                    alert($data.msg);
                }
            }
        });

    });
</script>
