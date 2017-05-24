<?php 
namespace backend\modules\projects\controllers;

use backend\controllers\SiteController;

use backend\modules\projects\models\Projects;
use backend\modules\users\models\Users;
use common\models\Servers;
//DML及DDL模型
use common\models\ExecuteLogs;
//查询模型
use common\models\QueryLogs;

use vendor\twl\tools\utils\Output;
use Yii;
/**
* 项目脚本
*/
class ScriptsController extends SiteController
{
    public function actionIndex()
    {
        //获取服务器列表
        $Servers = new servers();
        $server_list = $Servers::find()->where(['status' => 1])->orderBy('server_id')->asArray()->all();
        $default_host = \Yii::$app->session->get('default_host');
        if(!empty($default_host)){
            foreach($server_list as $key=>$val){
                if($default_host != $val['ip'])
                    unset($server_list[$key]);
            }
        }

        $project_list = Projects::find()->where(['status' => 1])->orderBy('pro_id')->asArray()->all();
        $default_pro_id = \Yii::$app->session->get('default_pro_id');
        if(!empty($default_pro_id)){
            foreach($project_list as $key=>$val){
                if($default_pro_id != $val['pro_id'])
                    unset($project_list[$key]);
            }
        }
        //传给视图
        $data['server_list'] = $server_list;
        $data['project_list'] = $project_list;
        $data['server_ids'] = $this->servers['server_ids'];
        //$data['user_dbs'] = $this->servers['db_names'];
        $data['user_dbs'] = array();
        $data['db_name_array'] = json_encode($this->servers['db_name_array']);

        return $this->render('index', $data);
    }

    public function actionGetScriptList(){
        $result = $this->getList();
        //var_dump($result);exit;
        Output::success("成功", $result);
    }

    public function actionExport()
    {
        $result = $this->getList();
        if ( empty( $result ) ) {
            Output::success("查询数据为空！");
        } else {
            $this->export_to_excel($result);
        }
        //var_dump($result);exit;
    }

    public function getList()
    {
        @$project_id = rtrim($_REQUEST['Project']);
        @$server_id = rtrim($_REQUEST['server_id']);
        @$database = rtrim($_REQUEST['database']);
        @$action = rtrim($_REQUEST['action']);
        @$start_time = rtrim($_REQUEST['start_time']);
        @$end_time = rtrim($_REQUEST['end_time']);


        $logs = new ExecuteLogs;

        $user_id = Yii::$app->users->identity->id;
        $result = array();
        
        if( !isset( $project_id ) ){
            Output::error("项目必选！");
        }
        if( empty( $server_id ) ){
            Output::error("环境必选！");
        }

        if( $action == '2' ){
            $type = 'DML';
        }elseif( $action == '3' ){
            $type = 'DDL';
        }
        $request = $logs::find()->select(['log_id','script','notes', 'host', 'database', 'pro_id', 'user_id', 'created_date'])->where(['server_id' => $server_id]);

        if( $project_id != 0 ){
            $request->andWhere(['pro_id' => $project_id]);
        }
        if( !empty( $database ) ){
            $request->andWhere(['database' => $database]);
        }
        if( $action != '1' ){
            $request->andWhere(['sqloperation' => $type]);
        }
        if( !empty($start_time) ){
            $request->andWhere(['>=', 'created_date', $start_time]);
        }
        if( !empty($end_time) ){
            $request->andWhere(['<=', 'created_date', $end_time]);
        }
        $result = $request->orderBy('created_date desc')->asArray()->all();

        foreach ($result as $key => $value) {
            $pro_id = $value['pro_id'];
            $user_id = $value['user_id'];
            $project = Projects::find()->select(['name'])->where(['pro_id' => $pro_id])->asArray()->one();
            $user = Users::find()->select(['username', 'chinesename'])->where(['id' => $user_id])->asArray()->one();
            $result[$key]['project_name'] = $project['name'];
            $result[$key]['username'] = $user['chinesename'] ? $user['chinesename']: $user['username'];
        }
        return $result;
    }

    /**
     * 项目脚本导出
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function export_to_excel( $data )
    {
        $objExcel = new \PHPExcel();

        $objExcel->getProperties()->setCreator("office 2003 excel");
        $objExcel->getProperties()->setLastModifiedBy("office 2003 excel");
        $objExcel->getProperties()->setTitle("Office 2003 XLS Test Document");
        $objExcel->getProperties()->setSubject("Office 2003 XLS Test Document");
        $objExcel->getProperties()->setDescription("Test document for Office 2003 XLS, generated using PHP classes.");
        $objExcel->getProperties()->setKeywords("office 2003 openxml php");
        $objExcel->getProperties()->setCategory("Test result file");
        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        /*$table_name = $argv[1]; //表名
        $title_array = json_decode($argv[2]);  // 表内容标题
        $list_array = json_decode($argv[3]);  // 表内容*/

        $objActSheet->setTitle("SQL脚本查询导出");//设置当前sheet

        $excel_key = array("A", "B", "C", "D", "E", "F");


        $objActSheet->setCellValue("A1", "数据库名");//设置表标题
        $objActSheet->setCellValue("B1", "SQL语句");//设置表标题
        $objActSheet->setCellValue("C1", "SQL注释");//设置表标题
        $objActSheet->setCellValue("D1", "项目名称");//设置表标题
        $objActSheet->setCellValue("E1", "执行时间");//设置表标题
        $objActSheet->setCellValue("F1", "执行人");//设置表标题

        if ( !empty( $data ) ) {
            $j = 2;
            foreach ($data as $key => $value) {
                $objActSheet->setCellValue("A" . $j, $value['database']);
                $objActSheet->setCellValue("B" . $j, $value['script']);
                $objActSheet->setCellValue("C" . $j, $value['notes']);
                $objActSheet->setCellValue("D" . $j, $value['project_name']);
                $objActSheet->setCellValue("E" . $j, $value['created_date']);
                $objActSheet->setCellValue("F" . $j, $value['username']);
                $j++;
            }
        }

        // 设置页方向和规模
        $objActSheet->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objActSheet->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

        //生成EXCEL文档
        $username = Yii::$app->users->identity->username;
        $data_time = date("YmdHis");
        $excelName = $username . "_";
        header('Content-Type: application/vnd.ms-excel');
        header('Cache-Control: max-age=0');
        header( 'Content-Disposition: attachment; filename='.iconv("utf-8", "GBK", $excelName).'.xls');
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $objWriter->save("php://output");
    }
}