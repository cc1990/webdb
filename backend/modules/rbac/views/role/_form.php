<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
//服务器模型
use common\models\Servers;

$rules = Yii::$app->authManager->getRules();
$rulesNames = array_keys($rules);
$rulesDatas = array_merge(['' => Yii::t('rbac', '(not use)')], array_combine($rulesNames, $rulesNames));

$authManager = Yii::$app->authManager;   
$permissions = $authManager->getPermissions();
$permissions_array = array();
$permissions_controller_array = array();

//从新排列数组

foreach ($permissions as $key => $value) {
    $controller_array = explode("_", $key);
    $controller = $controller_array[0] . "_" . $controller_array[1]; //获取模型_控制器，如，index_batch
    if ( in_array( $controller , $permissions_controller_array) ) {
        if( $controller_array[2] == "index" ){
            $permissions_array[$controller]['second'][$i]['name'] = $permissions_array[$controller]['name'];
            $permissions_array[$controller]['second'][$i]['description'] = $permissions_array[$controller]['description'];

            $permissions_array[$controller]['name'] = $key;
            $permissions_array[$controller]['description'] = $value->description;
        }else{
            $permissions_array[$controller]['second'][$i]['name'] = $key;
            $permissions_array[$controller]['second'][$i]['description'] = $value->description;
        }
        $i++;
    } else {
        $i = 0; 
        $permissions_controller_array[] = $controller;
        $permissions_array[$controller]['name'] = $key;
        $permissions_array[$controller]['description'] = $value->description;
    }
    
}
//var_dump($permissions_array);exit;

//获取服务器列表
$Servers = new servers();
$servers = $Servers->getServers();
$sql_operations = array('DML','DDL','DQL');
$environment_array = array(
    'dev' => '开发',
    'test' => '测试',
    'test_trunk' => '测试主干',
    'pre' => '预发布',
    'pro' => '线上',
    'dev_trunk' => '研发主干',
);
//var_dump($model->sqloperations);exit;
?>

<div class="auth-item-form">

    <?php $form = ActiveForm::begin(['options'=> ['class' => 'sui-form']]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 1, 'style' => 'margin: 0px -1px 0px 0px; height: 34px; width: 490px;']) ?>

    <?= $form->field($model, 'ruleName')->dropDownList($rulesDatas, ['class' => 'select-xfat']) ?>

    <?= $form->field($model, 'dbs')->textInput(['maxlength' => true, 'class' => 'input-xxlarge input-xfat']) ?>

    <div class="form-group field-role-environment">
        <label class="control-label" for="role-environment">分库分表环境</label>
        <div id="role-environment">
            <?php $environment=explode(',', $model->environment); ?>
                <?php foreach ($environment_array as $key => $vo): ?>
                <label><input <?= (in_array( $key, $environment )) ? "checked":"" ?> type="checkbox" name="Role[environment][]" value="<?= $key ?>">&nbsp;<?= $vo ?></label>&nbsp;
                <?php endforeach; ?>
        </div>
        <div class="help-block"></div>
    </div>

    <div class="form-group field-role-servers">
        <label class="control-label" for="role-servers">SQL操作</label>
        <input type="hidden" name="Role[servers]" value="">
        <div id="role-sqloperations">
            <?php $sqloperations=$model->sqloperations; ?>
                <?php foreach ($sql_operations as $operation): ?>
                <label><input <?= (@strpos("$sqloperations",$operation) !== false) ? "checked":"" ?> type="checkbox" name="Role[sqloperations][]" value="<?= $operation ?>">&nbsp;<?= $operation ?></label>&nbsp;
                <?php endforeach; ?>
        </div>
        <div class="help-block"></div>
    </div>

    <div class="form-group field-role-servers">
        <label class="control-label" for="role-servers">Servers</label>
        <input type="hidden" name="Role[servers]" value="">
        <div id="role-servers">
            <table class="sui-table table-bordered table-zebra">
                <thead>
                    <tr>
                        <td style="width:1px"><input class="check_all_server" type="checkbox" name="" ></td>
                        <td style="width:1px"><b>Name</b></td>
                        <td><b>IP</b></td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servers as $server): ?>
                        <tr>
                            <td>
                                <input <?= (is_array($model->servers) && in_array($server->server_id, $model->servers)) ? "checked":"" ?> type="checkbox" name="Role[servers][]" value="<?= $server->server_id ?>">
                            </td>
                            <td ><?= $server->name ?></td>
                            <td style="width:200px"><?= $server->ip ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="help-block"></div>
    </div>

    <div class="form-group field-role-permissions">
        <label class="control-label" for="role-permissions">Permissions</label>
        <input type="hidden" name="Role[permissions]" value="">
        <div id="role-permissions">
            <table class="sui-table table-bordered table-zebra">
                <thead>
                <tr>
                    <td style="width:1px"><input class="check_all" type="checkbox" name="" ></td>
                    <td style="width:250px"><b>Name</b></td>
                    <td><b>Description</b></td>
                </tr>
                </thead>
                <!-- <tbody>
                <?php foreach ($permissions as $permission): ?>
                    <tr>
                        <td>
                            <input <?=(is_array($model->permissions) && in_array($permission->name, $model->permissions)) ? "checked":"" ?> type="checkbox" name="Role[permissions][]" value="<?= $permission->name ?>">
                        </td>
                        <td><?= $permission->name ?></td>
                        <td><?= $permission->description ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody> -->
                <tbody>
                <?php foreach ($permissions_array as $key => $permission): ?>
                    <tr class="<?= $key ?>">
                        <td>
                            <input <?=(is_array($model->permissions) && in_array($permission['name'], $model->permissions)) ? "checked":"" ?> type="checkbox" name="Role[permissions][]" value="<?= $permission['name'] ?>" class="level_one">
                        </td>
                        <td><?= $permission['name'] ?><?php if( !empty( $permission['second'] ) ){ ?><span class="dropdown" style="float: right;">+</span><?php } ?></td>
                        <td><?= $permission['description'] ?></td>
                    </tr>
                    <?php if( !empty( $permission['second'] ) ){ ?>
                    <?php foreach ($permission['second'] as $value): ?>
                        <tr class="<?= $key ?>_level" style="display: none;">
                            <td>
                                <input <?=(is_array($model->permissions) && in_array($value['name'], $model->permissions)) ? "checked":"" ?> type="checkbox" name="Role[permissions][]" value="<?= $value['name'] ?>">
                            </td>
                            <td style="padding-left:30px;"><?= $value['name'] ?></td>
                            <td><?= $value['description'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php } ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="help-block"></div>
    </div>

    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('rbac', 'Save'), ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
<script type="text/javascript">
    $(".check_all").bind("click", function () {
        $("[name = 'Role[permissions][]']:checkbox").prop("checked", this.checked);
    });

    $(".check_all_server").bind("click", function () {
        $("[name = 'Role[servers][]']:checkbox").prop("checked", this.checked);
    });

    $(".dropdown").on("click", function(){
        var controller = $(this).parent().parent().attr("class");
        if( $("."+controller+"_level").is(":hidden") ){
            $("."+controller+"_level").show();
        }else{
            $("."+controller+"_level").hide();
        }
    });

    $(".level_one").on("click", function(){
        var controller = $(this).parent().parent().attr("class");
        $("."+controller+"_level input").prop("checked", this.checked);
    })
</script>