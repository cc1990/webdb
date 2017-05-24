<?php 
namespace backend\modules\index\models;

use Yii;
use yii\db\ActiveRecord;

/**
* 
*/
class Createdb extends ActiveRecord
{
    public static function tableName()
    {
        return 'create_db_logs';
    }

    public function rules()
    {
        return [
            ['db_name', 'unique','on' => ['create']],
            ['db_name', 'required','on' => ['create']],
            ['status', 'safe','on' => ['update']],
            [['db_name', 'server_id', 'server_ip', 'status'], 'safe', 'on' => 'default']
        ];
    }

    public function attributeLabels()
    {
        return [
            'db_name' => '数据库名',
            'server_ip' => '服务器IP',
            'status' => '当前步骤',
            'next_status' => '下一步操作',
            'is_independent_db' => '是否是独立ＩＰ服务器',
        ];
    }

    public function scenarios()
    {
        return [
            'create' => ['db_name'],
            'search' => ['db_name', 'server_ip'],
            'update' => ['status'],
            'default' => ['db_name', 'server_id', 'server_ip', 'status', 'create_time'],

        ];
    }
}