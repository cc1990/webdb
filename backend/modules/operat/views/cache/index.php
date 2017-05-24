<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\servers\models\ServersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '更新Redis缓存';
?>
<div class="div-index">
    <form id="cacheForm" name="cacheForm" enctype="multipart/form-data" class="sui-form sui-row-fluid form-horizontal">
    <div class="jumbotron">
        <div class="servers-index div-index">
            <?php $this->params['breadcrumbs'][] = $this->title; ?>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="blog-title content">
            <div class="select-menu sui-layout content-left" style="padding: 0 0;">
                请选择服务器： <select name="server_ip" id="DBHost">
                    <?php foreach($server_list as $key=>$val) { ?>
                    <option server_id="<?=$val['server_id']?>" value="<?=$val['ip']?>" data-environment="<?=$val['environment']?>" data-name="<?=$val['name']?>" ><?=$val['ip']?> - <?=$val['name']?></option>';
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="dblist" float: left;"></div>
        <p><a href="javascript:void(0);" class="sui-btn btn-xlarge btn-success" id="cache">更新缓存</a></p>
    </div>
    </form>
</div>
<script>
    $(document).ready(function(){
        getDb($('#DBHost').val());
    });
    //选择服务器触发
    $('#DBHost').on('change',function(){
        $('.dblist').html('');
        getDb($('#DBHost').val());
    });
    function getDb( server_ip ){
        var url = "<?=Url::to(['get-db?server_ip='])?>"+server_ip;
        $.getJSON( url, function(data){
            var tpl = "";
            if( data.code == 0 ){
                alert(data.msg);
            }else{
                $.each( data.content, function(i, vo){
                    tpl += '<label style="width: 200px;display: inline-block;" class="db_name" ><input type="checkbox" name="db_name[]" value="'+vo+'">'+vo+'</label>';
                } );
                //alert(tpl);
                $(".dblist").html(tpl);
            }
        } );
    }

    $("#cache").on('click', function(){
        var db_name_num = 0;
        $("input[type='checkbox']").each(function(){
            if( $(this).is(":checked") ){
                db_name_num += 1;
            }
        });

        $("#cache").html('正在更新');
        $("#cache").addClass('disabled');
        $.ajax({
            url: "<?=Url::to(['update'])?>",
            data: $("#cacheForm").serialize(),
            type: 'post',
            dataType: 'json',
            success: function(data){
                alert(data.msg);
                $("#cache").html('更新缓存');
                $("#cache").removeClass('disabled');
            },
            error: function( e ){
                console.log(e);
                alert("error");
                $("#cache").html('更新缓存');
                $("#cache").removeClass('disabled');
            }
        })
    });
</script>
