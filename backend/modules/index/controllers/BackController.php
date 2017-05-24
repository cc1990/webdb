<?php 

namespace backend\modules\index\controllers;

use backend\modules\index\models\RighterLogs;
use Yii;
use backend\controllers\SiteController;
use backend\modules\index\models\BackupLogs;
use common\models\Servers;
use yii\base\Exception;


class BackController extends SiteController
{
    private $_serversModel = '';
    private $_backupLogsModel = '';
    private $_return = [];
    /**
     * @description 初始化函数
    */
    public function beforeAction($action)
    {
        $this->_serversModel = new Servers();
        $this->_backupLogsModel = new BackupLogs();
        $this->_return = [
            "status"    =>  0,
            "msg"   =>  '系统维护中'
        ];
        return parent::beforeAction($action);
    }

    /**
     * @description 数据备份首页，记录展示
    */
    public function actionIndex()
    {
        //获取服务列表
        $servers_list = $this->_serversModel->find()->where(["status"=>1])->asArray()->all();

        //获取日志列表
        $page = Yii::$app->request->getQueryParam("page",1);
        $pageSize = Yii::$app->request->getQueryParam("per-page",20);
        $where = [];
        $andWhere = ["and"];
        if(Yii::$app->request->getQueryParam("server_id",0)){
            $where['server_id'] = Yii::$app->request->getQueryParam("server_id");
        }
        if(Yii::$app->request->getQueryParam("backupSearchHost",0)){
            $andWhere[] = ['like','host',Yii::$app->request->getQueryParam("backupSearchHost")];
        }
        if(Yii::$app->request->getQueryParam("databases",0)){
            $andWhere[] = ['like','databases',Yii::$app->request->getQueryParam("databases")];
        }
        if(Yii::$app->request->getQueryParam("table",0)){
            $andWhere[] = ['like','tables',Yii::$app->request->getQueryParam("table")];
        }
        if(Yii::$app->request->getQueryParam("where",0)){
            $andWhere[] = ['like','where',Yii::$app->request->getQueryParam("where")];
        }
        $template_list = $this->_backupLogsModel->find()->where(['template'=>1])->asArray()->all();
        $logs_list = $this->_backupLogsModel->find()->where($where)->andWhere($andWhere)->orderby('id desc')->limit($pageSize)->offset(($page-1)*$pageSize)->asArray()->all();
        $totalCount = $this->_backupLogsModel->find()->where($where)->andWhere($andWhere)->count();
        $pageHtml = $this->_getPageHtml($totalCount,$page,$pageSize);

        //获取订正列表
        $righterLogsModel = new RighterLogs();
        $righterPage = Yii::$app->request->getQueryParam("righter-page",1);
        $righterPageSize = Yii::$app->request->getQueryParam("righter-per-page",20);
        $righterLogList = $righterLogsModel->find();
        $whereRighter = ["and"];
        if(Yii::$app->request->getQueryParam("righterSearchHost",0)){
            $whereRighter[] = ['like','host',Yii::$app->request->getQueryParam("righterSearchHost",0)];
        }
        if(!empty($whereRighter))    $righterLogList = $righterLogList->where($whereRighter);
        $righterTotalCount = $righterLogList->count();
        $righterPageHtml = $this->_getPageHtml($righterTotalCount,$righterPage,$righterPageSize,"righter-page");
        $righterLogList = $righterLogList->orderBy("id desc")->limit($righterPageSize)->offset(($righterPage-1)*$righterPageSize)->asArray()->all();

        return $this->render("/back/index",[
            "servers_list"  =>  $servers_list,
            "logs_list"  =>  $logs_list,
            "righterLogList"    =>  $righterLogList,
            "template_list" =>  $template_list,
            "backupPageHtml"  =>  $pageHtml,
            "righterPageHtml"   =>  $righterPageHtml,
            "search"    =>  Yii::$app->request->getQueryParams()
        ]);
    }

    /**
     * @description 获取数据库列表
    */
    public function actionGetDatabases($host)
    {
        try {
            $conn = $this->_connectDb($host, 'mysql');
            $result = $conn->createCommand("show databases")->queryAll();
            $database_arr = [];
            foreach ($result as $value) {
                if(in_array(current($value),Yii::$app->params['filter_databases'])){
                    continue;
                }else {
                    $database_arr[] = current($value);
                }
            }
            $this->_return['status'] = 1;
            $this->_return['msg'] = "数据获取成功";
            $this->_return['data'] = $database_arr;
            return json_encode($this->_return);
        }catch (Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }

    /**
     * @description 获取表列表
    */
    public function actionGetTables($host,$database)
    {
        if(empty($host) || empty($database)){
            $this->_return['msg'] = "参数不可为空";
            return json_encode($this->_return);
        }
        try {
            $conn = $this->_connectDb($host, 'mysql');
            $conn->createCommand("use `{$database}`")->execute();
            $result = $conn->createCommand("show tables;")->queryAll();
            $database_arr = [];
            foreach ($result as $value) {
                if(in_array(current($value),Yii::$app->params['filter_databases'])){
                    continue;
                }else {
                    $database_arr[] = current($value);
                }
            }
            $this->_return['status'] = 1;
            $this->_return['msg'] = "数据获取成功";
            $this->_return['data'] = $database_arr;
            return json_encode($this->_return);
        }catch (Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }

    /**
     * @description 数据备份
    */
    public function actionSave()
    {
        $host = Yii::$app->request->post("host",'');
        $databases = Yii::$app->request->post("databases",'');
        $tables = Yii::$app->request->post("tables",'');
        $where = Yii::$app->request->post("where",'');
        $job_number = Yii::$app->request->post("job_number",'');
        $type = Yii::$app->request->post("type",'save');
        if($host == ''){
            $this->_return['msg'] = '请选择服务器';
            return json_encode($this->_return);
        }
        if($databases == ''){
            $this->_return['msg'] = '请选择备份数据库';
            return json_encode($this->_return);
        }
        if ($tables == '') {
            $this->_return['msg'] = '请选择备份表';
            return json_encode($this->_return);
        }
        try {
            $this->_backupLogsModel->host = $host;
            $this->_backupLogsModel->databases = $databases;
            $this->_backupLogsModel->tables = $tables;
            $this->_backupLogsModel->where = $where;
            $this->_backupLogsModel->job_number = $job_number;
            $this->_backupLogsModel->create_time = time();
            $this->_backupLogsModel->save();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '数据保存成功';
            if ($type == 'backup') {  //数据备份
                $status = $this->_backup($host,$databases,$tables,$where,$job_number);
                if($status == 0) {
                    $this->_return['status'] = 1;
                    $this->_return['msg'] = "备份成功！";
                }else{
                    $this->_return['msg'] = "数据保存成功，但备份失败！";
                }
            }
            return json_encode($this->_return);
        }catch (Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }

    /**
     * @description 开始备份数据
    */
    private function _backup($host,$databases,$tables,$where,$job_number)
    {
        $filename = date("YmdHis",time())."_".$host."_".Yii::$app->users->identity->username."_".$databases."_".$job_number.".sql";
        if($databases == '*'){
            $sql = "--all-databases";
        }else{
            $database_arr = array_filter(explode(",",$databases));
            if(count($database_arr) > 1 || $tables == '*'){
                $database_str = implode(" ",$database_arr);
                $sql = "--databases {$database_str}";
            }else{
                $tables_arr = array_filter(explode(",",$tables));
                if(count($tables_arr) == 1){
                    if(empty($where)) {
                        $sql = "{$database_arr[0]} {$tables_arr[0]}";
                    }else{
                        $sql = "{$database_arr[0]} {$tables_arr[0]} --where {$where}";
                    }
                }else{
                    $tables_str = implode(" ",$tables_arr);
                    $sql = "--databases {$database_arr[0]} --tables {$tables_str}";
                }
            }
        }
        exec("nohup sh /data/scripts/php_webdb_mysqldump.sh {$host} '{$sql}' $filename&",$outcome,$status);
        return $status;
    }

    /**
     * @description 模版设置
    */
    public function actionTemplate($id,$template_name)
    {
        $logInfo = $this->_backupLogsModel->findOne($id);
        $logInfo->template_name = $template_name;
        $logInfo->template = 1;
        $logInfo->save();
        $this->_return['status'] = 1;
        $this->_return['msg'] = '设置成功';
        return json_encode($this->_return);
    }

    /**
     * @descripton 数据备份
    */
    public function actionBack($id)
    {
        $logInfo = $this->_backupLogsModel->findOne($id)->toArray();
        $status = $this->_backup($logInfo['host'],$logInfo['databases'],$logInfo['tables'],$logInfo['where'],$logInfo['job_number']);
        if($status == 0) {
            $this->_return['status'] = 1;
            $this->_return['msg'] = "备份成功！";
        }else{
            $this->_return['msg'] = "数据保存成功，但备份失败！";
        }
        return json_encode($this->_return);
    }

    /**
     * @description 数据订正
    */
    public function actionRighter($outfile_sql,$ip)
    {
        $righterModel = new RighterLogs();
        try {
            $conn = $this->_connectDb($ip);
            $conn->createCommand($outfile_sql)->execute();
            $righterModel->sql = addslashes($outfile_sql);
            $righterModel->host = $ip;
            $righterModel->template = 0;
            $righterModel->create_time = time();
            $righterModel->save();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '订正成功';
            return json_encode($this->_return);
        }catch(Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }

    /**
     * 连接数据库
     * @param  string 服务器地址
     * @param  string $db_name
     * @return [type]
     */
    protected function _connectDb($server_ip,$db_name = 'mysql'){
        //组合数据库配置
        $db_name = $db_name == '*' ? 'mysql' :  $db_name;
        $server_ip_arr = explode(":",$server_ip);
        $ip = $server_ip_arr[0];
        $port = isset($server_ip_arr[1]) ? $server_ip_arr[1] : 3306;
        $connect_config['dsn'] = "mysql:host={$ip};port={$port};dbname={$db_name}";
        $connect_config['username'] = Yii::$app->params['BACK_USER'];
        $connect_config['password'] = Yii::$app->params['BACK_PASSWD'];
        $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];

        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }
}
 ?>