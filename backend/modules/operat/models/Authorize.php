<?php

namespace backend\modules\operat\models;

/**
* 授权白名单
*/
class Authorize extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'operat_authorize_white';
    }

    public function rules()
    {
        return [
            [['server_id', 'server_ip', 'environment', 'type'], 'safe'],
            [['db_name', 'username', 'stop_time', 'sqloperation'], 'required']
        ];
    }

    public function attributeLabels()
    {
        return [
            'server_id' => '服务器选择',
            'server_ip' => '服务器IP',
            'environment' => '分库分表环境',
            'db_name' => '数据库名',
            'username' => '工号',
            'stop_time' => '截止时间',
            'sqloperation' => 'SQL操作',
            'type' => '执行环境'
        ];
    }
}