<?php 
namespace backend\modules\index\models;

use yii\db\ActiveRecord;

class Deployment extends ActiveRecord
{
    public static function tableName()
    {
        return "execute_deployment_logs";
    }
}