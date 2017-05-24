<?php
    namespace backend\modules\servers\controllers;

    use common\models\Servers;
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
            $serversModel = new Servers();
            $serverList = $serversModel->find()->where(["status"=>1])->asArray()->all();
            $data = [];
            foreach($serverList as $value){
                $data[$value['ip']] = $this->_getData($value['ip']);
            }
            file_put_contents("./codemirror/data/data.json",json_encode($data));
        }

        /**
         * @description 根据ip获取服务器相关内容
        */
        private function _getData($ip)
        {
            $status = $this->_ping($ip);
            $return = [];
            if($status == 0) {
                try {
                    $conn = $this->_connectDB($ip);
                    //获取数据库列表
                    $databases = $conn->createCommand("show databases;")->queryAll();
                    foreach($databases as $value){
                        $database = current($value);
                        //获取表数据
                        $conn->createCommand("use {$database}")->execute();
                        if(!in_array($database,["information_schema","mysql","performance_schema"])){
                            $return[$database] = [];
                            $tables = $conn->createCommand("show tables")->queryAll();
                            foreach($tables as $value2){
                                $table = current($value2);
                                $return[$database][$table] = [];
                                $columns = $conn->createCommand("describe {$table}")->queryAll();
                                foreach($columns as $value3){
                                    $column = $value3['Field'];
                                    $return[$database][$table][$column] = true;
                                }
                            }
                        }
                    }
                    return $return;
                }catch(Exception $e){
                    return [];
                }
            }else{
                return [];
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
                exec("nc -nvv -w2 $server_ip 3306", $outcome, $status);
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
            $connect_config['dsn'] = "mysql:host={$ip};port={$port};dbname={$db_name}";
            $connect_config['username'] = Yii::$app->params['ADMIN_USER'];
            $connect_config['password'] = Yii::$app->params['ADMIN_PASSWD'];
            $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];

            //数据库连接对象
            $executeConnection = new \yii\db\Connection((Object)$connect_config);
            return $executeConnection;
        }

    }
?>