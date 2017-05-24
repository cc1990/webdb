<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\modules\users\models\Users */

$this->title = '密码修改';
if(Yii::$app->users->identity->is_change_passwd == 0){
    $remind = '第一次登陆请修改密码（默认密码：123456）';
}
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-create common-div">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if (isset($remind)): ?><h4 style="color:red"><?= $remind ?></h4><?php endif; ?>
    <?= $this->render('_form_update_password', [
        'model' => $model,
    ]) ?>

</div>
