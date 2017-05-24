<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "query_logs".
 *
 * @property string $log_id
 * @property string $user_id
 * @property string $host
 * @property string $database
 * @property string $script
 * @property string $result
 * @property integer $status
 * @property string $created_date
 * @property integer $server_id
 */
class QueryLogs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'query_logs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'server_id'], 'integer'],
            [['created_date'], 'safe'],
            [['host'], 'string', 'max' => 15],
            [['database', 'result'], 'string', 'max' => 50],
            [['script'], 'string']
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
            'result' => 'Result',
            'status' => 'Status',
            'created_date' => 'Created Date',
            'server_id' => 'Server ID',
        ];
    }
}
