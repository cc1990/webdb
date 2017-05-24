<?php
namespace backend\modules\backup\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Description 脚本备份日志
 *
 * @property integer $id
 */
class Logs extends ActiveRecord
{
    /**
     * Description 设置表名
     */
    public static function tableName()
    {
        return 'backup_sh_logs';
    }

    /**
     * Description 获取控制台数据
    */
    public function getControlList($where,$page,$limit)
    {
        $offset = ($page-1)*$limit;
        $sql = "select distinct(server_ip),backup_sh_servers.id,backup_sh_servers.serverName,archive_ip,archive_all_space,filesize,backup_remain_space,archive_remain_space,type,backup_sh_servers.status,start_time,end_time from(select * from backup_sh_logs order by start_time desc) as tmp left join backup_sh_servers on tmp.server_ip = backup_sh_servers.serverIp ";
        $sql .= "group by server_ip ";
        $sql .= $this->_getWhere($where,'having');
        $sql .= "limit {$offset},{$limit}";
        $controlList = $this->getDb()->createCommand($sql)->queryAll();
        return $controlList;
    }

    /**
     * Description 获取列表数据
     */
    public function getAll($where,$field = [],$page = 1,$limit = 15,$order = "start_time desc")
    {
        $result = $this->find()
            ->select($field)
            ->where($where)
            ->orderBy($order)
            ->offset((intval($page)-1) * intval($limit))
            ->limit(intval($limit))
            ->orderBy($order)
            ->all();
        return $result;
    }

    /**
     * Description 获取数量
     */
    public function getListCount($where)
    {
        $count = $this->find()
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * Description 获取数据量
    */
    public function getCount($where)
    {
        $sql = "select count(*) from (select distinct(server_ip) from (select * from backup_sh_logs order by start_time desc) as tmp left join backup_sh_servers on tmp.server_ip = backup_sh_servers.serverIp ";
        $sql .= $this->_getWhere($where);
        $sql .= "group by server_ip) tmp2";
        $result = $this->getDb()->createCommand($sql)->queryAll();
        return current($result[0]);
    }

    /**
     * Description 返回where字符串
    */
    private function _getWhere($where,$type = "where"){
        $whereStr = $type;
        if(!empty($where)){
            foreach($where as $key=>$value){
                if(is_array($value)){
                    if($value[0] == 'like') $whereStr .= " {$key} like '%{$value[1]}%' and";
                }else{
                    $whereStr .= " {$key}='{$value}' and";
                }
            }
        }
        return substr($whereStr,0,-4);
    }

    /**
     * Description 获取备份服务器列表
    */
    public function getServerList()
    {
        $sql = "select distinct(server_ip),serverName from backup_sh_logs logs left join backup_sh_servers servers on logs.server_ip = servers.serverIp group by serverIp";
        $result = $this->getDb()->createCommand($sql)->queryAll();
        return $result;
    }
}
