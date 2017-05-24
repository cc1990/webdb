<?php 
namespace backend\modules\correct\models;

use Yii;
use yii\db\ActiveRecord;

/**
* 
*/
class Log extends ActiveRecord
{
    public static function tableName()
    {
        return 'correct_logs';
    }

    public function rules()
    {
        return [
            [['workorder_no', 'workorder_time', 'workorder_user', 'workorder_title', 'workorder_reason', 'workorder_sql_checker', 'workorder_type', 'workorder_end_time', 'module_name', 'module_name', 'work_line', 'source', 'use_time', 'remark'], 'safe'],
            ['workorder_no', 'unique'],
            ['workorder_no', 'required'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'workorder_no' => '工单流水号',
            'workorder_time' => '创建时间',
            'workorder_user' => '工单申请人',
            'workorder_title' => '工单标题',
            'workorder_reason' => '订正原因',
            'workorder_sql_checker' => 'SQL验证人',
            'workorder_dba' => '工单执行人',
            'module_name' => '模块名称',
            'work_line' => '业务线',
            'workorder_type' => '工单类型',
            'workorder_end_time' => '结束时间',
            'db_names' => '数据库名',
            'use_time' => '工单耗时',
            'script_number' => '脚本数量',
            'influences_number' => '影响行数',
            'source' => '数据来源',
        ];
    }

}