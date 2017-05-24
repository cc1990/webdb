<?php 
namespace backend\modules\index\models;

use yii\db\ActiveRecord;

class RighterLogs extends ActiveRecord
{
    public static function tableName()
    {
        return "righter_logs";
    }
}