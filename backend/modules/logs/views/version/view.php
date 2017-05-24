<?php 
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->params['breadcrumbs'][] = ['label' => 'VersionLog', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->version_title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'View';
?>
<?=Html::jsFile('@web/public/plug/ueditor/ueditor.config.js') ?>
<?=Html::jsFile('@web/public/plug/ueditor/ueditor.all.min.js') ?>
<?=Html::jsFile('@web/public/plug/ueditor/lang/zh-cn/zh-cn.js') ?>
<div class="common-div">
    <table class="sui-table table-vzebra">
        <tbody>
            <tr>
                <td>标题：</td>
                <td><?=$model->version_title;?></td>
            </tr>
            <tr>
                <td>版本号：</td>
                <td><?=$model->version_number;?></td>
            </tr>
            <tr>
                <td colspan="2">
                    <script id="VersionSearch[version_log]" type="text/plain" name="VersionSearch[version_log]" style="width:800px;height:300px;"><?=$model->version_log;?></script>
                </td>
            </tr>
        </tbody>
      </table>
</div>
<script type="text/javascript">
    var ue = UE.getEditor('VersionSearch[version_log]');
</script>