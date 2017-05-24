<?php
namespace backend\modules\backup\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Description 脚本备份服务器表
 *
 * @property integer $id
 */
class BackupShServers extends ActiveRecord
{
    /**
     * Description 设置表名
     */
    public static function tableName()
    {
        return 'backup_sh_servers';
    }

    /**
     * Description 获取列表数据
    */
    public function getAll($where,$field = [],$page = 1,$limit = 15,$order = "status desc")
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
    public function getCount($where)
    {
        $count = $this->find()
            ->where($where)
            ->count();
        return $count;
    }
}
