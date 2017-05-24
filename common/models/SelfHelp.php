<?php 
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class SelfHelp extends ActiveRecord
{
    public static function tableName()
    {
        return 'selfhelp_scripts';
    }

    public function attributeLabels()
    {
        return [
            'workorder_no' => '工单流水号',
            'workorder_user' => '工单申请人',
            'environment' => '环境',
            'db_name' => '库名',
            'tb_name' => '表名',
            'sql' => '脚本',
            'server_ip' => '服务器IP',
            'backup_note' => '备份结果',
            'execute_note' => '执行结果',
            'workorder_type' => '工单类型',
        ];
    }
}