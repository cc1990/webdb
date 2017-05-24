<?php 
namespace common\models;

/**
* 
*/
class ApiUsersConfig extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'api_users_config';
    }

    public function rules()
    {
        return [
            [['username', 'from_ip', 'md5_key'], 'safe'],
        ];
    }
}