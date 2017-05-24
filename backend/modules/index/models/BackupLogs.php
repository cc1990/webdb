<?php 
namespace backend\modules\index\models;

use yii\db\ActiveRecord;

class BackupLogs extends ActiveRecord
{
    public static function tableName()
    {
        return "backup_logs";
    }
}