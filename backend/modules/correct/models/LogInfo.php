<?php 
namespace backend\modules\correct\models;

use Yii;
use yii\db\ActiveRecord;

/**
* 
*/
class LogInfo extends ActiveRecord
{
    public static function tableName()
    {
        return 'correct_logs_info';
    }

    public function rules()
    {
        return [
            [['scripts_number', 'influences_number'], 'integer']
        ];
    }

}