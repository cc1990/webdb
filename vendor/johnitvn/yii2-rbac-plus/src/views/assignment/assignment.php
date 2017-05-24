<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Url;

/** Get all roles */
$authManager = Yii::$app->authManager;


$this->params['breadcrumbs'][] = "";
?>

<link rel="stylesheet" type="text/css" href="http://g.alicdn.com/sj/dpl/1.5.1/css/sui.min.css">

<div class="user-assignment-form" id="htmlpage" >
<?php $form = ActiveForm::begin(['id' => 'roleform', 'options' => ['class' => 'sui-form', ]]); ?>
    <?= Html::activeHiddenInput($formModel, 'userId')?>
    <input type="hidden" name="AssignmentForm[roles]" value="">
    <table class="sui-table table-bordered table-zebra">
        <thead>
            <tr>
                <th style="width:30px"><input class="check_all" type="checkbox" name="" ></th>
                <th style="width:150px">Name</th>
                <th>Description</th>
            </tr>
            <tr id="w0-filters" class="filters">
                <td>&nbsp;</td>
                <td><input type="text" class="input-fat" name="RoleSearch[name]" style="height: 25px;"></td>
                <td><input type="text" class="input-fat" name="RoleSearch[description]" style="height: 25px;"></td>
            </tr>
        <tbody id="assignment">            
            <?php foreach ($authManager->getRoles() as $role): ?>
                <tr>
                    <?php
                        $checked = true;
                        if($formModel->roles==null||!is_array($formModel->roles)||count($formModel->roles)==0){
                            $checked = false;
                        }else if(!in_array($role->name, $formModel->roles) ){
                            $checked = false;
                        }                        
                    ?>
                    <td><input <?= $checked? "checked":"" ?>  type="checkbox" name="AssignmentForm[roles][]" value="<?= $role->name?>"></td>
                    <td><?= $role->name ?></td>
                    <td><?= $role->description ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!Yii::$app->request->isAjax) { ?>
    <div class="form-group">
    <?= Html::Button(Yii::t('rbac', 'Update'), ['class' => 'sui-btn btn-xlarge btn-info', 'id' => 'submit']) ?>
    </div>
    <?php } ?>
<?php ActiveForm::end(); ?>
<?=Html::jsFile('@web/public/js/jquery.min.js') ?>
<?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
<script type="text/javascript">
    $(".check_all").bind("click", function () {
        $("[name = 'AssignmentForm[roles][]']:checkbox").prop("checked", this.checked);
    });

    $("input[name='RoleSearch[name]']").focusout(function(){
        search();
    });

    $("input[name='RoleSearch[description]']").focusout(function(){
        search();
    });

    $("input[name='RoleSearch[name]']").keyup(function(){
        search();
    });

    $("input[name='RoleSearch[description]']").keyup(function(){
        search();
    });

    function search(){
        var name = $("input[name='RoleSearch[name]']").val();
        var description = $("input[name='RoleSearch[description]']").val();

        $("#assignment tr").each(function(){
            var name_text = $(this).children().next().html();
            var description_text = $(this).children().next().next().html();

            if( name != '' && description == '' ){
                if ( name_text.indexOf(name) >= 0 ) {
                    $(this).show();
                }else{
                    $(this).hide();
                }
            }else if( name == '' && description != '' ){
                if ( description_text.indexOf(description) >= 0 ) {
                    $(this).show();
                }else{
                    $(this).hide();
                }
            }else if( name != '' && description != '' ){
                if ( name_text.indexOf(name) >= 0 && description_text.indexOf(description) >= 0 ) {
                    $(this).show();
                }else{
                    $(this).hide();
                }
            }else{
                $(this).show();
            }
            
        });


    }

    $("#submit").on('click', function(){
        var index = parent.layer.getFrameIndex(window.name); 
        $.ajax({
            url: $("#roleform").attr("action"),
            type: 'post',
            dataType: 'json',
            data: $("#roleform").serialize(),
            success: function(data){
                parent.layer.msg('保存成功', {time: 2000, icon:6});
                parent.layer.close(index);
                parent.location.reload();
            },
            error: function(e){
                parent.layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                parent.layer.close(index);
            }
        })
    })
</script>
</div>