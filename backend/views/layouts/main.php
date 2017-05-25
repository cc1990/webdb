<?php

/* @var $this \yii\web\View */
/* @var $content string */

use backend\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title>数据库管理平台</title>
    <?=Html::cssFile('http://g.alicdn.com/sj/dpl/1.5.1/css/sui.min.css'); ?>
    <?=Html::cssFile('http://g.alicdn.com/sj/dpl/1.5.1/css/sui-append.min.css') ?>
    <?=Html::cssFile('@web/public/iconfont/iconfont.css') ?>
    <?=Html::jsFile('@web/public/js/jquery.min.js') ?>
    <?=Html::jsFile('@web/public/js/sui.min.js') ?>
    <?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
    <?=Html::cssFile('@web/public/css/custom.css') ?>
    <?php $this->head()?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="sui-navbar navbar-inverse" style="z-index:10;">
        <div class="navbar-inner">
            <a href="/" class="sui-brand">数据库管理平台<span class="version">V3.0</span></a>
            <ul class="sui-nav">
                <li><a href="/index/default">SQL操作</a></li>
                <li class="sui-dropdown">
                    <a href="" class="dropdown-toggle" data-toggle="dropdown">数据操作 <i class="caret"></i></a>
                    <ul class="sui-dropdown-menu" role="menu">
                        <li role="presentation"><a href="/index/default/export" role="menuitem" tabindex="-1">脚本导出</a></li>
                        <li role="presentation"><a href="/index/batch" role="menuitem" tabindex="-1">数据迁移</a></li>
                        <li role="presentation"><a href="/index/execute" role="menuitem" tabindex="-1">一键执行</a></li>
                        <li role="presentation"><a href="/index/migrate" role="menuitem" tabindex="-1">脚本部署</a></li>
                        <li role="presentation"><a href="/index/deployment" role="menuitem" tabindex="-1">数据库部署</a></li>
                        <li role="presentation"><a href="/index/back" role="menuitem" tabindex="-1">数据库备份</a></li>
                        <li role="presentation"><a href="/backup/index" role="menuitem" tabindex="-1">数据库脚本备份</a></li>
                        <li role="presentation"><a href="/index/createdb" role="menuitem" tabindex="-1">建库流程</a></li>
                    </ul>
                </li>
                <li class="sui-dropdown">
                    <a href="/rbac/role" class="dropdown-toggle" data-toggle="dropdown">权限管理 <i class="caret"></i></a>
                    <ul class="sui-dropdown-menu" role="menu">
                        <li role="presentation"><a href="/rbac/role" role="menuitem" tabindex="-1">角色管理</a></li>
                        <li role="presentation"><a href="/rbac/assignment" role="menuitem" tabindex="-1">角色分配</a></li>
                        <li role="presentation"><a href="/rbac/permission" role="menuitem" tabindex="-1">许可管理</a></li>
                    </ul>
                </li>
                <li class="sui-dropdown">
                    <a href="/operat/select" class="dropdown-toggle" data-toggle="dropdown">操作控制 <i class="caret"></i></a>
                    <ul class="sui-dropdown-menu" role="menu">
                        <li role="presentation"><a href="/operat/select" role="menuitem" tabindex="-1">查询限制</a></li>
                        <li role="presentation"><a href="/operat/select/white" role="menuitem" tabindex="-1">查询白名单</a></li>
                        <li role="presentation"><a href="/operat/authorize" role="menuitem" tabindex="-1">授权白名单</a></li>
                        <li role="presentation"><a href="/operat/cache" role="menuitem" tabindex="-1">更新缓存</a></li>
                    </ul>
                </li>
                <li><a href="/users">用户管理</a></li>
                <li class="sui-dropdown">
                    <a href="/servers" class="dropdown-toggle" data-toggle="dropdown">服务器管理<i class="caret"></i></a>
                    <ul class="sui-dropdown-menu" role="menu">
                        <li role="presentation"><a href="/servers" role="menuitem" tabindex="-1">服务器列表</a></li>
                        <li role="presentation"><a href="/servers/privilege/index" role="menuitem" tabindex="-1">服务器用户权限</a></li>
                    </ul>
                </li>
                <li class="sui-dropdown">
                    <a href="/projects" class="dropdown-toggle" data-toggle="dropdown">项目管理 <i class="caret"></i></a>
                    <ul class="sui-dropdown-menu" role="menu">
                        <li role="presentation"><a href="/projects" role="menuitem" tabindex="-1">项目列表</a></li>
                        <li role="presentation"><a href="/projects/scripts" role="menuitem" tabindex="-1">项目脚本</a></li>
                        <li role="presentation"><a href="/projects/info" role="menuitem" tabindex="-1">项目信息</a></li>
                    </ul>
                </li>
                <li class="sui-dropdown">
                    <a href="/logs/version" class="dropdown-toggle" data-toggle="dropdown">信息管理 <i class="caret"></i></a>
                    <ul class="sui-dropdown-menu" role="menu">
                        <li role="presentation"><a href="/logs/version" role="menuitem" tabindex="-1">版本记录</a></li>
                        <li role="presentation"><a href="/logs/scripts" role="menuitem" tabindex="-1">查询记录</a></li>
                        <li role="presentation"><a href="/logs/ddl" role="menuitem" tabindex="-1">DDL记录</a></li>
                        <li role="presentation"><a href="/correct/log" role="menuitem" tabindex="-1">订正记录</a></li>
                        <li role="presentation"><a href="/correct/scripts" role="menuitem" tabindex="-1">自助记录</a></li>
                    </ul>
                </li>
                <!-- <li><a href="">帮助</a></li> -->
            </ul>
            <ul class="sui-nav pull-right">
            <?php if(Yii::$app->users->isGuest){ ?>
                <li><a href="/site/login">登录</a></li>
            <?php }else{ ?>
                <li><a href="javascript:;" id="logout">退出（ <?php echo Yii::$app->users->identity->username ?> ）</a></li>
            <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container">
        <?php if( isset($this->params['breadcrumbs']) ){ ?>
            <div id="commonbox">
                <div class="breadcrumbs">
                    <?= Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <?= Alert::widget() ?>
                </div>
                <?= $content ?>
            </div>
        <?php }else{ ?>
            <?= Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]) ?>
            <?= Alert::widget() ?>
            <?= $content ?>    
        <?php } ?>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="" style="font-size: 8pt;">CopyRight &copy; <?= date('Y') ?> cc1990 by DB平台</div>
        </div>
    </footer>

<?php $this->endBody() ?>
<script type="text/javascript">
    $(document).ready(function(){
        $(".navbar-inner a").on('click', function(){
            var local_href = window.location.pathname;
            if( local_href == "/index/default" || local_href == "/index/default/index" || local_href == "/index/sharding" || local_href == "/"){
                if( $(this).attr('href') != '/site/logout' ){
                    $(this).attr("target", "_blank"); 
                }
            }
        });
        $("#logout").on("click", function(){
            $.getJSON("/site/logout", function(data){
                if( data.code == 0 ){
                    layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                }else{
                    window.location.href="/site/login";
                }
            });
        });

    });
</script>
</body>
</html>
<?php $this->endPage() ?>
