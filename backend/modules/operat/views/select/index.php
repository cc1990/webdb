<?php 
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '查询条数限制';
?>
<div class="site-index div-index">
    <h4>查询条数限制</h4>
    <table class="sui-table table-bordered table-zebra">
        <thead>
            <tr>
                <th>环境</th>
                <th>条数限制</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $key => $value): if( $key == 'id' || $key == 'white_list' || $key == 'white_list_num' ){ continue;} ?>
            <tr>
                <td><?=$rule[$key] ?></td>
                <td><?=$value ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <div class="form-group">
        <label class="control-label" for="white_list">白名单设置：<font size="2" color="red">白名单设置只对线上数据库有效，填写格式为：库名.表名，列如：membercenter.user_info</font></label>
        <textarea name="white_list" style="width: 100%; height: 200px;"><?= $list['white_list'] ?></textarea>
    </div></br>
    <div class="form-group">
        <label class="control-label" for="white_list">白名单限制条数</label>
        <input type="text" class="input-medium" name="" value="<?=$list['white_list_num'] ?>">
    </div></br>
    <div class="form-group">
        <input type='hidden' name='id' value='<?=$list['id'] ?>'>
        <button id="submit" class="sui-btn btn-xlarge btn-success">点击编辑</button>
    </div>
</div>
<script type="text/javascript">
    $("#submit").on("click", function(){
        window.location.href="<?=Url::to(['update'])?>?id=1";
    })


</script>