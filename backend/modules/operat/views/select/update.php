<?php 
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '查询条数限制';
?>
<div class="site-index common-div">
    <h4>查询条数限制（修改）</h4>
    <form id="w0" action="<?=Url::to(['update']); ?>?id=1" method="post" class="sui-form">
    <?php foreach ($list as $key => $value): if( $key == 'id' ){ echo "<input type='hidden' name='Select[id]' value='".$value."'>"; continue;} ?>
        <div class="form-group field-select-<?=$key ?>">
        <?php if ( $key == 'white_list' ) { ?>
            <label class="control-label" for="white_list">白名单设置：<font size="2" color="red">白名单设置只对线上数据库有效，填写格式为：库名.表名，列如：membercenter.user_info</font></label>
            <textarea name="Select[white_list]" style="width: 100%; height: 200px;"><?=$value ?></textarea>
        <?php } else if( $key == 'white_list_num' ) { ?>
            <label class="control-label" for="select-<?=$key ?>">白名单限制条数</label>
            <input type="text" id="select-white_list_num" class="input-xxlarge input-xfat" name="Select[white_list_num]" value="<?=$value ?>">

        <?php }else{ ?>
            <label class="control-label" for="select-<?=$key ?>"><?=$rule[$key] ?></label>
            <input type="text" id="select-<?=$key ?>" class="input-xxlarge input-xfat" name="Select[<?=$key ?>]" value="<?=$value ?>">

        <?php } ?>
            <div class="help-block"></div>
        </div>
    <?php endforeach ?>
        <div class="form-group">
            <button type="submit" class="sui-btn btn-xlarge btn-info">Update</button>
        </div>
    </form>
</div>