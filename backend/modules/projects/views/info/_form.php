<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
?>
<?=Html::jsFile('@web/public/plug/My97DatePicker/WdatePicker.js') ?>
<div>
    <?php $form = ActiveForm::begin(['options' => ['class' => 'sui-form']]);?>
    <div class="form-group field-projectsinfo-db_name projectbox" style="height: 55px;">
        <label class="control-label" for="projectsinfo-db_name">选择关联项目</label>
        <select class="projects" name="ProjectsInfo[pro_id]" id="projects" style="width: 500px; display: none;">
            <option value='0'>---请选择项目---</option>
        <?php foreach ($data['projects'] as $key => $value) { ?>
            <option value="<?=$value['pro_id']?>" <?php echo ($value['pro_id'] == $model->pro_id) ? 'selected' : '';?>><?=$value['name']?><?php if( !empty( $value['title'] ) ){ echo "（".$value['title']."）";} ?></option>
        <?php } ?>
        </select>
        <div class="sui-btn-group select-absolute" name="select_project" >
        <?php if( empty($model->pro_id) ){ ?>
            <button data-toggle="dropdown" class="sui-btn dropdown-toggle" value="0" style="width: 500px;text-align: left;"><i class="caret"></i>---请选择项目---</button>
        <?php }else{ ?>
            <?php foreach ($data['projects'] as $v){ 
                if( $v['pro_id'] == $model->pro_id ){
                    $pro_id = $v['pro_id'];
                    $pro_name = $v['name'];
                    if( !empty( $v['title'] ) ){
                        $pro_name .= "（".$v['title']."）";
                    }
                }
            }?>
            <button data-toggle="dropdown" class="sui-btn dropdown-toggle" value="<?=$model->pro_id?>" style="width: 500px;text-align: left;"><i class="caret"></i><?=$pro_name?></button>
        <?php } ?>
            <ul role="menu" aria-labelledby="drop1" class="sui-dropdown-menu dropdown-scroll" style="width: 100%;">
                <li name="search_project"><input type="text" placeholder="项目检索" class="input-default input-xxlarge" value=""></li>
                <li role="presentation"><a role="menuitem" tabindex="-1" href="#" data-pro_id="0">---请选择项目---</a></li>
                <?php foreach ($data['projects'] as $v):?>
                    <li role="presentation"><a role="menuitem" tabindex="-1" href="#" data-pro_id="<?=$v['pro_id']?>"><?=$v['name']?><?php if( !empty( $v['title'] ) ){ echo "（".$v['title']."）";} ?></a></li>
                <?php endforeach;?>
            </ul>
        </div>
        <div class="help-block"></div>  
    </div>

    
    <?= $form->field( $model, 'pro_name' )->textInput(['class' => 'input-xxlarge input-xfat'])?>
    
    <?= $form->field( $model, 'server_ip' )->textInput(['class' => 'input-xxlarge input-xfat'])?>
    <?= $form->field( $model, 'test_trunck_date' )->textInput(['onFocus' => 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm",isShowClear:false,readOnly:true,isShowWeek:true})', 'class' => 'input-xxlarge input-xfat'])?>
    <?= $form->field( $model, 'pre_date' )->textInput(['onFocus' => 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm",isShowClear:false,readOnly:true,isShowWeek:true})', 'class' => 'input-xxlarge input-xfat'])?>
    <?= $form->field( $model, 'pro_date' )->textInput(['onFocus' => 'WdatePicker({dateFmt:"yyyy-MM-dd HH:mm",isShowClear:false,readOnly:true,isShowWeek:true})', 'class' => 'input-xxlarge input-xfat'])?>
    <?= $form->field( $model, 'remark' )->textarea(['rows' => 5, 'cols' => 79])?>
    <?php if ( !$model->isNewRecord ) { ?>
    <div class="form-group field-projectsinfo-is_create_history">
        <input type="hidden" name="is_create_history" value="0"><label><input type="checkbox" id="projectsinfo-is_create_history" name="is_create_history" value="1" > 是否创建历史信息</label>
        <div class="help-block"></div>
    </div>
    <?php } ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'sui-btn btn-xlarge btn-success' : 'sui-btn btn-xlarge btn-info']) ?>
    </div>
    <?php ActiveForm::end();?>
</div>

<script type="text/javascript">
    $(window).ready(function(){
        if( $(".projects").val() != 0 ){
            $(".field-projectsinfo-pro_name").hide();
        }
    });
    $(".projects").change(function(){
        if( $(".projects option:selected").val() == 0 ){
            $(".field-projectsinfo-pro_name").show();
        }else{
            $(".field-projectsinfo-pro_name").hide();
        }
    });

    //关闭项目检索的click事件
    $(".projectbox").on('click',"[name='select_project'] ul [name='search_project']",function(){
        return false;
    });

    //项目检索事件
    $(".projectbox").on('keyup',"[name='select_project'] [name='search_project']",function(){
        var search = $(this).find("input").val();
        $(this).parent().parent().find('li').each(function(i){
            var name = $(this).find("a").first().text();
            if(name != '') {
                if (name.indexOf(search) === -1) {
                    $(this).removeClass("show");
                    $(this).addClass("hide");
                } else {
                    $(this).removeClass("hide");
                    $(this).addClass("show");
                }
            }
        });
    });

    //选中事件
    $('.projectbox').on('click',"[name='select_project'] ul li a",function(){
        if( $(this).attr('data-pro_id') == 0 ){
            $(".field-projectsinfo-pro_name").show();
        }else{
            $(".field-projectsinfo-pro_name").hide();
        }
        $(".projects").val($(this).attr('data-pro_id'));
        $(this).parent().parent().prev().val($(this).attr('data-pro_id'));
        $(this).parent().parent().prev().html('<i class="caret"></i>' + $(this).html());
    });
</script>
