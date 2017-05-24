<?php 
namespace backend\modules\logs\controllers;

use Yii;
use backend\controllers\SiteController;

use common\models\ExecuteLogs;
use common\models\QueryLogs;

/**
* 脚本日志分析控制器
*/
class ScriptsController extends SiteController
{

    public function actionIndex()
    {
        $connection = Yii::$app->db;

        $select = "select ";
        $column = "* ";
        $from = "from query_logs ";
        $where = "where (server_id in (select server_id from servers where environment = 'pro') or environment = 'pro') ";
        //$where = "where 1=1 ";
        $orderBy = "order by created_date desc ";

        $where_yesterday = "TO_DAYS(NOW( ) ) - TO_DAYS( created_date) = 1 ";//昨天数据
        $where_lastweek = "YEARWEEK(date_format(created_date,'%Y-%m-%d')) = YEARWEEK(now())-1 ";//上周数据
        $where_lastmonth = "PERIOD_DIFF( date_format( now( ) , '%Y%m' ) , date_format( created_date, '%Y%m' ) ) =1 "; //上个月
        $where_30day = "DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= date(created_date) ";//最近30天

        //查询最近30天每天的数据总量
        $select_column = "DATE_FORMAT(created_date,'%m-%d') as day, count(*) as count ";
        $groupBy = "group by day";
        $sql = $select . $select_column . $from . $where . "and " . $where_30day . $groupBy;
        $count = $connection->createCommand($sql)->queryAll();
        $data['count']['thirty_day'] = $count;

        //查询昨天操作最多的数据库
        $select_column = "`database`, count(*) as count ";
        $groupBy = "group by `database` ";
        $orderBy = "order by count desc ";
        $limit = "limit 10 ";
        $sql = $select . $select_column . $from . $where . "and " . $where_yesterday . $groupBy . $orderBy . $limit;
        $data['database']['yesterday'] = $connection->createCommand($sql)->queryAll();
        $sql_count = $select . "count(*) as count " . $from . $where . "and " . $where_yesterday;
        $data['database']['yesterday_count'] = $connection->createCommand($sql_count)->queryOne();

        //查询上周操作最多的数据库
        $sql = $select . $select_column . $from . $where . "and " . $where_lastweek . $groupBy . $orderBy . $limit;
        //echo $sql;exit;
        $data['database']['lastweek'] = $connection->createCommand($sql)->queryAll();
        $sql_count = $select . "count(*) as count " . $from . $where . "and " . $where_lastweek;
        $data['database']['lastweek_count'] = $connection->createCommand($sql_count)->queryOne();
        //查询上个月操作最多的数据库
        $sql = $select . $select_column . $from . $where . "and " . $where_lastmonth . $groupBy . $orderBy . $limit;
        $data['database']['lastmonth'] = $connection->createCommand($sql)->queryAll();
        $sql_count = $select . "count(*) as count " . $from . $where . "and " . $where_lastmonth;
        $data['database']['lastmonth_count'] = $connection->createCommand($sql_count)->queryOne();


        //查询昨天操作最多的用户
        $select_column = "users.chinesename, users.username, user_id, count(*) as count ";
        $groupBy = "group by user_id ";
        $orderBy = "order by count desc ";
        $limit = "limit 10 ";
        $sql = $select . $select_column . $from . " left join users on query_logs.user_id = users.id ". $where . "and " . $where_yesterday . $groupBy . $orderBy . $limit;
        $data['users']['yesterday'] = $connection->createCommand($sql)->queryAll();
        $sql_count = $select . "count(*) as count " . $from . $where . "and " . $where_yesterday;
        $data['users']['yesterday_count'] = $connection->createCommand($sql_count)->queryOne();

        //查询上周操作最多的用户
        $sql = $select . $select_column . $from . " left join users on query_logs.user_id = users.id " . $where . "and " . $where_lastweek . $groupBy . $orderBy . $limit;
        $data['users']['lastweek'] = $connection->createCommand($sql)->queryAll();
        $sql_count = $select . "count(*) as count " . $from . $where . "and " . $where_lastweek;
        $data['users']['lastweek_count'] = $connection->createCommand($sql_count)->queryOne();
        
        //查询上个月操作最多的用户
        $sql = $select . $select_column . $from . " left join users on query_logs.user_id = users.id " . $where . "and " . $where_lastmonth . $groupBy . $orderBy . $limit;
        $data['users']['lastmonth'] = $connection->createCommand($sql)->queryAll();
        $sql_count = $select . "count(*) as count " . $from . $where . "and " . $where_lastmonth;
        $data['users']['lastmonth_count'] = $connection->createCommand($sql_count)->queryOne();

        //var_dump($data);exit;
        return $this->render('index', $data);

    }

    public function actionShow()
    {
        $connection = Yii::$app->db;
        $key = $_REQUEST['key'];
        $value = $_REQUEST['value'];
        $type = "where_" . $_REQUEST['type'];

        $select = "select ";
        $column = "* ";
        $select_column = "users.chinesename, users.username, user_id, projects.name, query_logs.* ";
        $from = "from query_logs ";
        $join = "left join users on query_logs.user_id = users.id left join projects on projects.pro_id = query_logs.pro_id ";
        $where = "where (server_id in (select server_id from servers where environment = 'pro') or environment = 'pro') ";
        //$where = "where 1=1 ";
        $orderBy = "order by created_date desc ";

        $where_yesterday = "TO_DAYS(NOW( ) ) - TO_DAYS( created_date) = 1 ";//昨天数据
        $where_lastweek = "YEARWEEK(date_format(created_date,'%Y-%m-%d')) = YEARWEEK(now())-1 ";//上周数据
        $where_lastmonth = "PERIOD_DIFF( date_format( now( ) , '%Y%m' ) , date_format( created_date, '%Y%m' ) ) =1 "; //上个月
        $where_30day = "DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= date(created_date) ";//最近30天

        if ( $key == 'database' ) {
            $andWhere = " and query_logs.`database` = '" . $value . "'";
        } else if( $key == 'user' ) {
            $andWhere = " and query_logs.user_id = " . $value;
        }else{
            $andWhere = "";
        }

        $sql = $select . $select_column . $from . $join . $where . $andWhere . " and " . $$type . $orderBy;
        //echo $sql;exit;
        $data['list'] = $connection->createCommand($sql)->queryAll();
        //var_dump($data);exit;
        return $this->renderAjax('show', $data);
    }
}
?>