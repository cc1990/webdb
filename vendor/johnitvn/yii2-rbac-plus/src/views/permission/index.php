<?php
use yii\helpers\Html;
use yii\helpers\Url;
//use yii\bootstrap\Modal;
//use kartik\grid\GridView;
use yii\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;

/* @var $this yii\web\View */
/* @var $searchModel johnitvn\rbacplus\models\AuthItemSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('rbac','Permisstions Manager');
$this->params['breadcrumbs'][] = $this->title;

//CrudAsset::register($this);
?>
<div class="auth-item-index div-index">
    <div id="ajaxCrudDatatable">
    <p>
        <?= Html::a('Create Permisstions', ['create'], ['class' => 'sui-btn btn-xlarge btn-success']) ?>
    </p>
        <?=GridView::widget([
            'id'=>'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            //'pjax'=>true,
            'columns' => require(__DIR__.'/_columns.php'),
        ])?>
    </div>
</div>
<?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
<script type="text/javascript">
$(".view").on("click", function(){
  var name = $(this).attr('data-name');
  layer.open({
    type: 2,
    title: name,
    shadeClose: true,
    shade: 0.8,
    area: ['500px', '300px'],
    content: "<?=Url::to(['view?name=']) ?>"+name, 
  });
});
   
</script>
