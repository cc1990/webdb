<?php 
namespace backend\modules\projects\models;

use Yii;
use yii\db\ActiveRecord;

class ProjectLog extends ActiveRecord
{
    public static function tableName()
    {
        return "project_update_logs";
    }

}

