<?php 

namespace backend\modules\correct\models;

use Yii;
use yii\db\ActiveRecord;

class SelfHelp extends ActiveRecord
{
    public static function tableName()
    {
        return 'selfhelp_config';
    }

    public static function primaryKey()
    {
        return ['status'];
    }
}