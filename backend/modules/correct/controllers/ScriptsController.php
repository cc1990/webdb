<?php 
namespace backend\modules\correct\controllers;

use Yii;

use backend\controllers\SiteController;

use common\models\SelfHelp;
use backend\modules\correct\models\SelfHelpSearch;

use vendor\twl\tools\utils\Output;

class ScriptsController extends SiteController
{
    public function actionIndex()
    {
        $searchModel = new SelfHelpSearch();

        $dataProvider = $searchModel->search( Yii::$app->request->queryParams );
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionView($id){
        $model = SelfHelp::findOne($id);
        $workorder_no = $model->workorder_no;

        $data['list'] = SelfHelp::find()->where(['workorder_no' => $workorder_no])->asArray()->all();
        return $this->renderAjax('view', $data);
    }

    public function actionDownload($id)
    {
        $model = SelfHelp::findOne($id);

        if( $model->backup_status != '3' ){
            echo "该脚本未备份成功";
            exit;
        }

        $workorder_no = $model->workorder_no;
        $server_ip = $model->server_ip;
        $db_name = $model->db_name;
        $tb_name = $model->tb_name;
        $where = $model->where;

        $file_name = $server_ip . "_" . $db_name . "_" . $tb_name . "_" . md5( $workorder_no . $db_name . $tb_name . $where ) . ".sql";
        $file_dir = "/data/self_correct_dump/" . $model->workorder_no . "/";
        if (! file_exists ( $file_dir . $file_name )) {    
            echo "未找到脚本备份的文件";
            exit;
        } else {    
            //打开文件    
            $file = fopen ( $file_dir . $file_name, "r" );    
            //输入文件标签     
            Header ( "Content-type: application/octet-stream" );    
            Header ( "Accept-Ranges: bytes" );    
            Header ( "Accept-Length: " . filesize ( $file_dir . $file_name ) );    
            Header ( "Content-Disposition: attachment; filename=" . $file_name );    
            //输出文件内容     
            //读取文件内容并直接输出到浏览器    
            echo fread ( $file, filesize ( $file_dir . $file_name ) );    
            fclose ( $file );    
            exit;
        }  
    }
}