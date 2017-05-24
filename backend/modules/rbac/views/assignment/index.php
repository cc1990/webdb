<?php

use yii\helpers\Html;
use yii\helpers\Url;
//use yii\bootstrap\Modal;
//use kartik\grid\GridView;
use yii\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use common\models\User;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel mdm\admin\models\searchs\Menu */
$this->title = Yii::t('rbac', 'User Assignment');
$this->params['breadcrumbs'][] = $this->title;
?>

<style type="text/css">
table{

    table-layout:fixed; 
}
td{
    
    text-overflow: clip;
    white-space: nowrap;
    overflow: hidden;
}
.sui-table thead input{ width: 100%; }
.sui-table .action-column{width: 50px;}

</style>

<?php
//CrudAsset::register($this);
?>

<div class="assignment-index div-index">
    <div id="ajaxCrudDatatable">
        <?=GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            //'columns' => require __DIR__ . '/_columns.php',
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'headerOptions' => ['width' => '40']
                ],
                [
                    'attribute' => Yii::$app->getModule('rbac')->userModelIdField,
                    'headerOptions' => ['width' => '170']
                ],
                [
                    'attribute' => Yii::$app->getModule('rbac')->userModelLoginField,
                    'headerOptions' => ['width' => '170']
                ],
                [
                    'attribute' => Yii::$app->getModule('rbac')->userModelChinesenameField,
                    'headerOptions' => ['width' => '170']
                ],
                [
                    'attribute' => Yii::$app->getModule('rbac')->userModelDepartmentsField,
                    'headerOptions' => ['width' => '170']
                ],
                [
                    'attribute' => Yii::$app->getModule('rbac')->userModelRolesField,
                    'label' => 'Roles',
                    'content' => function($model) {
                        $authManager = Yii::$app->authManager;
                        $idField = Yii::$app->getModule('rbac')->userModelIdField;
                        $roles = [];
                        foreach ($authManager->getRolesByUser($model->{$idField}) as $role) {
                           $roles[] = $role->name;
                        }
                        if(count($roles)==0){
                            return Yii::t("yii","(未设置)");
                        }else{
                            return implode(",", $roles);
                        }
                    },
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => '操作',
                    'template' => '{update}',
                    'buttons' => [
                        'update' => function( $url, $searchModel, $key ){
                            return Html::a('<i class="iconfont">&#xe629;</i>',
                                'javascript:;',
                                ['class' => 'update', 'data-id' => $key, 'data-name' => $searchModel->username]
                            );
                        }
                    ]
                ],
            ]
        ])?>
    </div>
</div>
<?=Html::jsFile('@web/public/plug/layer/layer.js') ?>
<script type="text/javascript">
    $(".update").on("click", function(){
        var id = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        layer.open({
            type: 2,
            title: 'User： '+name,
            shadeClose: true,
            shade: 0.8,
            area: ['600px', '90%'],
            content: "<?=Url::to(['assignment?id=']) ?>"+id, //iframe的url
        });
    });
</script>
