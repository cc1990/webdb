<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model backend\modules\servers\models\Servers */

$this->title = 'Create Servers';
$this->params['breadcrumbs'][] = ['label' => 'Servers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$environment_array = array(
    'dev' => '开发',
    'test' => '测试',
    'test_trunk' => '测试主干',
    'pre' => '预发布',
    'pro' => '线上',
    'dev_trunk' => '研发主干',
);
?>
<div class="servers-create common-div">

    <h1><?= Html::encode($this->title) ?></h1>

    <form id="w0" action="<?=Url::to(['create']); ?>" method="post" class="sui-form">
    <div class="form-group field-servers-ip">
        <label class="control-label" for="servers-ip">IP地址</label>
        <input type="text" id="servers-ip" class="input-xxlarge input-xfat" name="Servers[ip]">

        <div class="help-block"></div>
    </div>
    <div class="form-group field-servers-mirror_ip">
        <label class="control-label" for="servers-mirror_ip">镜像服务器IP</label>
        <input type="text" id="servers-mirror_ip" class="input-xxlarge input-xfat" name="Servers[mirror_ip]">

        <div class="help-block"></div>
    </div>
    <div class="form-group field-servers-name">
        <label class="control-label" for="servers-name">描述</label>
        <input type="text" id="servers-name" class="input-xxlarge input-xfat" name="Servers[name]" maxlength="50">

        <div class="help-block"></div>
    </div>
    <div class="form-group field-servers-environment">
        <label class="control-label" for="servers-environment">服务器环境</label>
        <select name="Servers[environment]" id="servers-environment" class="form-control select-xfat" >
            <?php foreach ($environment_array as $key => $value) {
                echo "<option value='".$key."'>".$value."</option>";
            } ?>
        </select>

        <div class="help-block"></div>
    </div>
    <div class="form-group">
        <button type="submit" class="sui-btn btn-xlarge btn-success">Create</button>    </div>

    </form>


</div>