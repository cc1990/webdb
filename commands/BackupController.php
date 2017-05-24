<?php
    namespace app\commands;

    use app\backend\server\RedisServer;
    use app\backend\server\DbServerApp as DbServer;
    use yii\base\Exception;
    use yii\console\Controller;
    use Yii;

    /**
     * @description 数据备份类，定时执行
    */
    class BackupController extends Controller
    {
        /**
         * @description 用于codemirror的数据库结构备份，备份数据库、表和字段的名称
        */
        function actionCodemirror()
        {
            set_time_limit(0);
            $serverList = Yii::$app->getDb()->createCommand("select * from servers;")->queryAll();
            $pingresult = exec("nc -n -w1 " . Yii::$app->redis->hostname . " " . Yii::$app->redis->port, $outcome, $status);
            foreach($serverList as $value){
                try {
                    $this->_getData($value['ip'], $status);
                }catch(Exception $e){
                    continue;
                }
            }
        }

        /**
         * @description 根据ip获取服务器相关内容
        */
        private function _getData($ip, $status)
        {
            $redisServer = new RedisServer();
            $dbServer = new DbServer();
            $server_status = $this->_ping($ip);
            if($server_status == 0) {
                try {
                    $conn = $this->_connectDB($ip);
                    //获取数据库列表
                    $databases = $conn->createCommand("show databases;")->queryAll();
                    $databaseArr = [];
                    foreach($databases as $value){
                        $database = current($value);
                        //获取表数据
                        $conn->createCommand("use `{$database}`")->execute();
                        if(!in_array($database,["information_schema","mysql","performance_schema"])){
                            $databaseArr[] = $database;
                            $tables = $conn->createCommand("show tables")->queryAll();
                            $tableArr = [];
                            foreach($tables as $value2){
                                $table = current($value2);
                                $tableArr[] = $table;
                                try {
                                    $columns = $conn->createCommand("describe `{$table}`")->queryAll();
                                }catch(Exception $e){
                                    continue;
                                }
                                $columnArr = [];
                                foreach($columns as $value3){
                                    $column = $value3['Field'];
                                    $columnArr[] = $column;
                                }
                                if(!empty($columnArr)){
                                    $dbServer->hmset($ip,$database,$table,$columnArr);
                                    if( $status == 0 ){
                                        $redisServer->hmset($ip,$database,$table,$columnArr);
                                    }
                                }
                            }
                            if(!empty($tableArr)){ 
                                $dbServer->hmset($ip,$database,"tables",$tableArr);
                                if( $status == 0 ){
                                    $redisServer->hmset($ip,$database,"tables",$tableArr);
                                }
                            }
                        }
                    }
                    if(!empty($databaseArr)) {
                        $dbServer->hmset($ip,"databases",$databaseArr);
                        if( $status == 0 ){
                            $redisServer->hmset($ip,"databases",$databaseArr);
                        }
                    }
                    return [];
                }catch(Exception $e){
                    return [$e->getMessage()];
                }
            }else{
                return ["连接失败"];
            }
        }

        /**
         * 检查服务器是否连通
         * @return [type] [description]
         */
        private function _ping($server_ip)
        {
            $status = -1;
            if (strcasecmp(PHP_OS, 'WINNT') === 0) {
                // Windows 服务器下
                exec("ping -n 1 {$server_ip}", $outcome, $status);
            } elseif (strcasecmp(PHP_OS, 'Linux') === 0) {
                // Linux 服务器下
                exec("nc -n -w1 $server_ip 3306", $outcome, $status);
            }
            return $status;
        }

        /**
         * @descriptoin 数据库连接
        */
        private function _connectDB($server_ip,$db_name = 'mysql')
        {
            //组合数据库配置
            $db_name = $db_name == '*' ? 'mysql' :  $db_name;
            $server_ip_arr = explode(":",$server_ip);
            $ip = $server_ip_arr[0];
            $port = isset($server_ip_arr[1]) ? $server_ip_arr[1] : 3306;
            $connect_config['dsn'] = "mysql:host={$ip};port={$port};";
            $connect_config['username'] = "php";
            $connect_config['password'] = "phpmysqldb2016";
            $connect_config['charset'] = "utf8";

            //数据库连接对象
            $executeConnection = new \yii\db\Connection((Object)$connect_config);
            return $executeConnection;
        }

    }
?>