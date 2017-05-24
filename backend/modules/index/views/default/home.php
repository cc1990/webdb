<?php 
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'sql操作平台';
 ?>
<style type="text/css">
    .site-index{padding: 100px 0 0 300px;}
    .left{float: left;margin-left:50px;}
</style>
 <div class="site-index">
    <div class='left'><a href="<?=Url::to(['index']) ?>" class="sui-btn btn-xlarge btn-primary" id="execute_sql">通用库操作</a></div>
    <div class='left'><a href="<?=Url::to(['sharding/index']) ?>" class="sui-btn btn-xlarge btn-info">分库分表操作</a></div>
 </div>