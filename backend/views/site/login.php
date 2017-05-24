<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '登陆';
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>
    <span style="display: none;">loginhtml</span>
    <div class="row">
        <div class="col-lg-5">
            <form id="login-form" action="/site/login" method="post"  role="form" class="sui-form">
                <div class="form-group field-loginform-username required">
                    <label class="control-label" for="loginform-username">工号</label>
                    <input type="text" id="loginform-username" class="input-xxlarge input-xfat" name="username" autofocus>

                    <p class="help-block help-block-error"></p>
                </div>
                <div class="form-group field-loginform-password required">
                    <label class="control-label" for="loginform-password">密码</label>
                    <input type="password" id="loginform-password" class="input-xxlarge input-xfat" name="password">

                    <p class="help-block help-block-error"></p>
                </div>
                <div class="form-group field-loginform-rememberme">
                    <div class="checkbox">
                        <label for="loginform-rememberme">
                        <input type="checkbox" id="loginform-rememberme" name="rememberMe" value="1" checked>
                        Remember Me
                        </label>
                        <p class="help-block help-block-error"></p>

                    </div>
                </div>
                <div class="form-group">
                    <a class="sui-btn btn-xlarge btn-success" id="submit" name="login-button">Login</a>
                </div>

            </form>
        </div>
    </div>
</div>
<?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
<script type="text/javascript">
    function checkempty(){
        if ($("#loginform-username").val() ==""){
            alert('用户名不能为空');
             $("#loginform-username").focus();
            return false;
        }
        if ($("#loginform-password").val() ==""){
            alert('用户名不能为空');
             $("#loginform-password").focus();
            return false;
        }
        return true;
    }

    $("#submit").on('click', function(){
        login();
    });
    $(function(){
        document.onkeydown = function(e){ 
            var ev = document.all ? window.event : e;
            if(ev.keyCode==13) {
                login();
            }
        }
    }); 

    function login(){
        if( checkempty() ){
            $.ajax({
                url: "/site/login",
                type: 'post',
                dataType: 'json',
                data: $("#login-form").serialize(),
                success: function(data){
                    if( data.code == 0 ){
                        layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                    }else{
                        console.log(data);
                        window.location.href=data.content.url;
                    }
                },
                error: function(e){
                    layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                }
            });
        }
    }
</script>