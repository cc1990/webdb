<?php
namespace backend\modules\backup\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Description 脚本备份服务器表
 *
 * @property integer $id
 */
class HostStrategy extends ActiveRecord
{
    /**
     * Description 设置表名
     */
    public static function tableName()
    {
        return 'backup_host_strategy';
    }
}
