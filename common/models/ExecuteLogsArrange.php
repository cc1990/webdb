<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "execute_logs_arrange".
 *
 * @property string $log_id
 * @property string $user_id
 * @property string $host
 * @property string $database
 * @property string $script
 * @property integer $status
 * @property string $created_date
 * @property string $notes
 * @property integer $server_id
 */
class ExecuteLogsArrange extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'execute_logs_arrange';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'server_id'], 'integer'],
            [['created_date'], 'safe'],
            [['notes'], 'required'],
            [['notes'], 'string'],
            [['host'], 'string', 'max' => 15],
            [['database'], 'string', 'max' => 50],
            [['script'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'log_id' => 'Log ID',
            'user_id' => 'User ID',
            'host' => 'Host',
            'database' => 'Database',
            'script' => 'Script',
            'status' => 'Status',
            'created_date' => 'Created Date',
            'notes' => 'Notes',
            'server_id' => 'Server ID',
        ];
    }
}
