<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "audit_logs".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $execute_ids
 * @property string $create_time
 * @property string $update_time
 * @property integer $audit_person
 * @property integer $status
 * @property string $host
 * @property string $database
 * @property integer $server_id
 */
class AuditLogs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'audit_logs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'audit_person', 'status', 'server_id'], 'integer'],
            [['execute_ids'], 'required'],
            [['execute_ids'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['host'], 'string', 'max' => 15],
            [['database'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'execute_ids' => 'Execute Ids',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'audit_person' => 'Audit Person',
            'status' => 'Status',
            'host' => 'Host',
            'database' => 'Database',
            'server_id' => 'Server ID',
        ];
    }
}
